<?php
// book_train.php
include 'include/db.php'; // Ensure this path is correct
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

$prefill_schedule_id = null;
$prefill_from_city = '';
$prefill_to_city = '';
$prefill_travel_date = '';
$prefill_travel_time = '';
$prefill_fare_amount = '';
$prefill_coach_class = ''; // Added for train
$prefill_available_seats = 0; // Initialize for prefill

// Handle direct access via GET with schedule ID (e.g., from check_schedule.php)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id']) && isset($_GET['mode']) && $_GET['mode'] == 'train') {
    $schedule_id_from_get = intval($_GET['id']);
    
    // Fetch schedule details for pre-population
    $stmt_pre_schedule = mysqli_prepare($conn, "SELECT date, time, from_city, to_city, fare_amount, coach_class, available_seats FROM schedule WHERE id = ? AND mode = 'train' LIMIT 1");
    if ($stmt_pre_schedule) {
        mysqli_stmt_bind_param($stmt_pre_schedule, "i", $schedule_id_from_get);
        mysqli_stmt_execute($stmt_pre_schedule);
        $result_pre_schedule = mysqli_stmt_get_result(mysqli_stmt_execute($stmt_pre_schedule) ? $stmt_pre_schedule : null); // Check for execution success before getting result
        if ($result_pre_schedule && mysqli_num_rows($result_pre_schedule) > 0) {
            $schedule_data = mysqli_fetch_assoc($result_pre_schedule);
            $prefill_schedule_id = $schedule_id_from_get;
            $prefill_from_city = $schedule_data['from_city'];
            $prefill_to_city = $schedule_data['to_city'];
            $prefill_travel_date = $schedule_data['date'];
            $prefill_travel_time = $schedule_data['time'];
            $prefill_fare_amount = $schedule_data['fare_amount'];
            $prefill_coach_class = $schedule_data['coach_class'];
            $prefill_available_seats = $schedule_data['available_seats'];
        }
        mysqli_stmt_close($stmt_pre_schedule);
    }
}


