<?php
include 'db.php';
session_start();

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode     = $_POST['mode'];           // train, bus, plane
    $date     = $_POST['date'];
    $time     = $_POST['time'];
    $from     = $_POST['from_city'];
    $to       = $_POST['to_city'];
    $coach    = $_POST['coach'] ?? null;  // train only
    $class    = $_POST['class'] ?? null;  // plane only
    $seat     = $_POST['seat'];
    $name     = $_POST['name'];
    $age      = $_POST['age'];
    $mobile   = $_POST['mobile'];
    $payment  = $_POST['payment'];

    // Generate dummy PNR and seat number
    $pnr = rand(1000000000, 9999999999);
    $seat_no = rand(1, 100);

    // Insert booking into DB
    $query = "INSERT INTO bookings (
                mode, date, time, from_city, to_city, coach, class, seat, passenger_name,
                age, mobile, payment_method, pnr, seat_number
              ) VALUES (
                '$mode', '$date', '$time', '$from', '$to', 
                ".($coach ? "'$coach'" : "NULL").",
                ".($class ? "'$class'" : "NULL").",
                '$seat', '$name', '$age', '$mobile', '$payment', '$pnr', '$seat_no'
              )";

    if (mysqli_query($conn, $query)) {
        echo "<script>
            alert('Your $mode ticket is booked successfully!\\nPNR: $pnr\\nSeat No: $seat_no');
            window.location.href = 'index.php';
        </script>";
    } else {
        echo "<script>
            alert('Error while booking. Please try again.');
            window.history.back();
        </script>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>
