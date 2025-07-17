<?php
include 'include/db.php'; 
session_start();

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

if (isset($_GET['type']) && isset($_GET['id'])) {
    $booking_type = $_GET['type'];
    $booking_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

    if ($booking_id === false || $booking_id <= 0) {
        $message = "Invalid booking ID.";
        $message_type = "danger";
    } else {
        $table_name = '';
        $schedule_id_col = 'schedule_id'; // Most tables use this
        $id_col = 'id'; // Most tables use this for primary key

        switch ($booking_type) {
            case 'bus':
                $table_name = 'bus_bookings';
                break;
            case 'plane':
                $table_name = 'plane_bookings';
                break;
            case 'train':
                $table_name = 'train_bookings';
                break;
            default:
                $message = "Invalid booking type specified.";
                $message_type = "danger";
                break;
        }

        if (!empty($table_name)) {
            // Start transaction
            mysqli_begin_transaction($conn);

            try {
                // 1. Get the current status and schedule_id of the booking
                $stmt_fetch = mysqli_prepare($conn, "SELECT status, schedule_id FROM $table_name WHERE $id_col = ? AND user_id = ? FOR UPDATE");
                if (!$stmt_fetch) {
                    throw new Exception("Error preparing fetch statement: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt_fetch, "ii", $booking_id, $user_id);
                mysqli_stmt_execute($stmt_fetch);
                $result_fetch = mysqli_stmt_get_result($stmt_fetch);
                $booking_data = mysqli_fetch_assoc($result_fetch);
                mysqli_stmt_close($stmt_fetch);

                if (!$booking_data) {
                    throw new Exception("Booking not found or you don't have permission to cancel it.");
                }

                if ($booking_data['status'] == 'Cancelled') {
                    throw new Exception("This booking is already cancelled.");
                }

                $schedule_id = $booking_data['schedule_id'];

                // 2. Update the booking status to 'Cancelled'
                $stmt_update_booking = mysqli_prepare($conn, "UPDATE $table_name SET status = 'Cancelled' WHERE $id_col = ? AND user_id = ?");
                if (!$stmt_update_booking) {
                    throw new Exception("Error preparing update booking statement: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt_update_booking, "ii", $booking_id, $user_id);
                if (!mysqli_stmt_execute($stmt_update_booking)) {
                    throw new Exception("Error updating booking status: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt_update_booking);

                // 3. Increment available_seats in the schedule table
                $stmt_update_schedule = mysqli_prepare($conn, "UPDATE schedule SET available_seats = available_seats + 1 WHERE id = ?");
                if (!$stmt_update_schedule) {
                    throw new Exception("Error preparing update schedule statement: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt_update_schedule, "i", $schedule_id);
                if (!mysqli_stmt_execute($stmt_update_schedule)) {
                    throw new Exception("Error updating available seats: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt_update_schedule);

                // Commit transaction
                mysqli_commit($conn);
                $message = "Booking cancelled successfully! Seats have been made available again.";
                $message_type = "success";

            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($conn);
                $message = "Error cancelling booking: " . $e->getMessage();
                $message_type = "danger";
            }
        }
    }
} else {
    $message = "No booking type or ID specified.";
    $message_type = "danger";
}

// Redirect back to my_bookings.php with a message
header("Location: my_bookings.php?message=" . urlencode($message) . "&type=" . urlencode($message_type));
exit();
?>
