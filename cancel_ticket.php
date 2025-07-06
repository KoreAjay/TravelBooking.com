<?php
include 'include/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['booking_id']);
    $mode = $_POST['mode'];

    $table_map = [
        'Train' => 'train_bookings',
        'Bus' => 'bus_bookings',
        'Plane' => 'plane_bookings',
    ];

    if (!isset($table_map[$mode])) {
        die('Invalid booking type.');
    }

    $table = $table_map[$mode];
    $sql = "DELETE FROM $table WHERE id = $id AND user_id = {$_SESSION['user_id']}";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('❌ Ticket cancelled successfully.'); window.location.href = 'my_bookings.php';</script>";
    } else {
        echo "<script>alert('Error cancelling ticket.'); window.location.href = 'my_bookings.php';</script>";
    }
}
?>
