<?php
include 'include/db.php'; // Your database connection
session_start();

// Enable error reporting for debugging (REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$search_results = [];
$mode = $_GET['mode'] ?? ''; // bus, train, or flight (from home.php)
$from_city = $_GET['from_city'] ?? '';
$to_city = $_GET['to_city'] ?? '';
$date = $_GET['date'] ?? ''; // Y-m-d format from Flatpickr
$women_booking = isset($_GET['women_booking']) ? true : false; // Only for bus - not yet implemented in DB logic

// Validate and sanitize inputs
$mode = mysqli_real_escape_string($conn, $mode);
$from_city = mysqli_real_escape_string($conn, $from_city);
$to_city = mysqli_real_escape_string($conn, $to_city);
$date = mysqli_real_escape_string($conn, $date);

// Basic validation
if (empty($mode) || empty($from_city) || empty($to_city) || empty($date)) {
    $_SESSION['error_message'] = "Please provide all required search parameters to view results.";
    header("Location: home.php"); // Redirect back to home with an error
    exit();
}

// Map frontend mode to database mode if they differ (e.g., 'flight' to 'plane')
$db_mode = $mode;
if ($mode === 'flight') {
    $db_mode = 'plane';
}

// Build the query based on mode
// Using LOWER() on the 'mode' column from your table for case-insensitive matching
$sql = "SELECT * FROM schedule WHERE LOWER(mode) = LOWER('$db_mode') AND from_city = '$from_city' AND to_city = '$to_city' AND date = '$date'";

// Add specific conditions if needed (e.g., for women_booking for bus)
// As mentioned, your schedule table doesn't have a specific column for 'women_booking'.
// If you implement this feature, you'd need a column like 'women_friendly' BOOLEAN.
/*
if ($mode === 'bus' && $women_booking) {
    // Example: $sql .= " AND women_friendly = 1";
    // For now, it searches all buses. Logic for women-specific seats might be on booking page.
}
*/

$result = mysqli_query($conn, $sql);

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $search_results[] = $row;
        }
    } else {
        $_SESSION['info_message'] = "No " . ucfirst($mode) . "s found for this route and date.";
    }
} else {
    // Log the actual database error for debugging purposes
    error_log("Search results query error: " . mysqli_error($conn));
    $_SESSION['error_message'] = "An error occurred while fetching search results. Please try again later. (Error: " . mysqli_error($conn) . ")";
}

