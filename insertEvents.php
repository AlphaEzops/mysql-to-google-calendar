<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$database = $_ENV['DB_NAME'];
$port = $_ENV['DB_PORT'];
$googleCredentials = $_ENV['GOOGLE_CREDENTIALS_PATH'];
$calendarId = $_ENV['GOOGLE_CALENDAR_ID'];

// Connect to MySQL with explicit socket path
$connection = new mysqli($host . ':' . $port, $user, $pass, $database);

// Check connection
if ($connection->connect_error) {
    die("Failed to connect to MySQL: " . $connection->connect_error);
}

// Check if the table 'googleCalendarTable' already exists
$tableCheck = $connection->query("SHOW TABLES LIKE 'googleCalendarTable'");

if ($tableCheck->num_rows == 0) {
    // Table 'googleCalendarTable' does not exist, create it
    $createTableSQL = "
    CREATE TABLE googleCalendarTable (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        summary VARCHAR(255) NOT NULL,
        description TEXT,
        start_datetime DATETIME,
        end_datetime DATETIME
    );
    ";

    if ($connection->query($createTableSQL) === TRUE) {
        echo "Table 'googleCalendarTable' created successfully!\n";
    } else {
        echo "Error creating table: " . $connection->error . "\n";
    }
}


// Read data from the JSON file
$jsonData = file_get_contents('sample.json');
$eventData = json_decode($jsonData, true);

// Insert data into 'googleCalendarTable'
$insertDataSQL = "INSERT INTO googleCalendarTable (
    summary, description, start_datetime, end_datetime
) VALUES (
    '{$eventData["summary"]}', '{$eventData["description"]}', '{$eventData["start"]["dateTime"]}', '{$eventData["end"]["dateTime"]}'
)";

if ($connection->query($insertDataSQL) === TRUE) {
    echo "Event '{$eventData["summary"]}' added to the database!\n";
} else {
    echo "Error adding event to the database: " . $connection->error . "\n";
}

$connection->close();

?>