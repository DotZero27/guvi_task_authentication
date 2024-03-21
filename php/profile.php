<?php
require '../vendor/autoload.php';

use Predis\Client as RedisClient;
use MongoDB\Client as MongoClient;

$mongoConnectionString = sprintf(
    "mongodb://%s:%s@%s:27017/%s?authSource=admin",
    getenv('MONGO_USERNAME'),
    getenv('MONGO_ROOT_PASSWORD'),
    getenv('MONGO_HOST'),
    getenv('MONGO_DATABASE'),
);

$headers = getallheaders();

if (!array_key_exists('Authorization', $headers)) {
    http_response_code(401);
    echo json_encode(["error" => "Authorization header is missing"]);
    exit();
}

// Extract the token from the header
list($token_type, $token) = explode(" ", $headers['Authorization'], 2);

// Check if the token type is Bearer
if ($token_type !== 'Bearer') {
    http_response_code(401);
    exit("Invalid token type");
}

// Initialize both Redis and Mongo Client
try {
    $redis = new RedisClient([
        'host'   => getenv('REDIS_HOST'),
    ]);

    $mongo = new MongoClient($mongoConnectionString);
} catch (\Exception $e) {
    throw new \Exception('Failed to connect to DB: ' . $e->getMessage());
}

if (!$redis->exists($token)) {
    http_response_code(401);
    echo "Invalid Session Key";
    exit();
}

$user = json_decode($redis->get($token), true);
$expirationTime = $redis->ttl($token);

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['fieldId']) && isset($_POST['value'])) {
        $fieldId = $_POST['fieldId'];
        $value = $_POST['value'];

        $collection = $mongo->selectCollection(getenv('MONGO_DATABASE'), 'users');

        $result = $collection->updateOne(
            ['userId' =>strval($user['user']['id'])],
            ['$set' => [$fieldId => $value]],
            ['upsert' => true]
        );

        if ($result->isAcknowledged()) {

            $user['profile'][$fieldId] = $value;
            $redis->set($token, json_encode($user));
            $redis->expireAt($token, strtotime('now') + $expirationTime);

            echo json_encode(["success" => "Profile data updated successfully"]);
        } else {
            echo json_encode(["error" => "Failed to update profile data"]);
        }
        
        exit();
    } else {
        // If required parameters are not provided
        http_response_code(400);
        echo json_encode(["error" => "Incomplete POST data"]);
        exit();
    }
}

echo json_encode(["success" => "Session Validated", "data" => $user, "expiration_time" => $expirationTime]);