// Function to format the date for display
function formatDateForDisplay($dateString) {
    // Check if the date string is valid before formatting
    if (strtotime($dateString)) {
        return date("F j, Y", strtotime($dateString));
    }
    return "Invalid Date";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --white-color: #ffffff;
            --accent-color: #ffc107;
            --footer-bg: #212529;
            --footer-text: #adb5bd;
            --heading-font: 'Poppins', sans-serif;
            --body-font: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--body-font);
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-top: 70px;
        }

        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            background-color: var(--primary-color) !important;
        }
        .navbar-brand { font-weight: 700; font-size: 1.8rem; display: flex; align-items: center; color: var(--white-color) !important; }
        .navbar-brand:hover { color: var(--accent-color) !important; }
        .navbar-brand img { height: 40px; margin-right: 10px; }
        .nav-link { font-weight: 500; color: var(--white-color) !important; margin: 0 10px; transition: color 0.3s ease; }
        .nav-link:hover, .nav-link.active { color: var(--accent-color) !important; }
        .navbar-toggler-icon { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3csvg%3e"); }


        .results-container {
            margin-top: 30px;
            margin-bottom: 50px;
            background: var(--white-color);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .results-container h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }
        .results-container h3::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 70px;
            height: 4px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .result-card {
            background-color: #fcfcfc;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .result-card .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px dashed #e0e0e0;
        }
        .result-card .header .mode-icon { /* Changed from type-icon to mode-icon */
            font-size: 1.8rem;
            color: var(--primary-color);
            margin-right: 10px;
        }
        .result-card .header h5 {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            color: var(--dark-text);
        }
        .result-card .header .fare {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .result-card .details {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .result-card .details .info-block {
            text-align: center;
            flex: 1;
        }

        .result-card .details .info-block h6 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 5px;
        }
        .result-card .details .info-block p {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 0;
        }
        .result-card .details .arrow {
            font-size: 1.5rem;
            color: var(--secondary-color);
            margin: 0 15px;
            align-self: center; /* Center vertically within flex container */
        }

        .result-card .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #e0e0e0;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            gap: 10px; /* Space between items if wrapped */
        }

        .result-card .footer-info span {
            font-size: 0.9rem;
            color: var(--secondary-color);
            font-weight: 500;
        }
        .result-card .footer-info span i {
            margin-right: 5px;
            color: var(--primary-color);
        }

        .result-card .book-btn {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            padding: 10px 25px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
            width: 100%; /* Full width on small screens */
            margin-top: 15px; /* Space from other info */
        }
        .result-card .book-btn:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .alert {
            border-radius: 10px;
            font-weight: 500;
            padding: 15px 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
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

        /* Footer Styling (copied from home.php) */
        .main-footer {
            background: var(--footer-bg);
            color: var(--footer-text);
            padding: 60px 0 30px;
            margin-top: 80px;
            font-size: 0.95rem;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08);
        }

        .main-footer h5 {
            color: var(--white-color);
            font-weight: 600;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }

        .main-footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .main-footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .main-footer ul li {
            margin-bottom: 10px;
        }

        .main-footer ul li a {
            color: var(--footer-text);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .main-footer ul li a:hover {
            color: var(--accent-color);
        }

        .main-footer .social-icons a {
            color: var(--white-color);
            font-size: 1.5rem;
            margin-right: 15px;
            transition: color 0.3s ease, transform 0.3s ease;
        }

        .main-footer .social-icons a:hover {
            color: var(--accent-color);
            transform: translateY(-3px);
        }

        .main-footer .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            color: var(--footer-text);
            font-size: 0.85rem;
        }

        @media (max-width: 767.98px) {
            body { padding-top: 60px; }
            .results-container { margin-top: 20px; padding: 20px; }
            .results-container h3 { font-size: 1.8rem; margin-bottom: 20px; }
            .result-card .header { flex-direction: column; align-items: flex-start; }
            .result-card .header .mode-icon { margin-bottom: 10px; } /* Changed from type-icon to mode-icon */
            .result-card .header h5 { text-align: left; width: 100%; }
            .result-card .header .fare { margin-top: 10px; width: 100%; text-align: left; }
            .result-card .details { flex-direction: column; align-items: stretch; }
            .result-card .details .info-block { margin-bottom: 15px; text-align: left; }
            .result-card .details .arrow { display: none; } /* Hide arrow on small screens */
            .result-card .footer-info { flex-direction: column; align-items: flex-start; }
            .result-card .book-btn { width: 100%; }

            .main-footer { padding: 40px 0 20px; margin-top: 50px; }
            .main-footer h5 { margin-bottom: 15px; }
            .main-footer .social-icons { text-align: center; margin-top: 20px; }
            .main-footer .social-icons a { margin: 0 10px; }
            .main-footer .copyright { margin-top: 20px; padding-top: 15px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top px-md-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="home.php">
            <img src="assets/images/logo.png" alt="Sair Karo Logo"> Sair Karo
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="check_schedule.php">Check Schedules</a></li> <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-2 px-3 mt-2 mt-lg-0" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-warning ms-lg-2 px-3 text-dark mt-2 mt-lg-0" href="signup.php">Signup</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-2 px-3 mt-2 mt-lg-0" href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container results-container">
    <h3>Search Results for <?= ucfirst(htmlspecialchars($mode)) ?> from <?= htmlspecialchars($from_city) ?> to <?= htmlspecialchars($to_city) ?> on <?= formatDateForDisplay($date) ?></h3>

    <?php
    // Display messages from session if redirected from home.php or set on this page
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']);
    }
    if (isset($_SESSION['info_message'])) {
        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['info_message']) . '</div>';
        unset($_SESSION['info_message']);
    }
    if (isset($_SESSION['success_message'])) { // Added for completeness, though success might be rare here
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    ?>

    <?php if (!empty($search_results)): ?>
        <div class="row">
            <?php foreach ($search_results as $schedule):
                // Determine the correct booking page based on the mode
                $booking_page = '';
                switch ($schedule['mode']) {
                    case 'bus':
                        $booking_page = 'book_bus.php';
                        break;
                    case 'train':
                        $booking_page = 'book_train.php';
                        break;
                    case 'plane': // Your database stores 'plane' for flights
                        $booking_page = 'book_plane.php';
                        break;
                    default:
                        $booking_page = '#'; // Fallback for unknown mode
                        break;
                }
                $book_link = "{$booking_page}?id=" . htmlspecialchars($schedule['id']) . "&mode=" . htmlspecialchars($schedule['mode']);

                // Determine the icon based on mode
                $icon_class = 'fas fa-question-circle'; // Default unknown icon
                if ($schedule['mode'] == 'bus') {
                    $icon_class = 'fas fa-bus';
                } elseif ($schedule['mode'] == 'train') {
                    $icon_class = 'fas fa-train';
                } elseif ($schedule['mode'] == 'plane') {
                    $icon_class = 'fas fa-plane';
                }
            ?>
                <div class="col-lg-6 col-md-12">
                    <div class="result-card">
                        <div class="header">
                            <div>
                                <i class="mode-icon <?= $icon_class ?>"></i>
                                <h5><?= htmlspecialchars($schedule['coach_class']) ?></h5>
                            </div>
                            <span class="fare">₹<?= htmlspecialchars(number_format($schedule['fare_amount'], 0)) ?></span>
                        </div>
                        <div class="details">
                            <div class="info-block">
                                <h6><?= htmlspecialchars($schedule['from_city']) ?></h6>
                                <p>Departure: <?= date("h:i A", strtotime($schedule['time'])) ?></p>
                            </div>
                            <i class="fas fa-arrow-right arrow"></i>
                            <div class="info-block">
                                <h6><?= htmlspecialchars($schedule['to_city']) ?></h6>
                                <p>Arrival: N/A</p> <!-- Assuming arrival time is not in your current schedule table -->
                            </div>
                        </div>
                        <div class="footer-info">
                            <span><i class="fas fa-calendar-alt"></i> Date: <?= formatDateForDisplay($schedule['date']) ?></span>
                            <span><i class="fas fa-chair"></i> Seats: <?= htmlspecialchars($schedule['total_seats'] - $schedule['available_seats']) ?> / <?= htmlspecialchars($schedule['total_seats']) ?></span>
                            <span><i class="fas fa-check-circle"></i> Available: <?= htmlspecialchars($schedule['available_seats']) ?></span>
                        </div>
                        <a href="<?= $book_link ?>" class="btn btn-primary book-btn mt-3">Book Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            No <?= htmlspecialchars($mode) ?>s found for this route and date. Please try different criteria.
        </div>
    <?php endif; ?>
</div>

<footer class="main-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5>Sair Karo</h5>
                <p>Your one-stop solution for booking bus, train, and flight tickets easily and securely.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="check_schedule.php">Check Schedules</a></li>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h5>Information</h5>
                <ul>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Contact Info</h5>
                <p><i class="fas fa-map-marker-alt me-2"></i> Pimpri-Chinchwad, Maharashtra, India</p>
                <p><i class="fas fa-phone me-2"></i> +123 456 7890</p>
                <p><i class="fas fa-envelope me-2"></i> info@sairkaro.com</p>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> Sair Karo. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
