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

// Check if the table 'CalEvents' already exists
$tableCheck = $connection->query("SHOW TABLES LIKE 'CalEvents'");

if ($tableCheck->num_rows == 0) {
    // Table 'CalEvents' does not exist, create it
    $createTableSQL = "
CREATE TABLE `CalEvents` (
  `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `PCEventID` int(11) NOT NULL,
  `title` text COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `location` text COLLATE utf8_unicode_ci,
  `time_from` int(11) NOT NULL,
  `time_to` int(11) NOT NULL,
  `google_calendar_event_id` text COLLATE utf8_unicode_ci,
  `created` text COLLATE utf8_unicode_ci NOT NULL,
  `OwnerName` text COLLATE utf8_unicode_ci NOT NULL,
  `OwnerPhone` text COLLATE utf8_unicode_ci NOT NULL,
  `OwnerEmail` text COLLATE utf8_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
";

    if ($connection->query($createTableSQL) === TRUE) {
        echo "Table 'CalEvents' created successfully!\n";
    } else {
        echo "Error creating table: " . $connection->error . "\n";
    }
}

// Read data from the JSON file
$jsonData = file_get_contents('sample.json');
$eventData = json_decode($jsonData, true);

// Prepare the SQL statement
$insertDataSQL = $connection->prepare("INSERT INTO CalEvents (
    PCEventID, title, description, location, time_from, time_to, google_calendar_event_id, created, OwnerName, OwnerPhone, OwnerEmail
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
)");

// Bind parameters
$insertDataSQL->bind_param("isssiiissss", $eventData["PCEventID"], $eventData["title"], $eventData["description"], $eventData["location"], $eventData["time_from"], $eventData["time_to"], $eventData["google_calendar_event_id"], $eventData["created"], $eventData["OwnerName"], $eventData["OwnerPhone"], $eventData["OwnerEmail"]);

// Execute the statement
if ($insertDataSQL->execute()) {
    echo "Event '{$eventData["title"]}' added to the database!\n";
} else {
    echo "Error adding event to the database: " . $insertDataSQL->error . "\n";
}

// Close the prepared statement
$insertDataSQL->close();

$connection->close();
?>
