<?php
// book_bus.php
include 'include/db.php'; // Ensure this path is correct for your database connection
session_start();


// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login to book a ticket.'); window.location.href = 'login.php';</script>";
    exit();
}


// Check if username is set in session, if not, set a default (fallback)
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'Guest';
}

$message = '';
$message_type = '';

// --- Handle POST Request for Booking Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];

    // Sanitize and validate inputs
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    $passenger_name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $passenger_age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $seat_number = filter_input(INPUT_POST, 'seat', FILTER_SANITIZE_STRING); // This is seat_preference from form
    $mobile_number = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $fare_amount_submitted = filter_input(INPUT_POST, 'hidden_fare_input', FILTER_VALIDATE_FLOAT);

    // Validate essential inputs
    if (!$schedule_id || !$passenger_name || !$passenger_age || !$seat_number || !$mobile_number || !$payment_method || $fare_amount_submitted === false) {
        $message = "Please fill in all required booking details.";
        $message_type = "danger";
    } elseif ($passenger_age <= 0) {
        $message = "Please enter a valid age.";
        $message_type = "danger";
    } else {
        // Start a transaction for atomicity (optional but recommended for real-world)
        mysqli_begin_transaction($conn);
        $transaction_success = true;

        // 1. Fetch schedule details to verify fare, seats, and get other details for booking table
        $stmt_schedule = mysqli_prepare($conn, "SELECT date, time, from_city, to_city, fare_amount, available_seats FROM schedule WHERE id = ? AND mode = 'bus' FOR UPDATE"); // FOR UPDATE locks the row
        mysqli_stmt_bind_param($stmt_schedule, "i", $schedule_id);
        mysqli_stmt_execute($stmt_schedule);
        $result_schedule = mysqli_stmt_get_result($stmt_schedule);
        $schedule_data = mysqli_fetch_assoc($result_schedule);
        mysqli_stmt_close($stmt_schedule);

        if (!$schedule_data) {
            $message = "Selected schedule not found or invalid.";
            $message_type = "danger";
            $transaction_success = false;
        } elseif ($schedule_data['available_seats'] <= 0) {
            $message = "No available seats for this bus. Please choose another schedule.";
            $message_type = "danger";
            $transaction_success = false;
        } elseif (abs($schedule_data['fare_amount'] - $fare_amount_submitted) > 0.01) { // Compare float with tolerance
            $message = "Fare amount mismatch. Please try again.";
            $message_type = "danger";
            $transaction_success = false;
        } else {
            // All good, proceed with booking
            $booking_date = $schedule_data['date'];
            $booking_time = $schedule_data['time'];
            $from_city = $schedule_data['from_city'];
            $to_city = $schedule_data['to_city'];
            $fare_amount = $schedule_data['fare_amount']; // Use fare from DB

            // 2. Insert into bus_bookings table
            // CORRECTED: Column names 'passenger_name' to 'name' and 'passenger_age' to 'age'
            $stmt_insert = mysqli_prepare($conn, "INSERT INTO bus_bookings (user_id, schedule_id, name, age, seat_number, mobile_number, booking_date, booking_time, from_city, to_city, fare_amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
            if ($stmt_insert) {
                // CORRECTED LINE BELOW: Changed type definition string from "iisississds" to "iisississsds"
                mysqli_stmt_bind_param($stmt_insert, "iisississsds",
                    $user_id, $schedule_id, $passenger_name, $passenger_age, $seat_number,
                    $mobile_number, $booking_date, $booking_time, $from_city, $to_city,
                    $fare_amount, $payment_method
                );
                if (!mysqli_stmt_execute($stmt_insert)) {
                    $message = "Booking failed: " . mysqli_stmt_error($stmt_insert);
                    $message_type = "danger";
                    $transaction_success = false;
                }
                mysqli_stmt_close($stmt_insert);
            } else {
                $message = "Database error: Could not prepare insert statement.";
                $message_type = "danger";
                $transaction_success = false;
            }

            // 3. Decrement available seats in schedule table if booking was successful so far
            if ($transaction_success) {
                $new_available_seats = $schedule_data['available_seats'] - 1;
                $stmt_update_seats = mysqli_prepare($conn, "UPDATE schedule SET available_seats = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt_update_seats, "ii", $new_available_seats, $schedule_id);
                if (!mysqli_stmt_execute($stmt_update_seats)) {
                    $message = "Failed to update seat availability: " . mysqli_stmt_error($stmt_update_seats);
                    $message_type = "danger";
                    $transaction_success = false;
                }
                mysqli_stmt_close($stmt_update_seats);
            }
        }

        // Finalize transaction
        if ($transaction_success) {
            mysqli_commit($conn);
            $_SESSION['message'] = "Bus ticket booked successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: my_bookings.php"); // Redirect to my_bookings page
            exit();
        } else {
            mysqli_rollback($conn);
            // Error message already set
        }
    }
}

