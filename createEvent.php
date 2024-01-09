<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$database = $_ENV['DB_NAME'];
$port = $_ENV['DB_PORT'];
$connection = new mysqli($host . ':' . $port, $user, $pass, $database);

if ($connection->connect_error) {
    die("Failed to connect to MySQL: " . $connection->connect_error);
}

$result = $connection->query("SELECT * FROM googleCalendarTable");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Retrieve values from the database and assign them to variables
        $summary = $row['summary'];
        $description = $row['description'];
        $start_datetime = date('Y-m-d\TH:i:s', strtotime($row['start_datetime']));
        $end_datetime = date('Y-m-d\TH:i:s', strtotime($row['end_datetime']));
        createGoogleCalendarEvent(
            $summary,
            $description,
            $start_datetime,
            $end_datetime
        );
    }
} else {
    echo "No data found in the 'googleCaledarTable' table.\n";
}
function createGoogleCalendarEvent(
    $summary,
    $description,
    $start_datetime,
    $end_datetime
) {
    $client = new Google\Client();
    $client->setApplicationName('mysql-to-google-calendar');
    $client->setScopes(Google\Service\Calendar::CALENDAR_EVENTS);
    $client->setAuthConfig('./credentials.json');
    $client->setAccessType('offline');

    $service = new Google\Service\Calendar($client);

    // Define event information using the provided parameters
    $event = new Google_Service_Calendar_Event(
        array(
            'summary' => $summary,
            'description' => $description,
            'start' => array(
                'dateTime' => $start_datetime,
                'timeZone' => 'America/New_York',
            ),
            'end' => array(
                'dateTime' => $end_datetime,
                'timeZone' => 'America/New_York',
            ),
        )
    );
    $calendarId = $_ENV['GOOGLE_CALENDAR_ID'];
    try {
        // Add event to the calendar
        $event = $service->events->insert($calendarId, $event);
        echo "Event '$summary' added to the Google Calendar. Event ID: " . $event->id . "\n";
    } catch (Google\Service\Exception $e) {
        echo "Error adding event to Google Calendar: " . $e->getMessage() . "\n";
        echo "Error details: " . json_encode($e->getErrors(), JSON_PRETTY_PRINT) . "\n";
    }

}

$result->close();
$connection->close();
?>