<?php
require '../vendor/autoload.php';

$MONGO_USERNAME = getenv('MONGODB_USERNAME');
$MONGO_PASSWORD = getenv('MONGODB_ROOT_PASSWORD');
$MONGO_HOST = getenv('MONGODB_HOST');
$MONGO_DATABASE = getenv('MONGODB_DATABASE');

$connectionString = sprintf(
    "mongodb://%s:%s@%s:27017/%s?authSource=admin",
    $MONGO_USERNAME,
    $MONGO_PASSWORD,
    $MONGO_HOST,
    $MONGO_DATABASE
);

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
    $mongoClient = new MongoDB\Client($connectionString);
} catch (Exception $e) {
    die("Failed to connect to MongoDB: " . $e->getMessage());
}

// Create connection
$sqlconn = new mysqli($MYSQL_SERVER, $MYSQL_USERNAME, $MYSQL_PASSWORD, $MYSQL_DATABASE);


// Check connection
if ($sqlconn->connect_error) {
    die("Connection failed: " . $sqlconn->connect_error);
}

// Check if the login form is submitted via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Retrieve form data sent via AJAX
    $username = $_POST['username'];
    $password = $_POST['password'];

    echo json_encode(array("success" => "User authenticated successfully."));
    return;

    // Prepare SQL statement
    $stmt = $sqlconn->prepare("SELECT id FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists in the database
    if ($stmt->num_rows > 0) {
        // User authenticated successfully
        echo json_encode(array("success" => "User authenticated successfully."));
    } else {
        // Authentication failed
        echo json_encode(array("error" => "Invalid username or password."));
    }

    // Close statement and connection
    $stmt->close();
    $sqlconn->close();
} else {
    // Handle non-AJAX requests (optional)
}
