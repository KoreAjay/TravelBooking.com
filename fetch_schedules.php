<?php
// fetch_schedules.php
// This script fetches available schedules based on user's search criteria (from_city, to_city, date, mode)

// Change this include path from '../include/db.php' to './include/db.php' or 'include/db.php'
include 'include/db.php'; // Correct path assuming db.php is in TravalBooking.com/include/

// Set content type to JSON
header('Content-Type: text/html'); // Changed back to html to render cards, previously it was JSON

// Initialize variables
$mode = $_GET['mode'] ?? '';
$from_city = $_GET['from_city'] ?? '';
$to_city = $_GET['to_city'] ?? '';
$date = $_GET['date'] ?? '';

// Basic validation for inputs
if (empty($mode) || empty($from_city) || empty($to_city) || empty($date)) {
    echo '<p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Please provide all search criteria.</p>';
    exit();
}

// Prepare the SQL query
$sql = "SELECT id, time, from_city, to_city, fare_amount, coach_class, available_seats 
        FROM schedule 
        WHERE mode = ? AND from_city LIKE ? AND to_city LIKE ? AND date = ? AND available_seats > 0
        ORDER BY time ASC";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    // Bind parameters
    $from_city_param = '%' . $from_city . '%';
    $to_city_param = '%' . $to_city . '%';
    mysqli_stmt_bind_param($stmt, "ssss", $mode, $from_city_param, $to_city_param, $date);

    // Execute query
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo '<h4 class="text-start mb-3"><i class="fas fa-list-alt me-2"></i>Available ' . htmlspecialchars(ucfirst($mode)) . ' Schedules</h4>';
        echo '<div class="row">'; // Bootstrap row for cards
        while ($row = mysqli_fetch_assoc($result)) {
            $coach_label = ($mode === 'plane') ? 'Class' : 'Coach'; // Label dynamically based on mode
            echo '
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="schedule-card" 
                    data-id="' . htmlspecialchars($row['id']) . '" 
                    data-from="' . htmlspecialchars($row['from_city']) . '" 
                    data-to="' . htmlspecialchars($row['to_city']) . '" 
                    data-date="' . htmlspecialchars($date) . '" 
                    data-time="' . htmlspecialchars($row['time']) . '" 
                    data-coach="' . htmlspecialchars($row['coach_class']) . '"
                    data-class="' . htmlspecialchars($row['coach_class']) . '" data-fare="' . htmlspecialchars($row['fare_amount']) . '">
                    <h5>' . htmlspecialchars($row['from_city']) . ' <i class="fas fa-arrow-right mx-2"></i> ' . htmlspecialchars($row['to_city']) . '</h5>
                    <p><i class="fas fa-calendar-alt me-2"></i>Date: ' . htmlspecialchars($date) . '</p>
                    <p><i class="fas fa-clock me-2"></i>Departure: ' . htmlspecialchars(substr($row['time'], 0, 5)) . '</p>
                    <p><i class="fas fa-chair me-2"></i>Available Seats: ' . htmlspecialchars($row['available_seats']) . '</p>
                    <p><i class="fas fa-tag me-2"></i>' . $coach_label . ': ' . htmlspecialchars($row['coach_class']) . '</p>
                    <p class="fare"><i class="fas fa-rupee-sign me-1"></i>' . htmlspecialchars(number_format($row['fare_amount'], 2)) . '</p>
                    <button class="btn btn-primary btn-sm mt-2 select-schedule-btn" data-id="' . htmlspecialchars($row['id']) . '">Select Schedule</button>
                </div>
            </div>';
        }
        echo '</div>'; // Close row
        echo '<p class="text-center mt-3 text-muted"><i class="fas fa-hand-pointer me-2"></i>Click on a schedule to proceed with booking.</p>';
    } else {
        echo '<p class="text-info"><i class="fas fa-info-circle me-2"></i>No ' . htmlspecialchars(ucfirst($mode)) . ' schedules found for your selected criteria. Try different dates or routes.</p>';
    }

    mysqli_stmt_close($stmt);
} else {
    echo '<p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Database query error: ' . mysqli_error($conn) . '</p>';
}

// It's good practice to close the connection when the script finishes.
mysqli_close($conn);
?>