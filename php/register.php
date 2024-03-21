<?php
require '../vendor/autoload.php';

use Predis\Client as RedisClient;

// Database connection parameters
$MYSQL_SERVER = getenv('MYSQL_SERVER');
$MYSQL_USERNAME = getenv('MYSQL_USERNAME');
$MYSQL_PASSWORD = getenv('MYSQL_ROOT_PASSWORD');
$MYSQL_DATABASE = getenv('MYSQL_DATABASE');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.html', 301);
    die();
}

try {
    // Check if the login form is submitted via AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $_SERVER['REQUEST_METHOD'] === 'POST') {

        try {
            $redis = new RedisClient([
                'host'   => getenv('REDIS_HOST'),
            ]);
        
        } catch (\Exception $e) {
            throw new \Exception('Failed to connect to DB: ' . $e->getMessage());
        }

        // Create connection
        $sqlconn = new mysqli($MYSQL_SERVER, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE);

        // Check connection
        if ($sqlconn->connect_error) {
            throw new Exception("Connection failed: " . $sqlconn->connect_error);
        }
        // Retrieve form data sent via AJAX
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        $password_pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

        $fields = array("email", "password", "confirm_password");
        foreach ($fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            echo json_encode(array("error" => "Fill all the fields", "missing_fields" => $missing_fields));
            exit();
        }

        if (!preg_match($password_pattern, $password)) {
            echo json_encode(array("error" => "Password not strong","passwordWeak"=>true));
            exit();
        }
        
        if ($password !== $confirm_password) {
            echo json_encode(array("error" => "Password and confirm password don't match."));
            exit();
        }

        $checkexistinguser = $sqlconn->prepare("SELECT id FROM users WHERE email = ?");
        $checkexistinguser->bind_param("s", $email);
        $checkexistinguser->execute();

        if ($checkexistinguser->get_result()->num_rows > 0) {
            echo json_encode(array("error" => "User already exists"));
            exit();
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement
        $stmt = $sqlconn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashed_password);
        $stmt->execute();

        // Prepare and execute SELECT statement
        $user = $sqlconn->prepare("SELECT id, email, updated_at, created_at FROM users WHERE email = ?");
        $user->bind_param("s", $email);
        $user->execute();

        // Get the result set
        $result = $user->get_result();

        // Check if user exists in the database
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            $session_id = uniqid('session:');

            $redis->set($session_id, json_encode(['user' => $row]));
            $redis->expireAt($session_id, strtotime('+5 minutes'));

            // Return the session ID
            echo json_encode(array(
                "success" => true,
                "session_id" => $session_id
            ));

        } else {
            // Failed to get user
            echo json_encode(array("error" => "Failed to authenticate"));
        }

        // Close statement
        $stmt->close();
        $user->close();
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