// --- Handle Direct Access (GET request with ID) or search results pre-fill ---
$prefill_schedule_id = null;
$prefill_from_city = '';
$prefill_to_city = '';
$prefill_travel_date = '';
$prefill_travel_time = '';
$prefill_fare_amount = '';
$prefill_available_seats = 0; // Initialize available seats

// Check for URL parameters to pre-fill search and potentially select a schedule
if (isset($_GET['id']) && isset($_GET['mode']) && $_GET['mode'] === 'bus') {
    $requested_schedule_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($requested_schedule_id) {
        $stmt_prefill_schedule = mysqli_prepare($conn, "SELECT date, time, from_city, to_city, fare_amount, available_seats FROM schedule WHERE id = ? AND mode = 'bus' LIMIT 1");
        if ($stmt_prefill_schedule) {
            mysqli_stmt_bind_param($stmt_prefill_schedule, "i", $requested_schedule_id);
            mysqli_stmt_execute($stmt_prefill_schedule);
            $result_prefill = mysqli_stmt_get_result($stmt_prefill_schedule);
            $prefill_data = mysqli_fetch_assoc($result_prefill);

            if ($prefill_data) {
                $prefill_schedule_id = $requested_schedule_id;
                $prefill_from_city = $prefill_data['from_city'];
                $prefill_to_city = $prefill_data['to_city'];
                $prefill_travel_date = $prefill_data['date'];
                $prefill_travel_time = $prefill_data['time'];
                $prefill_fare_amount = $prefill_data['fare_amount'];
                $prefill_available_seats = $prefill_data['available_seats'];
            }
            mysqli_stmt_close($stmt_prefill_schedule);
        }
    }
}

