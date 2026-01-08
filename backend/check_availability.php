<?php
// backend/check_availability.php
require_once "connection.php";
require_once "select_query_maria.php";

if (isset($_GET['date']) && isset($_GET['vet_id'])) {
    $date = $_GET['date'];
    $vetID = $_GET['vet_id'];

    // Call the function we just added
    $bookedTimes = getBookedTimesMaria($date, $vetID);

    // Return the data as JSON so Javascript can read it
    header('Content-Type: application/json');
    echo json_encode($bookedTimes);
}
?>