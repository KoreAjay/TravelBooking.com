<?php
// Enable error reporting for development (disable/adjust for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'include/db.php'; // Ensure this path is correct

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$generated_trip_plan_html = ""; // This variable will hold the generated trip plan HTML to display

// Helper function for error messages - updated to match new alert styling
function displayError($message) {
    return "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i> " . htmlspecialchars($message) . "</div>";
}
function displaySuccess($message) {
    return "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i> " . htmlspecialchars($message) . "</div>";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Sanitize and validate inputs
    $destination = trim($_POST['destination'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $interests = $_POST['interests'] ?? []; // Array of interests

    // Basic validation
    if (empty($destination) || empty($start_date) || empty($end_date)) {
        $generated_trip_plan_html = displayError("Please fill in all required fields (Destination, Start Date, End Date).");
    } else {
        // More robust date validation
        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        $current_date_timestamp = strtotime(date('Y-m-d')); // Get timestamp for today's date at midnight

        if ($start_timestamp === false || $end_timestamp === false) {
            $generated_trip_plan_html = displayError("Invalid date format. Please use YYYY-MM-DD.");
        } elseif ($start_timestamp > $end_timestamp) {
            $generated_trip_plan_html = displayError("Start Date cannot be after End Date.");
        } elseif ($end_timestamp < $current_date_timestamp) {
            $generated_trip_plan_html = displayError("End Date cannot be in the past.");
        } else {
            // --- Placeholder for actual trip generation logic ---
            // This is where you'd put your complex logic or API calls.
            // For this example, we'll construct a sample HTML itinerary.

            $interest_list = !empty($interests) ? implode(', ', array_map('htmlspecialchars', $interests)) : 'No specific interests';

            // Construct the full HTML content of the itinerary to be saved
            $full_generated_itinerary_content = "
                <p><strong>Proposed Itinerary for " . htmlspecialchars($destination) . "</strong></p>
                <p><strong>Dates:</strong> " . htmlspecialchars(date('d M, Y', $start_timestamp)) . " to " . htmlspecialchars(date('d M, Y', $end_timestamp)) . "</p>
                <p><strong>Interests:</strong> " . htmlspecialchars($interest_list) . "</p>
                <hr>
                <h5>Day 1: Arrival & Exploration</h5>
                <p>Arrive in " . htmlspecialchars($destination) . ". Check into your accommodation. Explore a local market or a famous landmark.</p>
                <h5>Day 2: Adventure & Nature</h5>
                <p>Enjoy a nature hike or visit a wildlife sanctuary based on your interests.</p>
                <h5>Day 3: Culture & Cuisine</h5>
                <p>Immerse yourself in local culture and taste authentic cuisine.</p>
                <p class='text-muted mt-3'><em>This is a sample itinerary. Your actual AI-generated plan would be more detailed here.</em></p>
            ";

            // --- Database Insertion ---
            $user_id = $_SESSION['user_id'] ?? null; // Get user ID from session if available

            try {
                // Ensure $conn is available and valid
                if (!isset($conn) || !$conn) {
                    throw new Exception("Database connection is not established.");
                }

                // Prepare the SQL statement for inserting the trip plan
                $stmt = $conn->prepare("INSERT INTO trip_plans (user_id, destination, start_date, end_date, interests, generated_content) VALUES (?, ?, ?, ?, ?, ?)");

                if (!$stmt) {
                    throw new Exception("Database prepare error: " . $conn->error);
                }

                $interests_str = implode(', ', $interests); // Convert interests array to string

                // Dynamically bind user_id (NULL for guests, INT for logged-in users)
                if ($user_id === null) {
                    $null_user_id_param = NULL; // Explicitly set to NULL for binding
                    $stmt->bind_param("isssss", $null_user_id_param, $destination, $start_date, $end_date, $interests_str, $full_generated_itinerary_content);
                } else {
                    $stmt->bind_param("isssss", $user_id, $destination, $start_date, $end_date, $interests_str, $full_generated_itinerary_content);
                }

                $stmt->execute();

                $new_plan_id = $conn->insert_id; // Get the ID of the newly inserted row

                $stmt->close();

                // If successful, display the plan and the download link with the new database ID
                $generated_trip_plan_html = displaySuccess("Your trip plan has been generated and saved!") . "
                    <div class='card generated-plan-card mt-4'>
                        <div class='card-body'>
                            " . $full_generated_itinerary_content . "
                            <div class='text-center mt-4'>
                                <a href='download_plan.php?plan_id=" . htmlspecialchars($new_plan_id) . "' class='btn btn-success me-2'><i class='fas fa-download me-2'></i> Download Plan</a>
                                <a href='check_schedule.php' class='btn btn-outline-primary'><i class='fas fa-search me-2'></i> Find Schedules</a>
                            </div>
                        </div>
                    </div>
                ";

            } catch (mysqli_sql_exception $e) {
                error_log("Database error inserting trip plan: " . $e->getMessage());
                $generated_trip_plan_html = displayError("A database error occurred while saving your plan. Please try again later.");
            } catch (Exception $e) {
                error_log("General error inserting trip plan: " . $e->getMessage());
                $generated_trip_plan_html = displayError("An unexpected error occurred. Please try again later.");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Your Trip - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        /* Variables for easy color management - Consistent with other pages */
        :root {
            --primary-color: #dc3545; /* Red from home page */
            --primary-hover-color: #c82333; /* Darker Red for hover */
            --primary-focus-shadow: rgba(220, 53, 69, 0.3); /* Lighter red with transparency for focus */

            --secondary-color: #6c757d; /* Standard Grey */
            --accent-color: #ffc107; /* Bright Yellow/Orange - Highlight */
            --call-to-action-bg: #fd7e14; /* Strong Orange - For specific banners/CTAs */
            --light-bg: #f8f9fa; /* Very Light Grey */
            --dark-text: #343a40; /* Dark Grey for body text */
            --white-color: #ffffff; /* Pure White */
            --footer-bg: #212529; /* Darker background */
            --footer-text: #adb5bd; /* Lighter grey for footer text */
            --heading-font: 'Poppins', sans-serif;
            --body-font: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--body-font);
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-top: 70px; /* Space for fixed navbar */
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Make sure footer sticks to bottom */
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
            color: var(--dark-text);
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 0.5em;
        }

        p {
            margin-bottom: 1rem;
        }

        /* --- Global Transitions --- */
        a, .btn, .nav-link, .form-control, .form-select, .card, .table tbody tr, .mode-icon {
            transition: all 0.3s ease-in-out;
        }

        /* --- Navbar Styling (Copied for consistency) --- */
        .navbar {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background-color: var(--primary-color) !important;
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-weight: 800; /* Bolder */
            font-size: 2rem; /* Slightly larger */
            display: flex;
            align-items: center;
            color: var(--white-color) !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand:hover {
            color: var(--accent-color) !important;
            transform: translateY(-2px);
        }

        .navbar-brand img {
            height: 45px; /* Slightly larger logo */
            margin-right: 12px;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
        }

        .nav-link {
            font-weight: 500;
            color: var(--white-color) !important;
            margin: 0 12px; /* Increased margin */
            padding: 0.6rem 1.2rem;
            border-radius: 8px; /* Rounded corners */
        }

        .nav-link:hover, .nav-link.active {
            color: var(--accent-color) !important;
            background-color: rgba(255, 255, 255, 0.1); /* Subtle background on hover */
            transform: translateY(-2px);
        }

        .navbar-toggler {
            border: none;
            outline: none;
            color: var(--white-color);
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        .navbar .btn-outline-light {
            border-color: var(--white-color);
            color: var(--white-color) !important;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        .navbar .btn-outline-light:hover {
            background-color: var(--white-color);
            color: var(--primary-color) !important; /* Changes to primary red on hover */
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .navbar .btn-warning {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--dark-text) !important;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        .navbar .btn-warning:hover {
            background-color: #e0a800; /* Darker yellow on hover */
            border-color: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        /* Alerts for messages (re-used from other pages) */
        .alert-container {
            position: fixed;
            top: 75px; /* Adjust based on navbar height */
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 500px;
            z-index: 1050; /* Above most elements, below modals */
        }
        .alert {
            margin-bottom: 0; /* No margin between alerts */
            border-radius: 8px;
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Banner Top Bar Design (Same as Home Page) */
        .top-bar-banner {
            background-color: #343a40; /* Dark background, adjust as needed */
            color: #ffffff;
            padding: 10px 0;
            text-align: center;
            font-size: 0.9rem;
            position: relative;
            z-index: 1060; /* Ensure it's above the navbar */
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }
        .top-bar-banner .banner-text {
            flex-grow: 1;
        }
        .top-bar-banner .offer-button {
            background-color: var(--accent-color);
            color: var(--dark-text);
            padding: 5px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .top-bar-banner .offer-button:hover {
            background-color: #e0b000;
        }
        .top-bar-banner .close-banner {
            background: none;
            border: none;
            color: #ffffff;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0 10px;
        }
        .top-bar-banner .close-banner:hover {
            color: var(--primary-color);
        }
        .top-bar-banner .banner-image {
            max-height: 40px; /* Adjust as needed */
            width: auto;
            margin-right: 10px;
            border-radius: 5px;
        }


        /* --- Hero Section Styling (Copied from Check Schedule Page) --- */
        .hero-section {
            background: linear-gradient(rgba(220, 53, 69, 0.8), rgba(220, 53, 69, 0.8)), url('assets/images/travel-banner.jpg') no-repeat center center;
            background-size: cover;
            background-position: center bottom;
            color: var(--white-color);
            padding: 90px 0;
            text-align: center;
            margin-bottom: 50px;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
        }
        .hero-section h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--white-color);
            text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        }
        .hero-section p {
            font-size: 1.35rem;
            max-width: 750px;
            margin: 0 auto 40px;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.5;
            font-weight: 400;
        }


        /* --- Main Content Styling --- */
        .generate-trip-section {
            flex: 1; /* Allows this section to grow and push footer down */
            padding: 60px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        .section-header h1 {
            font-size: 3rem;
            font-weight: 700;
            color: var(--dark-text); /* Changed to dark-text for consistency with home/check schedule titles */
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 15px;
        }
        .section-header h1::after { /* Underline for section title */
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 5px;
            background-color: var(--accent-color);
            border-radius: 3px;
        }
        .section-header p {
            font-size: 1.1rem;
            color: var(--secondary-color);
            max-width: 700px;
            margin: 0 auto;
        }

        .form-card {
            background: var(--white-color);
            padding: 40px;
            border-radius: 18px; /* More rounded to match other cards */
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* Softer, larger shadow */
            max-width: 800px;
            margin: 0 auto;
            border: none;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 10px; /* More margin */
            color: var(--dark-text);
            font-size: 1.05rem; /* Consistent font size */
        }
        .form-control, .form-select {
            border-radius: 12px; /* More rounded to match other forms */
            padding: 14px 18px; /* More padding */
            border: 1px solid #dee2e6;
            font-size: 1.05rem; /* Consistent font size */
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); /* Subtle inner shadow */
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem var(--primary-focus-shadow), inset 0 1px 3px rgba(0,0,0,0.05); /* Consistent focus shadow */
            outline: none;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 14px 25px;
            font-weight: 700; /* Bolder */
            border-radius: 12px; /* More rounded */
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15); /* Button shadow */
        }
        .btn-primary:hover {
            background-color: var(--primary-hover-color); /* Darker red on hover */
            border-color: var(--primary-hover-color);
            transform: translateY(-3px); /* More pronounced lift */
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        }

        .form-check-label { /* Styling for checkbox labels */
            font-weight: 500;
            color: var(--dark-text);
            font-size: 0.95rem;
            margin-left: 5px; /* space between input and label text */
        }
        .form-check-input { /* Styling for checkboxes */
            border-radius: 5px; /* Slightly rounded squares */
            border: 1px solid #ced4da;
            width: 1.25em; /* Standard size */
            height: 1.25em; /* Standard size */
            margin-top: 0.25em; /* Align with text */
            vertical-align: top;
            background-color: #fff;
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 70% 70%;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            color-adjust: exact;
            transition: background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
        }
        .form-check-input:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem var(--primary-focus-shadow);
        }
        .form-check-input:active {
            filter: brightness(90%);
        }

        .generated-plan-card {
            background: var(--white-color);
            padding: 30px;
            border-radius: 18px; /* Consistent with form card */
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* Consistent shadow */
            max-width: 800px;
            margin: 40px auto;
            border: none;
        }
        .generated-plan-card .card-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        .generated-plan-card h5 {
            color: var(--dark-text);
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .generated-plan-card p {
            font-size: 1rem;
            line-height: 1.6;
            color: var(--dark-text);
        }
        .generated-plan-card .btn {
            font-weight: 600; /* Bolder for consistency */
            border-radius: 10px; /* More rounded */
            padding: 12px 20px;
        }
        .generated-plan-card .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .generated-plan-card .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .generated-plan-card .btn-outline-primary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .generated-plan-card .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: var(--white-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* --- Footer Styling (Copied for consistency) --- */
        .main-footer {
            background: var(--footer-bg);
            color: var(--footer-text);
            padding: 70px 0 40px; /* More padding */
            margin-top: auto; /* Push footer to the bottom */
            font-size: 0.98rem;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.1); /* Softer shadow */
        }

        .main-footer h5 {
            color: var(--white-color);
            font-weight: 700; /* Bolder */
            margin-bottom: 30px; /* More margin */
            position: relative;
            padding-bottom: 12px; /* More padding */
        }

        .main-footer h5::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px; /* Wider underline */
            height: 4px; /* Thicker underline */
            background-color: var(--accent-color); /* Uses accent color for underline */
            border-radius: 2px;
        }

        .main-footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .main-footer ul li {
            margin-bottom: 12px; /* More space between items */
        }

        .main-footer ul li a {
            color: var(--footer-text);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .main-footer ul li a:hover {
            color: var(--accent-color);
            transform: translateX(5px); /* Slight slide on hover */
        }

        .main-footer .social-icons a {
            color: var(--white-color);
            font-size: 1.7rem; /* Larger icons */
            margin-right: 20px; /* More space */
            text-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        .main-footer .social-icons a:hover {
            color: var(--accent-color);
            transform: translateY(-5px) scale(1.1); /* More pronounced lift and slight scale */
        }

        .main-footer .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.15); /* Slightly thicker border */
            padding-top: 25px; /* More padding */
            margin-top: 50px; /* More margin */
            text-align: center;
            color: var(--footer-text);
            font-size: 0.9rem;
        }

        /* Responsive Adjustments */
        @media (max-width: 991.98px) {
            .hero-section h1 {
                font-size: 3.2rem;
            }
            .hero-section p {
                font-size: 1.2rem;
            }
            .section-header h1 {
                font-size: 2.5rem;
            }
            .form-card {
                padding: 30px;
            }
            .generated-plan-card {
                padding: 25px;
            }
        }
        @media (max-width: 767.98px) {
            body {
                padding-top: 60px;
            }
            .navbar-brand {
                font-size: 1.7rem;
            }
            .navbar-brand img {
                 height: 38px;
            }
            .navbar-collapse {
                background-color: var(--primary-color);
                padding: 15px 0;
            }
            .nav-link {
                margin: 5px 0;
                text-align: center;
            }
            .top-bar-banner {
                flex-direction: column;
                gap: 10px;
                padding: 8px 0;
            }
            .top-bar-banner .banner-image {
                margin-right: 0;
            }
            .top-bar-banner .close-banner {
                position: absolute;
                top: 5px;
                right: 5px;
            }

            .generate-trip-section {
                padding: 40px 0;
            }
            .section-header {
                margin-bottom: 30px;
            }
            .section-header h1 {
                font-size: 2rem;
            }
            .section-header h1::after {
                width: 70px;
                height: 4px;
            }
            .section-header p {
                font-size: 1rem;
            }
            .form-card {
                padding: 25px;
            }
            .form-control, .form-select, .btn-primary {
                padding: 12px 18px;
            }
            .generated-plan-card {
                padding: 20px;
                margin: 30px auto;
            }
            .generated-plan-card .card-title {
                font-size: 1.5rem;
            }
            .generated-plan-card h5 {
                font-size: 1.1rem;
            }
            .main-footer {
                padding: 50px 0 25px;
            }
            .main-footer h5 {
                margin-bottom: 20px;
                text-align: center;
            }
            .main-footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .main-footer ul {
                text-align: center;
                margin-bottom: 30px;
            }
            .main-footer ul li a:hover {
                transform: none; /* Disable slide on mobile */
            }
            .main-footer .social-icons {
                text-align: center;
                margin-top: 20px;
            }
            .main-footer .social-icons a {
                margin: 0 12px;
                font-size: 1.5rem;
            }
            .main-footer .copyright {
                margin-top: 30px;
                padding-top: 20px;
            }
        }
        @media (max-width: 575.98px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            .hero-section p {
                font-size: 1rem;
            }
            .section-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

<div class="top-bar-banner" id="topBanner">
    <img src="https://placehold.co/100x40/FFC107/343A40?text=AD" alt="Advertisement" class="banner-image">
    <div class="banner-text">
        📢 Special Offer! Get 15% off on all bookings this weekend! Use code: <b>SAIRKARO15</b>
    </div>
    <a href="#" class="offer-button">View Details</a>
    <button class="close-banner" id="closeBanner"><i class="fas fa-times"></i></button>
</div>

<div class="alert-container">
    <?php
    // Display global session messages here if any
    if (!empty($_SESSION['success_message_global'])) {
        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['success_message_global']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        unset($_SESSION['success_message_global']);
    }
    if (!empty($_SESSION['error_message_global'])) {
        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['error_message_global']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        unset($_SESSION['error_message_global']);
    }
    if (!empty($_SESSION['info_message_global'])) {
        echo "<div class='alert alert-info alert-dismissible fade show' role='alert'>" . htmlspecialchars($_SESSION['info_message_global']) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        unset($_SESSION['info_message_global']);
    }
    ?>
</div>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top px-md-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="home.php">
            <img src="assets/images/logo2.png" alt="Sair Karo Logo"> Sair Karo
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="check_schedule.php">Check Schedules</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="generate_trip.php">Generate Trip</a></li>
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

<div class="hero-section">
    <div class="container">
        <h1>Plan Your Ideal Trip</h1>
        <p>Tell us your preferences, and we'll help you craft a personalized travel itinerary.</p>
    </div>
</div>

<main class="generate-trip-section container">
    <div class="section-header">
        <h1>Craft Your Next Adventure</h1>
        <p>Input your desired destination, dates, and interests, and let our AI create a unique itinerary for you!</p>
    </div>

    <div class="form-card">
        <form method="POST">
            <div class="mb-3">
                <label for="destination" class="form-label">Destination</label>
                <input type="text" class="form-control" id="destination" name="destination" placeholder="e.g., Goa, Kyoto, Swiss Alps" required>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="text" class="form-control" id="start_date" name="start_date" placeholder="Select Start Date" required>
                </div>
                <div class="col-md-6">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="text" class="form-control" id="end_date" name="end_date" placeholder="Select End Date" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Interests (Select all that apply)</label>
                <div class="row">
                    <div class="col-sm-6 col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="interests[]" value="Adventure" id="interestAdventure">
                            <label class="form-check-label" for="interestAdventure">
                                <i class="fas fa-hiking me-2"></i> Adventure
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="interests[]" value="Culture & History" id="interestCulture">
                            <label class="form-check-label" for="interestCulture">
                                <i class="fas fa-landmark me-2"></i> Culture & History
                            </label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="interests[]" value="Relaxation" id="interestRelaxation">
                            <label class="form-check-label" for="interestRelaxation">
                                <i class="fas fa-umbrella-beach me-2"></i> Relaxation
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="interests[]" value="Food & Cuisine" id="interestFood">
                            <label class="form-check-label" for="interestFood">
                                <i class="fas fa-utensils me-2"></i> Food & Cuisine
                            </label>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="interests[]" value="Nature & Wildlife" id="interestNature">
                            <label class="form-check-label" for="interestNature">
                                <i class="fas fa-tree me-2"></i> Nature & Wildlife
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="interests[]" value="Shopping" id="interestShopping">
                            <label class="form-check-label" for="interestShopping">
                                <i class="fas fa-shopping-bag me-2"></i> Shopping
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-magic me-2"></i> Generate My Trip Plan
                </button>
            </div>
        </form>
    </div>

    <?php echo $generated_trip_plan_html; // Display the generated plan HTML here ?>

</main>

<footer class="main-footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5>Sair Karo</h5>
                <p>Your one-stop solution for hassle-free bus, train, and flight ticket bookings. Travel with comfort and confidence.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Company</h5>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="#">Careers</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                    <li><a href="#">Partnerships</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Legal</h5>
                <ul>
                    <li><a href="/TravalBooking.com/privacy_policy.html">Privacy Policy</a></li>
                    <li><a href="/TravalBooking.com/terms_conditions.html">Terms & Conditions</a></li>
                    <li><a href="/TravalBooking.com/refund_policy.html">Refund Policy</a></li>
                    <li><a href="/TravalBooking.com/cookie_policy.html">Cookie Policy</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="check_schedule.php">Check Schedules</a></li>
                    <li><a href="feedback.php">Submit Feedback</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Support</h5>
                <ul>
                    <li><a href="contact.php">Help Center</a></li>
                    <li><a href="#">Live Chat</a></li>
                    <li><a href="#">Report an Issue</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; <?= date("Y"); ?> Sair Karo. All rights reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr for date inputs
        flatpickr("#start_date", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                // When start_date changes, update end_date's minDate
                flatpickr("#end_date").set('minDate', dateStr);
            }
        });
        flatpickr("#end_date", {
            dateFormat: "Y-m-d",
            minDate: "today" // Initially set to today
        });

        // Close banner functionality
        const topBanner = document.getElementById('topBanner');
        const closeBannerButton = document.getElementById('closeBanner');
        if (closeBannerButton) {
            closeBannerButton.addEventListener('click', function() {
                topBanner.style.display = 'none';
                // Adjust body padding-top if needed after banner removal
                document.body.style.paddingTop = '70px'; // Revert to just navbar height
            });
        }
        // Initial adjustment for body padding-top if banner is present
        if (topBanner) {
            // Get the computed height of the top banner
            const bannerHeight = topBanner.offsetHeight;
            // Get the computed height of the navbar
            const navbarHeight = document.querySelector('.navbar').offsetHeight;
            // Set body padding-top to sum of banner height and navbar height
            document.body.style.paddingTop = `${bannerHeight + navbarHeight}px`;
        }
    });
</script>
</body>
</html>
