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

// Only select events that do not have a Google Calendar ID yet
$result = $connection->query("SELECT * FROM CalEvents WHERE google_calendar_event_id IS NULL OR google_calendar_event_id = 0");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Retrieve values from the database and assign them to variables
        $id = $row['id'];
        $PCEventID = $row['PCEventID'];
        $summary = $row['title'];
        $description = $row['description'];
        $location = $row['location'];
        $start_datetime = date('Y-m-d\TH:i:s', $row['time_from']);
        $end_datetime = date('Y-m-d\TH:i:s', $row['time_to']);
        $google_calendar_event_id = $row['google_calendar_event_id'];
        $created = $row['created'];
        $OwnerName = $row['OwnerName'];
        $OwnerPhone = $row['OwnerPhone'];
        $OwnerEmail = $row['OwnerEmail'];

        // If the event has not been created in Google Calendar yet
        if (empty($google_calendar_event_id)) {
            $event_id = createGoogleCalendarEvent(
                $PCEventID,
                $summary,
                $description,
                $location,
                $start_datetime,
                $end_datetime,
                $created,
                $OwnerName,
                $OwnerPhone,
                $OwnerEmail
            );

            $updateSQL = $connection->prepare("UPDATE CalEvents SET google_calendar_event_id = ? WHERE id = ?");
            $updateSQL->bind_param("si", $event_id, $id);
            $updateSQL->execute();
        }
    }
} else {
    echo "No data found in the 'CalEvents' table.\n";
}

function createGoogleCalendarEvent(
    $PCEventID,
    $summary,
    $description,
    $location,
    $start_datetime,
    $end_datetime,
    $created,
    $OwnerName,
    $OwnerPhone,
    $OwnerEmail
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
            'location' => $location,
            'description' => $description,
            'start' => array(
                'dateTime' => $start_datetime,
                'timeZone' => 'America/New_York',
            ),
            'end' => array(
                'dateTime' => $end_datetime,
                'timeZone' => 'America/New_York',
            ),
            'owner' => array(
                'displayName' => $OwnerName,
                'emailAddress' => $OwnerEmail,
                'phoneNumber' => $OwnerPhone,
            ),
        )
    );
    $calendarId = $_ENV['GOOGLE_CALENDAR_ID'];

    try {
        // Add event to the calendar
        $createdEvent = $service->events->insert($calendarId, $event);
        $event_id = $createdEvent->id;
        echo "Event '$summary' added to the Google Calendar. Event ID: $event_id\n";
        return $event_id;
    } catch (Google\Service\Exception $e) {
        echo "Error adding event to Google Calendar: " . $e->getMessage() . "\n";
        echo "Error details: " . json_encode($e->getErrors(), JSON_PRETTY_PRINT) . "\n";
        return null;
    }
}

$result->close();
$connection->close();