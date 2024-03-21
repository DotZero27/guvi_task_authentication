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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.html', 301);
    die();
}

try {
    $redis = new RedisClient([
        'host'   => getenv('REDIS_HOST'),
    ]);

    $mongo = new MongoClient($mongoConnectionString);
} catch (\Exception $e) {
    throw new \Exception('Failed to connect to DB: ' . $e->getMessage());
}

// Database connection parameters
$MYSQL_SERVER = getenv('MYSQL_SERVER');
$MYSQL_USERNAME = getenv('MYSQL_USERNAME');
$MYSQL_PASSWORD = getenv('MYSQL_ROOT_PASSWORD');
$MYSQL_DATABASE = getenv('MYSQL_DATABASE');

try {
    // Check if the login form is submitted via AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'POST') {

        // Create connection
        $sqlconn = new mysqli($MYSQL_SERVER, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE);

        // Check connection
        if ($sqlconn->connect_error) {
            throw new Exception("Connection failed: " . $sqlconn->connect_error);
        }

        // Retrieve form data sent via AJAX
        $email = $_POST['email'];
        $password = $_POST['password'];

        if ($email == "" || $password == "") {
            echo json_encode(array("error" => "Fill all the fields"));
            exit();
        }

        // Prepare and execute SELECT statement
        $stmt = $sqlconn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Get the result set
        $result = $stmt->get_result();

        // Check if user exists in the database
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $hashed_password = $row['password'];

            if (password_verify($password, $hashed_password)) {

                unset($row['password']);

                $session_id = uniqid('session:');

                $collection = $mongo->selectCollection(getenv('MONGO_DATABASE'), 'users');

                // Execute the search
                $profile = $collection->findOne([
                    'userId' => strval($row['id'])
                ]);

                // Check if a document was found
                if ($profile) {
                    unset($profile['userId'], $profile['_id']);
                }

                $redis->set($session_id, json_encode(['user' => $row, 'profile' => $profile]));

                $redis->expireAt($session_id, strtotime('+5 minutes'));

                // Send User Result
                echo json_encode(array(
                    "success" => true,
                    "session_id" => $session_id
                ));
            } else {
                // Passwords don't match
                echo json_encode(array("error" => "Invalid email or password"));
            }
        } else {
            //User not found
            echo json_encode(array("error" => "Invalid email or password"));
        }

        // Close statement
        $stmt->close();
    } else {
        // Handle non-AJAX requests (optional)
    }
} catch (Exception $e) {
    echo json_encode(array("error" => $e->getMessage()));
}

// Close connection
if (isset($sqlconn)) {
    $sqlconn->close();
}
