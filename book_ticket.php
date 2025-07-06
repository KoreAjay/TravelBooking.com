<?php // book_ticket.php
include 'include/db.php';
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


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- Booking Submission Logic ---
    $user_id = $_SESSION['user_id'];
    $schedule_id = intval($_POST['schedule_id']); // Ensure integer
    $seat_pref = trim($_POST['seat']);
    $name = trim($_POST['name']);
    $age = (int) $_POST['age'];
    $mobile = trim($_POST['mobile']);
    $payment = trim($_POST['payment']);

    // Validate inputs
    if (empty($schedule_id) || empty($name) || empty($mobile) || empty($payment) || $age <= 0) {
        echo "<script>alert('Please fill in all passenger details correctly.'); window.history.back();</script>";
        exit();
    }

    // Use a transaction for atomic operations
    mysqli_begin_transaction($conn);

    try {
        // Fetch schedule details using schedule_id to get actual date, time, from_city, to_city, fare_amount,
        // available_seats, and total_seats for seat number generation and accurate booking.
        // Using prepared statement for security
        $stmt_schedule = mysqli_prepare($conn, "SELECT date, time, from_city, to_city, fare_amount, available_seats, total_seats FROM schedule WHERE id = ? AND mode = 'bus' LIMIT 1");
        if (!$stmt_schedule) {
            throw new Exception("Error preparing schedule query: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_schedule, "i", $schedule_id);
        mysqli_stmt_execute($stmt_schedule);
        $result_schedule_details = mysqli_stmt_get_result($stmt_schedule);

        if (mysqli_num_rows($result_schedule_details) == 0) {
            throw new Exception("Selected schedule not found or not a bus schedule.");
        }
        $schedule_data = mysqli_fetch_assoc($result_schedule_details);
        mysqli_stmt_close($stmt_schedule);

        $booking_date_from_schedule = $schedule_data['date'];
        $booking_time_from_schedule = $schedule_data['time'];
        $from_city_booking = $schedule_data['from_city'];
        $to_city_booking = $schedule_data['to_city'];
        $fare_amount_booking = $schedule_data['fare_amount'];
        $available_seats = $schedule_data['available_seats'];
        $total_seats = $schedule_data['total_seats'];

        if ($available_seats <= 0) {
            throw new Exception("No seats available for this schedule.");
        }

        // Generate PNR number (example: BUS + 5 random alphanumeric chars + last 4 digits of timestamp)
        $pnr_number = 'BUS' . substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5) . substr(time(), -4);

        // Determine the actual seat number. This is a very basic sequential assignment.
        // In a real system, you'd need a more robust seat allocation mechanism (e.g., specific seat maps, occupied seats).
        $selected_seat_number = $total_seats - $available_seats + 1; // Assigning the next available logical seat

        // Insert booking into bus_bookings table
        // Using prepared statement for insertion
        $insert_booking_sql = "INSERT INTO bus_bookings (user_id, schedule_id, pnr_number, name, age, mobile, seat_number, payment_method, booking_date, booking_time, booking_from, booking_to, fare_paid, booking_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), CURTIME(), ?, ?, ?, 'Confirmed')";
        $stmt_insert_booking = mysqli_prepare($conn, $insert_booking_sql);
        if (!$stmt_insert_booking) {
            throw new Exception("Error preparing booking insert: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_insert_booking, "iisississd", // CORRECTED type string
            $user_id,
            $schedule_id,
            $pnr_number,
            $name,
            $age,
            $mobile,
            $selected_seat_number, // This is an integer
            $payment,
            $from_city_booking,
            $to_city_booking,
            $fare_amount_booking
        );

        if (!mysqli_stmt_execute($stmt_insert_booking)) {
            throw new Exception("Error creating booking: " . mysqli_stmt_error($stmt_insert_booking));
        }
        mysqli_stmt_close($stmt_insert_booking);

        // Update available seats in the schedule table
        // Using prepared statement for update
        $update_seats_sql = "UPDATE schedule SET available_seats = available_seats - 1 WHERE id = ?";
        $stmt_update_seats = mysqli_prepare($conn, $update_seats_sql);
        if (!$stmt_update_seats) {
            throw new Exception("Error preparing seat update: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt_update_seats, "i", $schedule_id);
        if (!mysqli_stmt_execute($stmt_update_seats)) {
            throw new Exception("Error updating available seats: " . mysqli_stmt_error($stmt_update_seats));
        }
        mysqli_stmt_close($stmt_update_seats);

        // Commit transaction
        mysqli_commit($conn);

        echo "<script>alert('Booking successful! Your PNR is: " . htmlspecialchars($pnr_number) . "'); window.location.href = 'user_dashboard.php?tab=my-bookings';</script>";
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        echo "<script>alert('Booking failed: " . htmlspecialchars($e->getMessage()) . "'); window.history.back();</script>";
        exit();
    }
}

// Fetch cities for search filters
$from_cities = [];
$to_cities = [];

$cities_query = "SELECT DISTINCT from_city FROM schedule WHERE mode = 'bus' UNION SELECT DISTINCT to_city FROM schedule WHERE mode = 'bus'";
$cities_result = mysqli_query($conn, $cities_query);
if ($cities_result) {
    while ($row = mysqli_fetch_assoc($cities_result)) {
        if (!empty($row['from_city'])) {
            $from_cities[] = $row['from_city'];
        }
    }
    // Sort cities alphabetically
    sort($from_cities);
    $to_cities = $from_cities; // Assuming same cities for 'from' and 'to'
} else {
    // Handle error or log it
    error_log("Error fetching cities: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Bus Ticket - Sair Karo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f0f2f5; }
        .navbar { background-color: #ffffff; border-bottom: 1px solid #e9ecef; }
        .footer { background-color: #212529; color: white; padding: 20px 0; text-align: center; }
        .booking-container { background-color: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-top: 30px; }
        .form-label { font-weight: bold; }
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .schedule-table th, .schedule-table td { padding: 12px 15px; border: 1px solid #dee2e6; text-align: left; }
        .schedule-table th { background-color: #007bff; color: white; }
        .schedule-table tbody tr:nth-child(even) { background-color: #f2f2f2; }
        .schedule-table tbody tr:hover { background-color: #e9ecef; cursor: pointer; }
        .selected-schedule { background-color: #d1ecf1 !important; border-color: #bee5eb; }
        .btn-search, .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-search:hover, .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
        .alert-info { background-color: #e0f7fa; border-color: #b2ebf2; color: #007bff; }
        .modal-body .form-control[readonly] { background-color: #e9ecef; }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>

    <div class="container booking-container">
        <h2 class="mb-4 text-center">Book Your Bus Ticket</h2>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-search me-2"></i>Find Your Bus
            </div>
            <div class="card-body">
                <form id="searchBusForm">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="from_city" class="form-label">From City</label>
                            <select class="form-select" id="from_city" name="from_city" required>
                                <option value="">Select Origin</option>
                                <?php foreach ($from_cities as $city): ?>
                                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="to_city" class="form-label">To City</label>
                            <select class="form-select" id="to_city" name="to_city" required>
                                <option value="">Select Destination</option>
                                <?php foreach ($to_cities as $city): ?>
                                    <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="travel_date" class="form-label">Travel Date</label>
                            <input type="date" class="form-control" id="travel_date" name="travel_date" required min="<?= date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-search"><i class="fas fa-bus me-2"></i>Search Buses</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="searchResults" class="card shadow-sm" style="display:none;">
            <div class="card-header bg-success text-white">
                <i class="fas fa-route me-2"></i>Available Buses
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped schedule-table">
                        <thead>
                            <tr>
                                <th>Bus ID</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Fare (₹)</th>
                                <th>Available Seats</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="passenger_details_form" class="card shadow-sm mt-4" style="display:none;">
            <div class="card-header bg-info text-white">
                <i class="fas fa-user-friends me-2"></i>Passenger Details
            </div>
            <div class="card-body">
                <form id="bookingForm" method="POST" action="book_bus.php">
                    <input type="hidden" name="schedule_id" id="booking_schedule_id">
                    <input type="hidden" name="booking_date" id="booking_date">
                    <input type="hidden" name="booking_time" id="booking_time">
                    <input type="hidden" name="booking_from_city" id="booking_from_city">
                    <input type="hidden" name="booking_to_city" id="booking_to_city">
                    <input type="hidden" name="hidden_fare_input" id="hidden_fare_input">


                    <div class="mb-3">
                        <label for="selected_schedule_display" class="form-label">Selected Schedule</label>
                        <input type="text" class="form-control" id="selected_schedule_display" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">Passenger Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" title="Please enter a 10-digit mobile number" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="seat" class="form-label">Seat Preference (e.g., Window, Aisle, Any)</label>
                        <input type="text" class="form-control" id="seat" name="seat" placeholder="e.g., Window, Aisle, Any">
                    </div>
                    <div class="mb-3">
                        <label for="payment" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment" name="payment" required>
                            <option value="">Select Payment Method</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Debit Card">Debit Card</option>
                            <option value="Net Banking">Net Banking</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="display_fare" class="form-label">Total Fare</label>
                        <input type="text" class="form-control" id="display_fare" readonly>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check-circle me-2"></i>Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'include/footer.php'; ?>

    <script>
    $(document).ready(function() {
        // Handle search form submission
        $('#searchBusForm').on('submit', function(e) {
            e.preventDefault();
            var fromCity = $('#from_city').val();
            var toCity = $('#to_city').val();
            var travelDate = $('#travel_date').val();

            if (fromCity && toCity && travelDate) {
                $.ajax({
                    url: 'fetch_schedules.php', // This file will handle fetching schedules based on criteria
                    type: 'GET',
                    data: {
                        mode: 'bus',
                        from_city: fromCity,
                        to_city: toCity,
                        travel_date: travelDate
                    },
                    success: function(response) {
                        $('#scheduleTableBody').html(response);
                        $('#searchResults').slideDown(); // Show results section
                        $('#passenger_details_form').slideUp(); // Hide booking form until schedule is selected
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Error: " + status + error);
                        $('#scheduleTableBody').html('<tr><td colspan="8" class="text-center text-danger">Error loading schedules. Please try again.</td></tr>');
                        $('#searchResults').slideDown();
                    }
                });
            } else {
                alert('Please select From City, To City, and Travel Date.');
            }
        });

        // Handle selection of a schedule from the displayed list
        $(document).on('click', '.select-schedule-btn', function() {
            $('.schedule-table tbody tr').removeClass('selected-schedule');
            $(this).closest('tr').addClass('selected-schedule');

            var scheduleId = $(this).data('id');
            var fromCity = $(this).data('from');
            var toCity = $(this).data('to');
            var travelDate = $(this).data('date');
            var travelTime = $(this).data('time');
            var fareAmount = $(this).data('fare');

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

            // Show the passenger details form
            $('#passenger_details_form').slideDown();

            // Scroll to the passenger details form
            $('html, body').animate({
                scrollTop: $('#passenger_details_form').offset().top - 80 // Adjust offset as needed
            }, 800);
        });
    });
    </script>
</body>
</html>