<?php // get_schedules.php
include 'include/db.php'; // Ensure your database connection file is correctly included

header('Content-Type: application/json'); // Set header for JSON response

$schedules = []; // Initialize an empty array for schedules

// Check if all required POST parameters are set (now only from_city and to_city)
if (isset($_POST['from_city']) && isset($_POST['to_city'])) {
    // Sanitize user inputs to prevent SQL injection
    $from_city = mysqli_real_escape_string($conn, $_POST['from_city']);
    $to_city = mysqli_real_escape_string($conn, $_POST['to_city']);

    // Get current date and time to filter out past schedules
    $current_datetime = date('Y-m-d H:i:s');

    // Query to fetch schedules from the 'schedule' table for 'bus' mode
    // Filters by from_city, to_city, mode='bus', and ensures the schedule date/time is in the future or present.
    $sql_schedules = "SELECT id, mode, from_city, to_city, date, time, coach_class, fare_amount, total_seats, available_seats
                      FROM schedule
                      WHERE from_city = '$from_city'
                      AND to_city = '$to_city'
                      AND mode = 'bus'  -- IMPORTANT: Filter for bus only
                      AND CONCAT(date, ' ', time) >= '$current_datetime'
                      ORDER BY date ASC, time ASC";

    $result_schedules = mysqli_query($conn, $sql_schedules);

    if ($result_schedules) {
        while ($row = mysqli_fetch_assoc($result_schedules)) {
            $schedules[] = $row; // Add each fetched row to the schedules array
        }
    } else {
        // Log the error for debugging
        error_log("SQL Error in get_schedules.php: " . mysqli_error($conn));
    }
}

// Return the schedules as a JSON array
echo json_encode($schedules);

// Close the database connection
mysqli_close($conn);
?>