// --- Search functionality (if user searches on this page) ---
$schedules = [];
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['search'])) {
    $search_from = filter_input(INPUT_GET, 'from_city', FILTER_SANITIZE_STRING);
    $search_to = filter_input(INPUT_GET, 'to_city', FILTER_SANITIZE_STRING);
    $search_date = filter_input(INPUT_GET, 'travel_date', FILTER_SANITIZE_STRING);

    if (empty($search_from) || empty($search_to) || empty($search_date)) {
        $message = "Please fill in all search fields.";
        $message_type = "warning";
    } else {
        $sql_search = "SELECT id, date, time, from_city, to_city, fare_amount, available_seats, total_seats FROM schedule WHERE mode = 'bus' AND from_city LIKE ? AND to_city LIKE ? AND date = ? AND available_seats > 0 ORDER BY time";
        $stmt_search = mysqli_prepare($conn, $sql_search);
        if ($stmt_search) {
            $param_from = '%' . $search_from . '%';
            $param_to = '%' . $search_to . '%';
            mysqli_stmt_bind_param($stmt_search, "sss", $param_from, $param_to, $search_date);
            mysqli_stmt_execute($stmt_search);
            $result_search = mysqli_stmt_get_result($stmt_search);
            while ($row = mysqli_fetch_assoc($result_search)) {
                $schedules[] = $row;
            }
            mysqli_stmt_close($stmt_search);

            if (empty($schedules)) {
                $message = "No bus schedules found for your search criteria.";
                $message_type = "info";
            }
        } else {
            $message = "Database search error: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}

// Retrieve messages from session if redirected
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Bus Ticket - Sair Karo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        /* Variables for easy color management (consistent with other pages) */
        :root {
            --primary-color: #D32F2F; /* A strong red */
            --secondary-color: #757575; /* Darker grey for contrast */
            --light-bg: #f8f9fa; /* Light background */
            --dark-text: #212121; /* Dark text */
            --info-color: #2196F3;
            --success-color: #4CAF50;
            --warning-color: #FFC107;
            --danger-color: #F44336;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-top: 70px; /* Adjust for fixed navbar */
        }

        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color);
        }
        .nav-link {
            font-weight: 500;
            color: var(--dark-text);
        }
        .nav-link:hover, .nav-link.active {
            color: var(--primary-color);
        }
        .container {
            margin-top: 30px;
            margin-bottom: 50px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden; /* Ensures rounded corners apply to all content */
        }
        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            padding: 1rem 1.5rem;
            border-bottom: none;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-label {
            font-weight: 500;
            color: var(--dark-text);
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 10px 15px;
            height: auto; /* Allow height to adjust */
        }
        .input-group-text {
            background-color: var(--primary-color);
            color: white;
            border: 1px solid var(--primary-color);
            border-right: none;
            border-radius: 8px 0 0 8px;
            padding: 0.75rem 1rem;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            border-radius: 8px;
            padding: 12px 25px;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #B71C1C;
            border-color: #B71C1C;
        }
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-color: #eee;
        }
        .list-group-item:last-child {
            border-bottom-left-radius: 15px;
            border-bottom-right-radius: 15px;
        }
        .fare-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        /* Styles for passenger details form */
        #passenger_details_form {
            display: <?= ($prefill_schedule_id && $prefill_available_seats > 0) ? 'block' : 'none'; ?>; /* Initially hidden */
            margin-top: 20px;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        /* Search Results Styles */
        .schedule-card {
            margin-bottom: 15px;
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .schedule-card .card-body {
            padding: 1rem;
        }
        .schedule-card h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 5px;
        }
        .schedule-card p {
            margin-bottom: 5px;
            font-size: 0.95rem;
        }
        .schedule-card .btn-select {
            background-color: var(--info-color);
            border-color: var(--info-color);
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .schedule-card .btn-select:hover {
            background-color: #1976D2;
            border-color: #1976D2;
        }

        .footer {
            background-color: #303843;
            color: #ffffff;
            padding: 40px 0;
            font-size: 0.9rem;
        }
        .footer h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 20px;
        }
        .footer ul {
            list-style: none;
            padding: 0;
        }
        .footer ul li {
            margin-bottom: 10px;
        }
        .footer ul li a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer ul li a:hover {
            color: var(--primary-color);
        }
        .footer .social-icons a {
            color: #ffffff;
            font-size: 1.5rem;
            margin-right: 15px;
            transition: color 0.3s ease;
        }
        .footer .social-icons a:hover {
            color: var(--primary-color);
        }
        .footer .copyright {
            border-top: 1px solid #4a4a4a;
            padding-top: 20px;
            margin-top: 30px;
            text-align: center;
            color: #cccccc;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="home.php">Sair Karo</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="check_schedule.php">Check Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="book_bus.php">Book Bus</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="book_plane.php">Book Plane</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="book_train.php">Book Train</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_bookings.php">My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="text-center mb-4">Book Your Bus Ticket</h2>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                Search Bus Schedules
            </div>
            <div class="card-body">
                <form action="book_bus.php" method="GET" id="searchBusForm">
                    <input type="hidden" name="search" value="1">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="from_city" class="form-label">From City</label>
                            <input type="text" class="form-control" id="from_city" name="from_city" placeholder="e.g., Pune" value="<?= htmlspecialchars($prefill_from_city); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="to_city" class="form-label">To City</label>
                            <input type="text" class="form-control" id="to_city" name="to_city" placeholder="e.g., Mumbai" value="<?= htmlspecialchars($prefill_to_city); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="travel_date" class="form-label">Travel Date</label>
                            <input type="date" class="form-control" id="travel_date" name="travel_date" value="<?= htmlspecialchars($prefill_travel_date); ?>" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($schedules)): ?>
        <div class="card mt-4">
            <div class="card-header">
                Available Bus Schedules
            </div>
            <div class="card-body">
                <?php foreach ($schedules as $schedule): ?>
                    <div class="card schedule-card mb-3" data-schedule-id="<?= $schedule['id']; ?>"
                         data-from="<?= htmlspecialchars($schedule['from_city']); ?>"
                         data-to="<?= htmlspecialchars($schedule['to_city']); ?>"
                         data-date="<?= htmlspecialchars($schedule['date']); ?>"
                         data-time="<?= htmlspecialchars($schedule['time']); ?>"
                         data-fare="<?= htmlspecialchars($schedule['fare_amount']); ?>"
                         data-available-seats="<?= htmlspecialchars($schedule['available_seats']); ?>">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h5><?= htmlspecialchars($schedule['from_city']); ?> to <?= htmlspecialchars($schedule['to_city']); ?></h5>
                                <p class="text-muted mb-1">Date: <?= htmlspecialchars($schedule['date']); ?> | Time: <?= date("H:i", strtotime($schedule['time'])); ?></p>
                                <p class="mb-0">Available Seats: <span class="badge bg-info"><?= htmlspecialchars($schedule['available_seats']); ?> / <?= htmlspecialchars($schedule['total_seats']); ?></span></p>
                            </div>
                            <div class="text-end">
                                <h4 class="fare-amount">₹<?= number_format($schedule['fare_amount'], 2); ?></h4>
                                <?php if ($schedule['available_seats'] > 0): ?>
                                    <button type="button" class="btn btn-select mt-2">Select & Book</button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-danger mt-2" disabled>Sold Out</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mt-4" id="passenger_details_form" style="display: <?= ($prefill_schedule_id && $prefill_available_seats > 0) ? 'block' : 'none'; ?>;">
            <div class="card-header">
                Passenger Details
            </div>
            <div class="card-body">
                <form action="book_bus.php" method="POST">
                    <input type="hidden" name="schedule_id" id="booking_schedule_id" value="<?= htmlspecialchars($prefill_schedule_id); ?>">
                    <input type="hidden" name="booking_date" id="booking_date" value="<?= htmlspecialchars($prefill_travel_date); ?>">
                    <input type="hidden" name="booking_time" id="booking_time" value="<?= htmlspecialchars($prefill_travel_time); ?>">
                    <input type="hidden" name="from_city" id="booking_from_city" value="<?= htmlspecialchars($prefill_from_city); ?>">
                    <input type="hidden" name="to_city" id="booking_to_city" value="<?= htmlspecialchars($prefill_to_city); ?>">
                    <input type="hidden" name="hidden_fare_input" id="hidden_fare_input" value="<?= htmlspecialchars($prefill_fare_amount); ?>">

                    <div class="mb-3">
                        <label for="selected_schedule_display" class="form-label">Selected Schedule</label>
                        <input type="text" class="form-control" id="selected_schedule_display" readonly
                            value="<?php
                                if ($prefill_schedule_id) {
                                    echo htmlspecialchars($prefill_from_city . ' to ' . $prefill_to_city . ' on ' . $prefill_travel_date . ' at ' . substr($prefill_travel_time, 0, 5));
                                }
                            ?>">
                    </div>

                    <div class="mb-3">
                        <label for="display_fare" class="form-label">Total Fare</label>
                        <input type="text" class="form-control" id="display_fare" readonly
                            value="<?php
                                if ($prefill_fare_amount) {
                                    echo '₹' . number_format($prefill_fare_amount, 2);
                                }
                            ?>">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Passenger Name</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Full Name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Passenger Age</label>
                            <input type="number" class="form-control" id="age" name="age" required placeholder="Age" min="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="seat" class="form-label">Seat Preference</label>
                            <select class="form-select" id="seat" name="seat" required>
                                <option value="">Select Seat Preference</option>
                                <option value="Window">Window</option>
                                <option value="Aisle">Aisle</option>
                                <option value="Any">Any</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" required placeholder="10-digit mobile number" pattern="[0-9]{10}" title="Please enter a 10-digit mobile number">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Net Banking">Net Banking</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="confirmBookingBtn"
                        <?= ($prefill_available_seats <= 0) ? 'disabled' : ''; ?>>
                        <?php if ($prefill_available_seats > 0): ?>
                            <i class="fas fa-check-circle me-2"></i>Confirm Booking
                        <?php else: ?>
                            Sold Out
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Sair Karo</h5>
                    <p>Your ultimate travel partner for seamless bookings.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="home.php">Home</a></li>
                        <li><a href="check_schedule.php">Check Schedule</a></li>
                        <li><a href="my_bookings.php">My Bookings</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-map-marker-alt me-2"></i> Pimpri-Chinchwad, Maharashtra, India</li>
                        <li><i class="fas fa-phone me-2"></i> +91 98765 43210</li>
                        <li><i class="fas fa-envelope me-2"></i> info@sairkaro.com</li>
                    </ul>
                </div>

                <div class="col-md-2">
                    <h5>Follow Us</h5>
                    <div class="social-icons">
                        <a href="#" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="copyright">
                        &copy; <?= date("Y") ?> Sair Karo. All Rights Reserved.
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Event listener for "Select & Book" buttons
            $('.schedule-card .btn-select').on('click', function() {
                var scheduleId = $(this).closest('.schedule-card').data('schedule-id');
                var fromCity = $(this).closest('.schedule-card').data('from');
                var toCity = $(this).closest('.schedule-card').data('to');
                var travelDate = $(this).closest('.schedule-card').data('date');
                var travelTime = $(this).closest('.schedule-card').data('time');
                var fareAmount = $(this).closest('.schedule-card').data('fare');
                var availableSeats = $(this).closest('.schedule-card').data('available-seats');

                // Populate hidden fields for form submission
                $('#booking_schedule_id').val(scheduleId);
                $('#booking_date').val(travelDate);
                $('#booking_time').val(travelTime);
                $('#booking_from_city').val(fromCity);
                $('#booking_to_city').val(toCity);
                $('#hidden_fare_input').val(fareAmount);

                // Display selected schedule info and fare
                $('#selected_schedule_display').val(fromCity + ' to ' + toCity + ' on ' + travelDate + ' at ' + travelTime.substring(0, 5));
                $('#display_fare').val(parseFloat(fareAmount).toFixed(2));

                // Show the passenger details form with a nice slide effect
                $('#passenger_details_form').slideDown();

                // Enable/disable the submit button based on available seats
                if (availableSeats > 0) {
                     $('#passenger_details_form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Confirm Booking');
                } else {
                     $('#passenger_details_form button[type="submit"]').prop('disabled', true).html('Sold Out');
                }

                // Scroll to the passenger details form
                $('html, body').animate({
                    scrollTop: $('#passenger_details_form').offset().top - 80 // Adjust offset as needed
                }, 800);
            });

            // Autoload schedules and select if parameters are present in URL
            var prefillScheduleId = <?= json_encode($prefill_schedule_id); ?>;
            var prefillFromCity = <?= json_encode($prefill_from_city); ?>;
            var prefillToCity = <?= json_encode($prefill_to_city); ?>;
            var prefillTravelDate = <?= json_encode($prefill_travel_date); ?>;
            var prefillTravelTime = <?= json_encode($prefill_travel_time); ?>;
            var prefillFareAmount = <?= json_encode($prefill_fare_amount); ?>;
            var prefillAvailableSeats = <?= json_encode($prefill_available_seats); ?>;

            if (prefillScheduleId) {
                // Populate hidden fields and display info for the passenger form
                $('#booking_schedule_id').val(prefillScheduleId);
                $('#booking_date').val(prefillTravelDate);
                $('#booking_time').val(prefillTravelTime);
                $('#booking_from_city').val(prefillFromCity);
                $('#booking_to_city').val(prefillToCity);
                $('#hidden_fare_input').val(prefillFareAmount);
                $('#selected_schedule_display').val(prefillFromCity + ' to ' + prefillToCity + ' on ' + prefillTravelDate + ' at ' + prefillTravelTime.substring(0, 5));
                $('#display_fare').val(parseFloat(prefillFareAmount).toFixed(2));

                // Show the passenger details form
                $('#passenger_details_form').slideDown();

                // Enable/disable the submit button based on available seats
                if (prefillAvailableSeats > 0) {
                     $('#passenger_details_form button[type="submit"]').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Confirm Booking');
                } else {
                     $('#passenger_details_form button[type="submit"]').prop('disabled', true).html('Sold Out');
                }

                // Scroll to the passenger details form
                $('html, body').animate({
                    scrollTop: $('#passenger_details_form').offset().top - 80
                }, 800);
            }
        });
    </script>
</body>

</html>