// --- Handle POST Request for Booking Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];

    // Sanitize and validate inputs
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    $passenger_name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $passenger_age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $seat_preference = filter_input(INPUT_POST, 'seat', FILTER_SANITIZE_STRING); // This is seat_preference from form
    $mobile_number = filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_STRING);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $fare_amount = filter_input(INPUT_POST, 'fare_amount', FILTER_VALIDATE_FLOAT); // Use hidden fare input
    $coach_class_booked = filter_input(INPUT_POST, 'coach_class_booked', FILTER_SANITIZE_STRING); // Get coach class from hidden input

    // Fetch *full* schedule details again to get from_city, to_city, date for saving
    $stmt_schedule = mysqli_prepare($conn, "SELECT from_city, to_city, date, time, available_seats, coach_class FROM schedule WHERE id = ? AND mode = 'train' LIMIT 1");
    if (!$stmt_schedule) {
        $message = "Database error: " . mysqli_error($conn);
        $message_type = "danger";
    } else {
        mysqli_stmt_bind_param($stmt_schedule, "i", $schedule_id);
        if (mysqli_stmt_execute($stmt_schedule)) { // Execute and check for success
            $result_schedule = mysqli_stmt_get_result($stmt_schedule);
            $schedule_data = mysqli_fetch_assoc($result_schedule);
            mysqli_stmt_close($stmt_schedule);

            if (!$schedule_data) {
                $message = "Invalid schedule selected.";
                $message_type = "danger";
            } elseif ($schedule_data['available_seats'] <= 0) {
                $message = "No seats available for this train.";
                $message_type = "danger";
            } elseif (empty($schedule_data['date'])) { // ADDED THIS NEW CHECK
                $message = "Error: Travel date for the selected train is missing in our records. Please select another schedule or contact support.";
                $message_type = "danger";
            }
            else {
                // Get data from schedule for direct saving
                $from_city_to_save = $schedule_data['from_city'];
                $to_city_to_save = $schedule_data['to_city'];
                $travel_date_to_save = $schedule_data['date'];
                $coach_class_to_save = $schedule_data['coach_class']; // Use coach_class from schedule table

                // Generate a simple PNR (You might want a more robust generation logic)
                $pnr_number = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

                // Generate a random seat number (simple example, you'd need a more complex seat management)
                $seat_number = rand(1, 60); // Assuming 60 seats per train for simplicity

                // UPDATED INSERT QUERY: Now includes from_city, to_city, travel_date, and coach_class
                $columns = "user_id, schedule_id, pnr_number, name, age, mobile, seat_number, seat_preference, fare_amount, payment_method, from_city, to_city, travel_date, coach_class, status, created_at";
                $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', NOW()";
                // CORRECTED types string for the 14 parameters: iisissdsssssss
                // user_id(i), schedule_id(i), pnr_number(s), name(s), age(i), mobile(s), seat_number(s), seat_preference(s), fare_amount(d), payment_method(s), from_city(s), to_city(s), travel_date(s), coach_class(s)
                $types = "iisissdsssssss"; 

                $stmt_insert = mysqli_prepare($conn, "INSERT INTO train_bookings ({$columns}) VALUES ({$placeholders})");

                if ($stmt_insert) {
                    // MODIFIED BIND PARAM: Now includes from_city_to_save, to_city_to_save, travel_date_to_save, and coach_class_to_save
                    mysqli_stmt_bind_param($stmt_insert, $types, $user_id, $schedule_id, $pnr_number, $passenger_name, $passenger_age, $mobile_number, $seat_number, $seat_preference, $fare_amount, $payment_method, $from_city_to_save, $to_city_to_save, $travel_date_to_save, $coach_class_to_save);

                    if (mysqli_stmt_execute($stmt_insert)) {
                        // Update available seats in schedule
                        $stmt_update_seats = mysqli_prepare($conn, "UPDATE schedule SET available_seats = available_seats - 1 WHERE id = ?");
                        mysqli_stmt_bind_param($stmt_update_seats, "i", $schedule_id);
                        mysqli_stmt_execute($stmt_update_seats);
                        mysqli_stmt_close($stmt_update_seats);

                        $message = "Train ticket booked successfully! PNR: " . $pnr_number;
                        $message_type = "success";

                        // Clear form data after successful booking
                        $_POST = array();

                    } else {
                        $message = "Error booking ticket: " . mysqli_error($conn);
                        $message_type = "danger";
                    }
                    mysqli_stmt_close($stmt_insert);
                } else {
                    $message = "Error preparing booking statement: " . mysqli_error($conn);
                    $message_type = "danger";
                }
            }
        } else {
            $message = "Error executing schedule query: " . mysqli_stmt_error($stmt_schedule);
            $message_type = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Book Train Ticket - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        :root {
            --primary-color: #dc3545; /* Red, matching your image */
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --white-color: #ffffff;
            --accent-color: #ffc107; /* Orange/yellow for highlights */
            --info-light: #e0f2fe;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e0e9f0 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 70px; /* Space for fixed navbar */
        }

        /* Navbar Styling */
        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--white-color) !important;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }
        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .navbar-brand:hover {
            color: var(--accent-color) !important;
        }

        .nav-link {
            color: var(--white-color) !important;
            font-weight: 500;
            margin-right: 15px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--accent-color) !important;
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }

        .container-main {
            flex: 1;
            margin-top: 40px;
            padding-bottom: 60px;
        }

        .page-title {
            color: var(--primary-color); /* Matches button/navbar color */
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            font-size: 2.8rem;
            position: relative;
            padding-bottom: 15px;
        }
        .page-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 120px;
            height: 5px;
            background-color: var(--accent-color);
            border-radius: 3px;
        }

        /* Search Form Styling */
        .search-card {
            background-color: var(--white-color);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 40px;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-text);
        }

        .form-control, .btn {
            border-radius: 10px;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white-color);
            font-weight: 600;
            padding: 12px 25px;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.2s ease;
        }
        .btn-primary-custom:hover {
            background-color: #c82333; /* Darker red on hover */
            border-color: #c82333;
            transform: translateY(-2px);
        }

        /* Schedule Results Styling */
        .schedule-card {
            background-color: var(--white-color);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .schedule-card .card-header {
            background-color: var(--primary-color);
            color: var(--white-color);
            font-weight: 600;
            padding: 15px 20px;
            font-size: 1.1rem;
        }

        .schedule-card .card-body {
            padding: 20px;
        }

        .schedule-detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .schedule-detail-item strong {
            color: var(--dark-text);
        }
        .schedule-detail-item span {
            color: var(--secondary-color);
        }

        .fare-amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: right;
        }

        .btn-select-schedule {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--dark-text);
            font-weight: 600;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
        }
        .btn-select-schedule:hover {
            background-color: #e0a800; /* Darker accent on hover */
            border-color: #e0a800;
            color: var(--dark-text);
        }

        /* Passenger Details Form */
        #passenger_details_form {
            display: none; /* Hidden by default */
            background-color: var(--white-color);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 40px;
        }

        .alert-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 500;
        }
        .alert-success-custom {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-danger-custom {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Footer */
        footer {
            background-color: var(--dark-text); /* Using dark-text for footer background */
            color: var(--white-color);
            padding: 25px 0;
            text-align: center;
            margin-top: auto;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        footer a {
            color: var(--white-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        footer a:hover {
            color: var(--accent-color);
        }
        .social-icons a {
            color: var(--white-color);
            font-size: 1.5rem;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        .social-icons a:hover {
            color: var(--accent-color);
        }
        .copyright {
            margin-top: 20px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .page-title {
                font-size: 2.2rem;
            }
            .navbar-brand {
                font-size: 1.5rem;
            }
            .navbar-brand img {
                height: 35px;
            }
            .navbar-nav {
                text-align: center;
                padding-top: 10px;
            }
            .nav-link {
                margin-right: 0;
                margin-bottom: 5px;
            }
        }
        @media (max-width: 575.98px) {
            .page-title {
                font-size: 1.8rem;
                margin-bottom: 30px;
            }
            .page-title::after {
                width: 80px;
                height: 3px;
            }
            .search-card, .schedule-card, #passenger_details_form {
                padding: 20px;
            }
            .btn-primary-custom, .btn-select-schedule {
                width: 100%;
                margin-top: 10px;
            }
            .fare-amount {
                text-align: left;
                margin-top: 15px;
            }
            .schedule-detail-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .schedule-detail-item strong {
                margin-bottom: 3px;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand" href="home.php">
                    <img src="assets/images/logo.png" alt="Sair Karo Logo">
                    Sair Karo
                </a>
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
                            <a class="nav-link" href="about.php">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact.php">Contact Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_bookings.php">My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="container container-main">
        <h1 class="page-title">Book Train Ticket</h1>

        <?php if (!empty($message)): ?>
            <div class="alert alert-message <?= $message_type === 'success' ? 'alert-success-custom' : 'alert-danger-custom' ?>" role="alert">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="search-card">
            <h2 class="h4 mb-4 text-center">Find Your Train</h2>
            <form id="searchTrainForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="from_city" class="form-label">From City</label>
                        <input type="text" class="form-control" id="from_city" name="from_city" placeholder="e.g., Mumbai" value="<?= htmlspecialchars($prefill_from_city) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="to_city" class="form-label">To City</label>
                        <input type="text" class="form-control" id="to_city" name="to_city" placeholder="e.g., Delhi" value="<?= htmlspecialchars($prefill_to_city) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="travel_date" class="form-label">Travel Date</label>
                        <input type="date" class="form-control" id="travel_date" name="travel_date" value="<?= htmlspecialchars($prefill_travel_date) ?>" required>
                    </div>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary-custom"><i class="fas fa-search me-2"></i> Search Trains</button>
                </div>
            </form>
        </div>

        <div id="schedule_results">
            </div>

        <div id="passenger_details_form">
            <h2 class="h4 mb-4 text-center">Passenger Details</h2>
            <form action="book_train.php" method="POST">
                <input type="hidden" name="schedule_id" id="booking_schedule_id" value="<?= htmlspecialchars($prefill_schedule_id) ?>">
                <input type="hidden" name="fare_amount" id="hidden_fare_input" value="<?= htmlspecialchars($prefill_fare_amount) ?>">
                <input type="hidden" name="coach_class_booked" id="booking_coach_class" value="<?= htmlspecialchars($prefill_coach_class) ?>">

                <div class="mb-3">
                    <label for="selected_schedule_display" class="form-label">Selected Train</label>
                    <input type="text" class="form-control" id="selected_schedule_display" readonly value="">
                </div>

                <div class="mb-3">
                    <label for="display_fare" class="form-label">Fare Amount</label>
                    <input type="text" class="form-control" id="display_fare" readonly value="">
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Passenger Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="age" class="form-label">Age</label>
                        <input type="number" class="form-control" id="age" name="age" placeholder="Age" min="1" max="120" required>
                    </div>
                    <div class="col-md-6">
                        <label for="mobile" class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" id="mobile" name="mobile" placeholder="10-digit Mobile" pattern="[0-9]{10}" title="Please enter a 10-digit mobile number" required>
                    </div>
                    <div class="col-md-6">
                        <label for="seat" class="form-label">Seat Preference</label>
                        <select class="form-select" id="seat" name="seat" required>
                            <option value="">Select Preference</option>
                            <option value="Window">Window</option>
                            <option value="Aisle">Aisle</option>
                            <option value="Any">Any</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Net Banking">Net Banking</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary-custom" disabled><i class="fas fa-check-circle me-2"></i>Confirm Booking</button>
                </div>
            </form>
        </div>

    </main>

    <footer class="mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Sair Karo</h5>
                    <p>
                        Sair Karo is your one-stop solution for hassle-free online
                        bus, train, and plane ticket booking. We aim to provide a
                        seamless and comfortable travel experience.
                    </p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="home.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
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
            // Function to load schedules via AJAX
            function loadSchedules(fromCity, toCity, travelDate) {
                $.ajax({
                    url: 'fetch_schedules.php', // You'll need to create this file
                    type: 'GET',
                    data: {
                        mode: 'train',
                        from_city: fromCity,
                        to_city: toCity,
                        travel_date: travelDate
                    },
                    success: function(response) {
                        $('#schedule_results').html(response);
                        attachSelectScheduleHandlers();
                    },
                    error: function(xhr, status, error) {
                        $('#schedule_results').html('<div class="alert alert-danger mt-4" role="alert">Error loading schedules: ' + xhr.responseText + '</div>');
                    }
                });
            }

            // Attach event handler to the search form
            $('#searchTrainForm').submit(function(e) {
                e.preventDefault(); // Prevent default form submission

                var fromCity = $('#from_city').val();
                var toCity = $('#to_city').val();
                var travelDate = $('#travel_date').val();

                if (fromCity && toCity && travelDate) {
                    loadSchedules(fromCity, toCity, travelDate);
                    $('#passenger_details_form').slideUp(); // Hide passenger form on new search
                } else {
                    alert('Please fill in all search fields.');
                }
            });

            // Function to attach click handlers to "Select Schedule" buttons
            function attachSelectScheduleHandlers() {
                $('.btn-select-schedule').off('click').on('click', function() {
                    var scheduleId = $(this).data('id');
                    var fromCity = $(this).data('from');
                    var toCity = $(this).data('to');
                    var travelDate = $(this).data('date');
                    var travelTime = $(this).data('time');
                    var coachClass = $(this).data('class'); // Get coach class from data attribute
                    var fareAmount = $(this).data('fare');
                    var availableSeats = $(this).data('available-seats');

                    // Populate hidden fields for form submission
                    $('#booking_schedule_id').val(scheduleId);
                    $('#booking_date').val(travelDate);
                    $('#booking_time').val(travelTime);
                    $('#booking_from_city').val(fromCity);
                    $('#booking_to_city').val(toCity);
                    $('#hidden_fare_input').val(fareAmount);
                    $('#booking_coach_class').val(coachClass); // Set coach_class in hidden field

                    // Display selected schedule info and fare
                    $('#selected_schedule_display').val(fromCity + ' to ' + toCity + ' on ' + travelDate + ' at ' + travelTime.substring(0, 5) + ' (Class: ' + coachClass + ')');
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
            }

            // Autoload schedules and select if parameters are present in URL
            var prefillScheduleId = <?= json_encode($prefill_schedule_id); ?>;
            var prefillFromCity = <?= json_encode($prefill_from_city); ?>;
            var prefillToCity = <?= json_encode($prefill_to_city); ?>;
            var prefillTravelDate = <?= json_encode($prefill_travel_date); ?>;
            var prefillTravelTime = <?= json_encode($prefill_travel_time); ?>;
            var prefillFareAmount = <?= json_encode($prefill_fare_amount); ?>;
            var prefillCoachClass = <?= json_encode($prefill_coach_class); ?>;
            var prefillAvailableSeats = <?= json_encode($prefill_available_seats); ?>;


            if (prefillScheduleId) {
                // Populate hidden fields and display info for the passenger form
                $('#booking_schedule_id').val(prefillScheduleId);
                $('#booking_date').val(prefillTravelDate);
                $('#booking_time').val(prefillTravelTime);
                $('#booking_from_city').val(prefillFromCity);
                $('#booking_to_city').val(prefillToCity);
                $('#hidden_fare_input').val(prefillFareAmount);
                $('#booking_coach_class').val(prefillCoachClass); // Set coach_class in hidden field
                $('#selected_schedule_display').val(prefillFromCity + ' to ' + prefillToCity + ' on ' + prefillTravelDate + ' at ' + prefillTravelTime.substring(0, 5) + ' (Class: ' + prefillCoachClass + ')');
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

                // Also load and display the schedule results for context
                loadSchedules(prefillFromCity, prefillToCity, prefillTravelDate);
            }
        });
    </script>
</body>
</html>