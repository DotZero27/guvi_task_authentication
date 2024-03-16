<?php
require '../vendor/autoload.php';

use Predis\Client;

// Create a new instance of the Predis Client pointing to your Redis server
$client = new Client([
    'host'   => getenv('REDIS_HOST'),
]);
// Set a key-value pair in Redis
$client->set('key', 'test');

// Retrieve the value for a given key from Redis
$value = $client->get('key');

echo $value; // Output: value