<?php
// get_train_schedules.php
include 'include/db.php'; // Ensure this path is correct

header('Content-Type: application/json');

$response = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $from_city = isset($_POST['from_city']) ? mysqli_real_escape_string($conn, $_POST['from_city']) : '';
    $to_city = isset($_POST['to_city']) ? mysqli_real_escape_string($conn, $_POST['to_city']) : '';

    if (!empty($from_city) && !empty($to_city)) {
        // Fetch schedules for 'train' mode based on from_city and to_city
        // Also order by date and time to show upcoming schedules first
        $sql = "SELECT id, date, time, mode, coach_class, fare_amount, available_seats, total_seats, from_city, to_city
                FROM schedule
                WHERE mode = 'train'
                AND from_city = '$from_city'
                AND to_city = '$to_city'
                AND date >= CURDATE() -- Only show schedules from today onwards
                ORDER BY date ASC, time ASC";

        $result = mysqli_query($conn, $sql);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $response[] = $row;
            }
        } else {
            // Log the error for debugging
            error_log("Database error in get_train_schedules.php: " . mysqli_error($conn));
            // Return an empty array or an error status if you handle it specifically in JS
            // For now, an empty array will simply result in "No trains found" message on the client.
        }
    }
}

echo json_encode($response);

if (isset($conn)) {
    mysqli_close($conn);
}
?>