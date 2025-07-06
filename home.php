<?php
// include/db.php should now connect to 'travel_db'
include 'include/db.php'; // Ensure this path is correct and connects to 'travel_db'
session_start(); // Start the session if not already started

// Enable full error reporting for debugging (REMOVE IN PRODUCTION)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check for database connection success
if (mysqli_connect_errno()) {
    error_log("Failed to connect to MySQL: " . mysqli_connect_error());
    // Gracefully handle DB connection failure, don't die() abruptly for users
    $conn = null; // Set connection to null to prevent further DB operations
    $db_connection_failed = true;
} else {
    $db_connection_failed = false;
}

// Fetch recent feedback for the home page
$recent_feedback = [];
if (!$db_connection_failed) {
    // Query to get the latest 3 feedback entries by ID in descending order
    $query_feedback = "SELECT name, city, feedback FROM feedback ORDER BY id DESC LIMIT 3";
    $result_feedback = mysqli_query($conn, $query_feedback);

    // Check if the query was successful
    if ($result_feedback) {
        // Loop through the results and store them in an array
        while ($row = mysqli_fetch_assoc($result_feedback)) {
            $recent_feedback[] = $row;
        }
    } else {
        // Log error if feedback cannot be fetched
        error_log("Error fetching feedback: " . mysqli_error($conn));
    }
}

// Check for session messages (e.g., from search_results.php redirect)
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
$info_message = $_SESSION['info_message'] ?? '';

// Clear the session messages after displaying them
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
unset($_SESSION['info_message']);

// --- Feature: Fetch upcoming departures in next one hour (for the first carousel) ---
$upcoming_departures_next_hour = [
    'bus' => [],
    'train' => [],
    'flight' => [] // Note: using 'flight' key for frontend display, but 'plane' for DB mode
];

// --- Feature: Fetch Top 10 Upcoming Schedules for each mode (Bus, Train, Plane) ---
$top_10_upcoming_buses = []; // This will now fetch from DB and fallback to dummy
$top_10_upcoming_trains = []; // This fetches from DB only for table display
$top_10_upcoming_flights = []; // Note: using 'flight' here for consistency with frontend naming

if (!$db_connection_failed) {
    $current_datetime_str = date('Y-m-d H:i:s');
    $next_hour_datetime_str = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Mapping for database mode names to frontend icon classes and display names
    $modes_display_map = [
        'bus' => ['icon' => 'fas fa-bus', 'display_name' => 'Bus'],
        'train' => ['icon' => 'fas fa-train', 'display_name' => 'Train'],
        'plane' => ['icon' => 'fas fa-plane', 'display_name' => 'Flight']
    ];

    // --- Fetch for "Departures in the Next Hour" (first carousel) ---
    foreach ($modes_display_map as $db_mode_key => $mode_info) {
        $frontend_mode_key = ($db_mode_key === 'plane') ? 'flight' : $db_mode_key; // Map 'plane' to 'flight' for frontend

        $query_next_hour = "
            SELECT id, mode, from_city, to_city, date, time, fare_amount AS fare
            FROM schedule
            WHERE mode = ?
              AND CONCAT(date, ' ', time) > ?
              AND CONCAT(date, ' ', time) <= ?
            ORDER BY CONCAT(date, ' ', time) ASC
            LIMIT 10;
        ";

        if ($stmt = mysqli_prepare($conn, $query_next_hour)) {
            mysqli_stmt_bind_param($stmt, "sss", $db_mode_key, $current_datetime_str, $next_hour_datetime_str);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $row['mode_type'] = $frontend_mode_key; // Store frontend-friendly mode name
                $row['icon_class'] = $mode_info['icon'];
                $upcoming_departures_next_hour[$frontend_mode_key][] = $row;
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("Error preparing next hour departures query for {$db_mode_key}: " . mysqli_error($conn));
        }
    }

    // --- Fetch and populate Top 10 for Buses and Flights (carousels) ---
    $dummy_id_start_bus = 6000;
    $dummy_id_start_flight = 7000;

    foreach (['bus', 'plane'] as $db_mode_key) { // Iterate only for bus and plane for carousels
        $frontend_mode_key = ($db_mode_key === 'plane') ? 'flight' : $db_mode_key;
        $target_array = null;
        $dummy_id_base = 0;

        if ($frontend_mode_key === 'bus') {
            $target_array = &$top_10_upcoming_buses;
            $dummy_id_base = $dummy_id_start_bus;
        } elseif ($frontend_mode_key === 'flight') {
            $target_array = &$top_10_upcoming_flights;
            $dummy_id_base = $dummy_id_start_flight;
        }

        if ($target_array !== null) {
            $query_top_schedules = "
                SELECT id, mode, from_city, to_city, date, time, coach_class, fare_amount AS fare, total_seats, available_seats
                FROM schedule
                WHERE mode = ?
                  AND CONCAT(date, ' ', time) > ?
                ORDER BY CONCAT(date, ' ', time) ASC
                LIMIT 10;
            ";

            if ($stmt = mysqli_prepare($conn, $query_top_schedules)) {
                mysqli_stmt_bind_param($stmt, "ss", $db_mode_key, $current_datetime_str);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)) {
                    $row['mode_type'] = $frontend_mode_key;
                    $row['icon_class'] = $modes_display_map[$db_mode_key]['icon'];
                    $target_array[] = $row;
                }
                mysqli_stmt_close($stmt);
            } else {
                error_log("Error preparing top schedules query for {$db_mode_key}: " . mysqli_error($conn));
            }

            // Add dummy data if less than 10 records are fetched for these carousels
            if (count($target_array) < 10) {
                $dummy_count_needed = 10 - count($target_array);
                $dummy_start_time_base = new DateTime();
                $dummy_start_time_base->modify('+2 hours');

                for ($i = 0; $i < $dummy_count_needed; $i++) {
                    $current_dummy_time_obj = clone $dummy_start_time_base;
                    $current_dummy_time_obj->modify("+" . (($i + count($target_array)) * 15) . " minutes"); // Offset by existing count
                    $dummy_time = $current_dummy_time_obj->format('H:i:s');
                    $dummy_date = $current_dummy_time_obj->format('Y-m-d');

                    $dummy_fare = 0;
                    $dummy_coach_class = '';
                    $dummy_from = '';
                    $dummy_to = '';
                    $dummy_total_seats = 50;
                    $dummy_available_seats = rand(10, $dummy_total_seats);

                    if ($frontend_mode_key === 'bus') {
                        $dummy_from = 'BusCity' . chr(65 + rand(0, 5));
                        $dummy_to = 'BusCity' . chr(65 + rand(6, 10));
                        $dummy_fare = rand(300, 900);
                        $dummy_coach_class = (rand(0,1) == 0) ? 'AC Seater' : 'Non-AC Seater';
                    } elseif ($frontend_mode_key === 'flight') {
                        $dummy_from = 'AirptP' . chr(65 + rand(0, 5));
                        $dummy_to = 'AirptQ' . chr(65 + rand(6, 10));
                        $dummy_fare = rand(2500, 8000);
                        $dummy_coach_class = 'Economy';
                        $db_mode_key_for_dummy = 'plane'; // Ensure correct DB mode for flight dummy
                    }

                    $target_array[] = [
                        'id' => $dummy_id_base++,
                        'mode' => $db_mode_key_for_dummy ?? $db_mode_key,
                        'from_city' => $dummy_from,
                        'to_city' => $dummy_to,
                        'date' => $dummy_date,
                        'time' => $dummy_time,
                        'coach_class' => $dummy_coach_class,
                        'fare' => $dummy_fare,
                        'total_seats' => $dummy_total_seats,
                        'available_seats' => $dummy_available_seats,
                        'mode_type' => $frontend_mode_key,
                        'icon_class' => $modes_display_map[$db_mode_key]['icon']
                    ];
                }
                usort($target_array, function($a, $b) {
                    return strtotime($a['date'] . ' ' . $a['time']) - strtotime($b['date'] . ' ' . $b['time']);
                });
                $target_array = array_slice($target_array, 0, 10);
            }
        }
    }

    // --- ONLY FETCH REAL DATA FOR TOP 10 UPCOMING TRAINS (NO DUMMY FALLBACK HERE) ---
    $query_top_trains = "
        SELECT id, mode, from_city, to_city, date, time, coach_class, fare_amount AS fare, total_seats, available_seats
        FROM schedule
        WHERE mode = 'train'
          AND CONCAT(date, ' ', time) > ?
        ORDER BY CONCAT(date, ' ', time) ASC
        LIMIT 10;
    ";

    if ($stmt = mysqli_prepare($conn, $query_top_trains)) {
        mysqli_stmt_bind_param($stmt, "s", $current_datetime_str);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $top_10_upcoming_trains[] = $row;
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error preparing top trains query: " . mysqli_error($conn));
    }
}

// Function to format the date for display
function formatDateForDisplay($dateString) {
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
    <title>Sair Karo - Book Your Travel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">


    <style>
        /* Variables for easy color management */
        :root {
            --primary-color: #dc3545; /* RedBus-like red, using Bootstrap's danger */
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --white-color: #ffffff;
            --accent-color: #ffc107; /* Bootstrap's warning for accent */
            --footer-bg: #212529;
            --footer-text: #adb5bd;
            --heading-font: 'Poppins', sans-serif;
            --body-font: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--body-font);
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-top: 70px; /* Space for fixed navbar */
            overflow-x: hidden;
            display: flex; /* For footer stickiness */
            flex-direction: column; /* For footer stickiness */
            min-height: 100vh; /* For footer stickiness */
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
            color: var(--dark-text);
        }

        /* --- Navbar Styling --- */
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background-color: var(--primary-color) !important;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            color: var(--white-color) !important;
        }

        .navbar-brand:hover {
            color: var(--accent-color) !important;
        }

        .navbar-brand img {
            height: 40px;
            margin-right: 10px;
        }

        .nav-link {
            font-weight: 500;
            color: var(--white-color) !important;
            margin: 0 10px;
            transition: color 0.3s ease;
            padding: 0.5rem 1rem;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--accent-color) !important;
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
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3csvg%3e");
        }

        /* --- Hero Section with Search Widget --- */
        .hero-section {
            background-color: var(--primary-color);
            padding: 80px 0 50px;
            color: var(--white-color);
            position: relative;
            z-index: 1;
            overflow: hidden; /* For background image if added */
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-section p {
            font-size: 1.25rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        /* Search Widget Specific Styling */
        .search-widget-container {
            background-color: var(--white-color);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 10;
            transform: translateY(50px); /* Lift it over the next section */
            margin-bottom: 100px; /* Adjust margin to prevent overlap */
        }

        .search-widget-container .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        .search-widget-container .form-control {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px 15px 15px 45px; /* Adjust padding for icon */
            height: auto;
            font-size: 1rem;
            color: var(--dark-text);
        }

        .search-widget-container .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        .search-widget-container .form-group .form-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            font-size: 1.2rem;
        }

        .search-widget-container .swap-button {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: background-color 0.2s ease;
        }
        .search-widget-container .swap-button:hover {
            background-color: rgba(220, 53, 69, 0.1); /* Using rgba for primary color */
        }

        .date-input-group .form-control {
            padding-right: 15px; /* Remove extra padding if no swap button */
        }

        .date-options button {
            background-color: #f0f2f5;
            border: 1px solid #ddd;
            color: var(--dark-text);
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .date-options button.active, .date-options button:hover {
            background-color: var(--primary-color);
            color: var(--white-color);
            border-color: var(--primary-color);
        }

        .form-check-switch {
            display: flex;
            align-items: center;
            justify-content: flex-end; /* Align to the right */
            margin-top: 10px;
        }
        .form-check-switch label {
            margin-right: 10px;
            font-weight: 500;
            color: var(--dark-text);
            font-size: 0.95rem;
        }
        .form-check-switch .form-check-input {
            width: 3.2em; /* Wider switch */
            height: 1.8em; /* Taller switch */
            background-color: #e9ecef;
            border-color: #e9ecef;
            transition: background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
            cursor: pointer;
        }
        .form-check-switch .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .form-check-switch .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }

        .search-button {
            width: 100%;
            padding: 15px;
            font-size: 1.25rem;
            font-weight: 600;
            border-radius: 10px;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .search-button:hover {
            background-color: #c82333; /* Darker red on hover */
            border-color: #bd2130;
            transform: translateY(-2px);
        }

        /* Train Booking Promo */
        .train-promo-card {
            background-color: #f2f2f2; /* Light grey background */
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .train-promo-card .train-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-right: 20px;
        }
        .train-promo-card .content h4 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .train-promo-card .content p {
            font-size: 0.95rem;
            color: var(--secondary-color);
            margin-bottom: 0;
        }
        .train-promo-card .btn {
            background-color: #28a745; /* Green for booking */
            border-color: #28a745;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }
        .train-promo-card .btn:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        /* --- Section Titles --- */
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 80px;
            margin-bottom: 40px;
            text-align: center;
            color: var(--dark-text); /* Changed to dark-text for consistency */
            position: relative;
            padding-bottom: 15px;
        }
        .section-title::after {
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

        /* --- Offers Section --- */
        .offers-tabs .nav-link {
            color: var(--dark-text) !important;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-right: 10px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .offers-tabs .nav-link.active,
        .offers-tabs .nav-link:hover {
            background-color: var(--primary-color);
            color: var(--white-color) !important;
            border-color: var(--primary-color);
        }

        .offers-container {
            display: flex;
            overflow-x: auto; /* Enable horizontal scrolling */
            -webkit-overflow-scrolling: touch; /* Smooth scrolling on iOS */
            padding-bottom: 15px; /* Space for scrollbar */
            gap: 20px; /* Space between cards */
            scroll-snap-type: x mandatory; /* Snap to cards */
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        .offers-container::-webkit-scrollbar { /* Hide scrollbar for Chrome, Safari and Opera */
            display: none;
        }

        .offer-card {
            flex: 0 0 min(300px, 90vw); /* Adjusted for better responsiveness on small screens */
            scroll-snap-align: start; /* Snap to start of card */
            background-color: var(--white-color);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease;
            cursor: pointer;
            position: relative;
            min-height: 250px; /* Ensure minimum height */
        }
        .offer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .offer-card-header {
            background-color: var(--primary-color);
            color: var(--white-color);
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .offer-card-body {
            padding: 20px;
        }
        .offer-card-body h6 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 5px;
        }
        .offer-card-body p {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }
        .offer-card-image {
            height: 100px; /* Adjusted height for image */
            background-size: cover;
            background-position: center;
            margin-bottom: 10px;
            border-radius: 8px; /* Slightly rounded corners for image */
        }
        .offer-code {
            display: inline-block;
            background-color: #e9ecef;
            color: var(--primary-color);
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            margin-top: 10px;
        }

        /* What's New Section */
        .whats-new-card {
            background-color: var(--primary-color); /* Kept primary color for highlight */
            color: var(--white-color);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.3s ease;
            height: 100%;
        }
        .whats-new-card:hover {
            transform: translateY(-5px);
        }
        .whats-new-card .content {
            flex-grow: 1;
        }
        .whats-new-card .content h5 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: inherit; /* Inherit white color */
        }
        .whats-new-card .content p {
            font-size: 0.95rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        .whats-new-card .link {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .whats-new-card .link i {
            margin-left: 8px;
            transition: margin-left 0.2s ease;
        }
        .whats-new-card .link:hover i {
            margin-left: 12px;
        }
        .whats-new-card .icon-img {
            margin-left: 20px;
            height: 80px;
            width: auto;
        }

        /* --- Departure Card for Carousel --- */
        .departure-card {
            background-color: var(--white-color);
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin: 10px; /* Space around cards in carousel */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: auto; /* Allow height to adjust */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 220px; /* Ensure some consistent height */
            text-align: center;
        }
        .departure-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .departure-card .mode-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .departure-card h5 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 10px;
        }
        .departure-card p {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 8px;
        }
        .departure-card .fare {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 15px;
        }
        .departure-card .btn {
            margin-top: 20px;
            font-weight: 600;
            border-radius: 8px;
        }

        /* Carousel Adjustments */
        .carousel-container .carousel-inner {
            padding-bottom: 40px; /* Space for indicators */
        }
        .carousel-container .carousel-control-prev,
        .carousel-container .carousel-control-next {
            width: 5%;
            color: var(--primary-color);
            opacity: 0.8;
        }
        .carousel-container .carousel-control-prev-icon,
        .carousel-container .carousel-control-next-icon {
            background-color: var(--primary-color); /* Make icons visible */
            border-radius: 50%;
            padding: 15px;
        }
        .carousel-container .carousel-indicators [data-bs-target] {
            background-color: var(--primary-color);
            opacity: 0.6;
        }
        .carousel-container .carousel-indicators .active {
            opacity: 1;
        }

        /* --- Happy Clients Section (for fetched feedback) --- */
        .happy-client-card {
            background: var(--white-color);
            padding: 30px;
            border-left: 5px solid var(--primary-color);
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .happy-client-card:hover {
            transform: translateY(-5px);
        }

        .happy-client-card h5 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .happy-client-card p {
            font-style: italic;
            color: var(--secondary-color);
            line-height: 1.6;
            flex-grow: 1;
        }
        .happy-client-card .client-info {
            font-weight: 600;
            color: var(--dark-text);
            margin-top: 15px;
            font-size: 0.95rem;
        }
        .happy-client-card .client-info span {
            color: var(--secondary-color);
            font-weight: 400;
        }


        /* --- Footer Styling --- */
        .main-footer {
            background: var(--footer-bg);
            color: var(--footer-text);
            padding: 60px 0 30px;
            margin-top: 80px;
            font-size: 0.95rem;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.08);
            width: 100%; /* Ensure footer spans full width */
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

        /* Responsive Adjustments */
        @media (max-width: 767.98px) {
            body { padding-top: 60px; }
            .navbar-brand { font-size: 1.5rem; }
            .navbar-collapse { background-color: var(--primary-color); padding: 15px 0; }
            .nav-link { margin: 5px 0; text-align: center; }

            .hero-section { padding: 50px 0 30px; }
            .hero-section h1 { font-size: 2.5rem; }
            .hero-section p { font-size: 1.05rem; margin-bottom: 20px; }
            .search-widget-container { transform: translateY(30px); margin-bottom: 60px; padding: 20px;}
            .search-widget-container .form-control { padding: 12px 12px 12px 40px; }
            .search-widget-container .form-group .form-icon { font-size: 1rem; left: 10px; }
            .search-widget-container .swap-button { right: 10px; font-size: 1.2rem; }
            .search-button { padding: 12px; font-size: 1.1rem; }
            .train-promo-card { flex-direction: column; text-align: center; }
            .train-promo-card .train-icon { margin-right: 0; margin-bottom: 15px; }

            .section-title { font-size: 2.2rem; margin-top: 50px; margin-bottom: 30px; }
            .section-title::after { width: 70px; height: 4px; }

            .offers-tabs { justify-content: center; }
            .offers-tabs .nav-link { margin-bottom: 10px; }
            .offers-container { padding-bottom: 10px; }
            /* No specific flex-basis for .offer-card here as min() handles it */

            .whats-new-card { flex-direction: column; text-align: center; }
            .whats-new-card .icon-img { margin-left: 0; margin-top: 20px; }
            .whats-new-card .content { margin-bottom: 15px; }
            .whats-new-card .link { justify-content: center; }

            .departure-card { margin: 5px; min-height: 200px; padding: 20px; }
            .departure-card .mode-icon { font-size: 2rem; }
            .departure-card h5 { font-size: 1.1rem; }
            .departure-card p { font-size: 0.8rem; }
            .departure-card .fare { font-size: 1rem; }
            .departure-card .btn { font-size: 0.9rem; padding: 8px 15px; }

            .happy-client-card { margin-bottom: 15px; padding: 20px; }

            .main-footer { padding: 40px 0 20px; margin-top: 50px; }
            .main-footer h5 { margin-bottom: 15px; }
            .main-footer .social-icons { text-align: center; margin-top: 20px; }
            .main-footer .social-icons a { margin: 0 10px; }
            .main-footer .copyright { margin-top: 20px; padding-top: 15px; }
        }

        @media (min-width: 768px) and (max-width: 991.98px) {
            .hero-section h1 { font-size: 3rem; }
            .hero-section p { font-size: 1.1rem; }
            .search-widget-container { transform: translateY(40px); margin-bottom: 80px; }
            .section-title { font-size: 2.5rem; }
            .offer-card { flex: 0 0 45%; } /* Two cards per row for tablets, original was 45% */
        }
        @media (min-width: 992px) {
            /* For larger screens, display multiple cards in a carousel item */
            .carousel-item.active .row,
            .carousel-item-next .row,
            .carousel-item-prev .row {
                display: flex;
            }
            .carousel-item-next:not(.carousel-item-start) .col-lg-4,
            .active.carousel-item-end .col-lg-4 {
                transform: translateX(100%);
            }
            .carousel-item-prev:not(.carousel-item-end) .col-lg-4,
            .active.carousel-item-start .col-lg-4 {
                transform: translateX(-100%);
            }
        }
        .carousel-item .col-lg-4 {
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        /* Hidden Booking Cards (as per redesign, search widget takes priority) */
        .old-booking-cards {
            display: none; /* Hide the old train, bus, plane cards */
        }

        /* Alerts for messages */
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

        /* Banner Top Bar Design */
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

        /* Styles for banner image */
        .top-bar-banner .banner-image {
            max-height: 40px; /* Adjust as needed */
            width: auto;
            margin-right: 10px;
            border-radius: 5px;
        }

        /* Styles for the main travel mode buttons (Bus, Train, Flight) */
        .search-widget-container .nav-pills .nav-item .nav-link {
            background-color: var(--primary-color); /* ALL buttons will now have a red background */
            color: var(--white-color) !important; /* Text and icons will be white for contrast */
            border: 1px solid var(--primary-color); /* Red border for consistency */
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 0 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Subtle shadow */
        }

        /* Icons within these buttons will also be white */
        .search-widget-container .nav-pills .nav-item .nav-link i {
            font-size: 1.3rem;
            color: var(--white-color); /* Ensures icons are white on the red background */
        }

        .search-widget-container .nav-pills .nav-item .nav-link:hover {
            background-color: #c82333; /* Slightly darker red on hover */
            border-color: #c82333;
            color: var(--white-color) !important; /* Keep text white on hover */
            transform: translateY(-2px);
        }

        /* Active (selected) tab state */
        .search-widget-container .nav-pills .nav-item .nav-link.active {
            background-color: #bd2130; /* Even darker red for the ACTIVE button to distinguish it */
            color: var(--white-color) !important; /* Text remains white */
            border-color: #bd2130; /* Border also darker red */
            box-shadow: 0 5px 20px rgba(189, 33, 48, 0.4); /* Stronger, darker shadow for active */
            transform: translateY(-3px);
        }

        /* Ensure active icon is also white (redundant but explicit is good) */
        .search-widget-container .nav-pills .nav-item .nav-link.active i {
            color: var(--white-color);
        }

        /* Chatbot Button Styling */
        .chat-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: var(--primary-color);
            color: var(--white-color);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            z-index: 1000;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .chat-button:hover {
            background-color: var(--primary-hover-color);
            transform: translateY(-5px);
        }
        .chat-button .fas {
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        /* Chat Widget Container */
        #chat-widget-container {
            position: fixed;
            bottom: 100px; /* Position above the chat button */
            right: 30px;
            width: 350px;
            height: 450px;
            background-color: var(--white-color);
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            z-index: 999;
            display: none; /* Hidden by default */
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #e0e0e0;
            cursor: grab; /* Indicates it's draggable */
        }
        #chat-widget-container.dragging {
            cursor: grabbing;
        }

        #chat-widget-header {
            background-color: var(--primary-color);
            color: var(--white-color);
            padding: 15px;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: grab;
        }
        #chat-widget-header button {
            background: none;
            border: none;
            color: var(--white-color);
            font-size: 1.2rem;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        #chat-widget-header button:hover {
            transform: rotate(90deg);
        }

        #chat-widget-body {
            flex-grow: 1;
            padding: 15px; /* Adjusted padding */
            display: flex;
            flex-direction: column;
            /* No justify-content: center; align-items: center; text-align: center; here */
            color: var(--dark-text);
            font-size: 0.95rem;
            overflow-y: auto; /* Enable scrolling for chat content */
            scroll-behavior: smooth; /* Smooth scroll on new messages */
        }

        /* Chat message styling */
        .chat-message {
            max-width: 80%;
            padding: 8px 12px;
            border-radius: 12px;
            margin-bottom: 10px;
            word-wrap: break-word;
        }
        .user-message {
            background-color: #e0f2f7; /* Light blue */
            align-self: flex-end; /* Align to the right */
            border-bottom-right-radius: 3px;
        }
        .bot-message {
            background-color: #f0f0f0; /* Light grey */
            align-self: flex-start; /* Align to the left */
            border-bottom-left-radius: 3px;
        }

        /* Quick questions styling */
        .quick-questions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
            margin-bottom: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            justify-content: center; /* Center quick questions */
        }
        .quick-questions button {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: var(--dark-text);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .quick-questions button:hover {
            background-color: var(--primary-color);
            color: var(--white-color);
            border-color: var(--primary-color);
        }

        /* Chat input area */
        .chat-input-area {
            display: flex;
            padding: 10px 15px;
            border-top: 1px solid #eee;
            background-color: #f8f8f8;
        }
        .chat-input-area input {
            flex-grow: 1;
            border: 1px solid #ddd;
            border-radius: 20px;
            padding: 8px 15px;
            font-size: 0.95rem;
            margin-right: 10px;
        }
        .chat-input-area input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.25);
        }
        .chat-input-area button {
            background-color: var(--primary-color);
            color: var(--white-color);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .chat-input-area button:hover {
            background-color: var(--primary-hover-color);
        }


        /* Responsive adjustments for chat widget */
        @media (max-width: 767.98px) {
            .chat-button {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            #chat-widget-container {
                bottom: 80px;
                right: 20px;
                width: calc(100% - 40px); /* Full width minus margin */
                height: 350px; /* Smaller height on mobile */
                left: 20px; /* Center on small screens */
            }
            #chat-widget-header {
                font-size: 1rem;
                padding: 10px 15px;
            }
            #chat-widget-body {
                padding: 10px; /* Further reduced padding on small screens */
            }
            .chat-message {
                max-width: 90%; /* Wider messages on small screens */
            }
            .quick-questions button {
                padding: 5px 10px;
                font-size: 0.8rem;
            }
            .chat-input-area {
                padding: 8px 10px;
            }
            .chat-input-area input {
                padding: 6px 12px;
            }
            .chat-input-area button {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
        }

        /* Styles for the new table for trains */
        .train-schedule-table {
            width: 100%;
            border-collapse: separate; /* For rounded corners */
            border-spacing: 0; /* Remove default spacing */
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-radius: 12px; /* Rounded corners for the entire table */
            overflow: hidden; /* Ensures child elements respect rounded corners */
        }

        .train-schedule-table th,
        .train-schedule-table td {
            padding: 15px 20px;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid #e0e0e0;
        }

        .train-schedule-table thead th {
            background-color: var(--primary-color);
            color: var(--white-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.05em;
        }

        .train-schedule-table tbody tr:nth-child(odd) {
            background-color: #fefefe;
        }

        .train-schedule-table tbody tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        .train-schedule-table tbody tr:hover {
            background-color: #f0f0f0;
            transform: scale(1.005); /* Slight hover effect */
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .train-schedule-table td .btn {
            padding: 8px 15px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        /* Specific border-radius for first/last cells in header/body */
        .train-schedule-table th:first-child { border-top-left-radius: 12px; }
        .train-schedule-table th:last-child { border-top-right-radius: 12px; }
        .train-schedule-table tbody tr:last-child td:first-child { border-bottom-left-radius: 12px; }
        .train-schedule-table tbody tr:last-child td:last-child { border-bottom-right-radius: 12px; }
        .train-schedule-table tbody tr:last-child td { border-bottom: none; } /* Remove last row bottom border */


        /* Ensure responsive table behavior */
        @media (max-width: 991.98px) {
            .train-schedule-table {
                display: block;
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap; /* Prevent content from wrapping inside cells */
            }

            .train-schedule-table thead,
            .train-schedule-table tbody,
            .train-schedule-table th,
            .train-schedule-table td,
            .train-schedule-table tr {
                display: block;
            }

            .train-schedule-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .train-schedule-table tr {
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                margin-bottom: 1rem;
            }

            .train-schedule-table td {
                border: none;
                position: relative;
                padding-left: 50%; /* Space for pseudo-element label */
                text-align: right;
            }

            .train-schedule-table td::before {
                /* For mobile, display the column headers as labels */
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: var(--dark-text);
            }

            .train-schedule-table tbody tr:last-child td {
                border-bottom: 1px solid #e0e0e0; /* Re-add border for last cell on mobile */
            }
            .train-schedule-table tbody tr {
                border-radius: 12px; /* Ensure rounded corners on mobile */
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
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($info_message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($info_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
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
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="home.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="check_schedule.php">Check Schedules</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link" href="feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                <?php
                // Define username for display, safely checking if it's set in session
                $displayNavUsername = '';
                if (isset($_SESSION['username'])) {
                    $displayNavUsername = $_SESSION['username'];
                }
                ?>
                <?php if (!isset($_SESSION['user_id'])): // Check if user is NOT logged in ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-2 px-3 mt-2 mt-lg-0" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-warning ms-lg-2 px-3 text-dark mt-2 mt-lg-0" href="signup.php">Signup</a></li>
                <?php else: // If user IS logged in ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?= htmlspecialchars($displayNavUsername) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="my_bookings.php"><i class="fas fa-ticket-alt me-1"></i> My Bookings</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-1"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-section text-center">
    <div class="container">
        <h1>Your Journey, Our Priority</h1>
        <p>Book bus, train, and flight tickets with ease and confidence.</p>
    </div>
</div>

<main class="container">
    <div class="search-widget-container mx-auto">
        <ul class="nav nav-pills mb-4 justify-content-center" id="travelModeTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="bus-tab" data-bs-toggle="pill" data-bs-target="#bus-panel" type="button" role="tab" aria-controls="bus-panel" aria-selected="true"><i class="fas fa-bus me-2"></i>Bus Tickets</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="train-tab" data-bs-toggle="pill" data-bs-target="#train-panel" type="button" role="tab" aria-controls="train-panel" aria-selected="false"><i class="fas fa-train me-2"></i>Train Tickets</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="plane-tab" data-bs-toggle="pill" data-bs-target="#plane-panel" type="button" role="tab" aria-controls="plane-panel" aria-selected="false"><i class="fas fa-plane me-2"></i>Flight Tickets</button>
            </li>
        </ul>

        <div class="tab-content" id="travelModeTabsContent">
            <div class="tab-pane fade show active" id="bus-panel" role="tabpanel" aria-labelledby="bus-tab">
                <form action="search_results.php" method="GET">
                    <input type="hidden" name="mode" value="bus">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-location-dot"></i></span>
                                <input type="text" class="form-control" id="busFrom" name="from_city" placeholder="From" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-location-dot"></i></span>
                                <input type="text" class="form-control" id="busTo" name="to_city" placeholder="To" required>
                                <button type="button" class="swap-button" id="swapBusCities" aria-label="Swap cities"><i class="fas fa-right-left"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2 date-input-group">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" id="busDate" name="date" placeholder="Date of Journey" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="date-options mt-2">
                            <button type="button" class="btn btn-light" data-date-offset="0">Today</button>
                            <button type="button" class="btn btn-light" data-date-offset="1">Tomorrow</button>
                        </div>
                        <div class="form-check form-switch form-check-switch mt-2">
                            <label class="form-check-label" for="busReturnSwitch">Return Date</label>
                            <input class="form-check-input" type="checkbox" id="busReturnSwitch">
                        </div>
                    </div>
                    <div class="row g-3" id="busReturnDateRow" style="display: none;">
                        <div class="col-md-12">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" id="busReturnDate" name="return_date" placeholder="Return Date">
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary search-button"><i class="fas fa-search me-2"></i>Search Buses</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="train-panel" role="tabpanel" aria-labelledby="train-tab">
                <form action="search_results.php" method="GET">
                    <input type="hidden" name="mode" value="train">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-location-dot"></i></span>
                                <input type="text" class="form-control" id="trainFrom" name="from_city" placeholder="From Station" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-location-dot"></i></span>
                                <input type="text" class="form-control" id="trainTo" name="to_city" placeholder="To Station" required>
                                <button type="button" class="swap-button" id="swapTrainStations" aria-label="Swap stations"><i class="fas fa-right-left"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2 date-input-group">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" id="trainDate" name="date" placeholder="Date of Journey" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="date-options mt-2">
                            <button type="button" class="btn btn-light" data-date-offset="0">Today</button>
                            <button type="button" class="btn btn-light" data-date-offset="1">Tomorrow</button>
                        </div>
                        <div class="form-check form-switch form-check-switch mt-2">
                            <label class="form-check-label" for="trainReturnSwitch">Return Date</label>
                            <input class="form-check-input" type="checkbox" id="trainReturnSwitch">
                        </div>
                    </div>
                    <div class="row g-3" id="trainReturnDateRow" style="display: none;">
                        <div class="col-md-12">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" id="trainReturnDate" name="return_date" placeholder="Return Date">
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary search-button"><i class="fas fa-search me-2"></i>Search Trains</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="plane-panel" role="tabpanel" aria-labelledby="plane-tab">
                <form action="search_results.php" method="GET">
                    <input type="hidden" name="mode" value="flight">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-plane-departure"></i></span>
                                <input type="text" class="form-control" id="planeFrom" name="from_city" placeholder="From Airport" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-plane-arrival"></i></span>
                                <input type="text" class="form-control" id="planeTo" name="to_city" placeholder="To Airport" required>
                                <button type="button" class="swap-button" id="swapPlaneAirports" aria-label="Swap airports"><i class="fas fa-right-left"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2 date-input-group">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" id="planeDate" name="date" placeholder="Date of Journey" required>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <div class="date-options mt-2">
                            <button type="button" class="btn btn-light" data-date-offset="0">Today</button>
                            <button type="button" class="btn btn-light" data-date-offset="1">Tomorrow</button>
                        </div>
                        <div class="form-check form-switch form-check-switch mt-2">
                            <label class="form-check-label" for="planeReturnSwitch">Return Date</label>
                            <input class="form-check-input" type="checkbox" id="planeReturnSwitch">
                        </div>
                    </div>
                    <div class="row g-3" id="planeReturnDateRow" style="display: none;">
                        <div class="col-md-12">
                            <div class="form-group">
                                <span class="form-icon"><i class="fas fa-calendar-alt"></i></span>
                                <input type="text" class="form-control" id="planeReturnDate" name="return_date" placeholder="Return Date">
                            </div>
                        </div>
                    </div>
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary search-button"><i class="fas fa-search me-2"></i>Search Flights</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <section class="container mt-5">
        <div class="train-promo-card">
            <div class="d-flex align-items-center">
                <i class="fas fa-train train-icon"></i>
                <div class="content">
                    <h4>Book Train Tickets with Sair Karo!</h4>
                    <p>Experience hassle-free train ticket booking. Get confirmed tickets instantly.</p>
                </div>
            </div>
            <a href="check_schedule.php?mode=train" class="btn btn-success">Book Now</a>
        </div>
    </section>


    <!-- Section for "Departures in the Next Hour" -->
    <h2 class="section-title">Departures in the Next Hour</h2>
    <section class="container upcoming-departures-section mb-5">
        <?php
        $any_upcoming_next_hour = false;
        foreach ($upcoming_departures_next_hour as $mode => $departures) {
            if (!empty($departures)) {
                $any_upcoming_next_hour = true;
                break;
            }
        }
        ?>

        <?php if ($db_connection_failed): ?>
            <div class="alert alert-danger text-center" role="alert">
                Unable to fetch upcoming departures due to a database connection issue.
            </div>
        <?php elseif (!$any_upcoming_next_hour): ?>
            <div class="alert alert-info text-center" role="alert">
                No upcoming departures found in the next hour. Check back later!
            </div>
        <?php else: ?>
            <div id="upcomingDeparturesCarousel" class="carousel slide carousel-container" data-bs-ride="carousel" data-bs-interval="false">
                <div class="carousel-inner">
                    <?php
                    $all_upcoming_flattented_next_hour = [];
                    foreach ($upcoming_departures_next_hour as $mode => $departures) {
                        $all_upcoming_flattented_next_hour = array_merge($all_upcoming_flattented_next_hour, $departures);
                    }
                    // Sort combined departures by time to ensure chronological order
                    usort($all_upcoming_flattented_next_hour, function($a, $b) {
                        return strtotime($a['date'] . ' ' . $a['time']) - strtotime($b['date'] . ' ' . $b['time']);
                    });

                    $chunked_departures_next_hour = array_chunk($all_upcoming_flattented_next_hour, 3); // 3 cards per slide for large screens
                    $is_first_item_next_hour = true;

                    foreach ($chunked_departures_next_hour as $chunk_index => $chunk) {
                        echo '<div class="carousel-item ' . ($is_first_item_next_hour ? 'active' : '') . '">';
                        echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
                        foreach ($chunk as $departure) {
                            // Link for "Book Ticket"
                            $link_params = [
                                'mode' => $departure['mode_type'],
                                'id' => $departure['id'] // Pass the ID for booking
                            ];
                            // Adjust parameters for search_results.php if needed, or directly link to booking page if that's the intent
                            // For simplicity, now directly links to booking page, which is typically what 'Book Ticket' does
                            $link_base = '';
                            if ($departure['mode_type'] === 'bus') {
                                $link_base = 'book_bus.php';
                            } elseif ($departure['mode_type'] === 'train') {
                                $link_base = 'book_train.php';
                            } elseif ($departure['mode_type'] === 'flight') {
                                $link_base = 'book_plane.php';
                            }
                            $link = "{$link_base}?" . http_build_query($link_params);


                            echo '<div class="col d-flex">
                                    <div class="departure-card flex-fill">
                                        <i class="' . htmlspecialchars($departure['icon_class']) . ' mode-icon"></i>
                                        <h5>' . htmlspecialchars($departure['from_city']) . ' to ' . htmlspecialchars($departure['to_city']) . '</h5>
                                        <p>Departure: ' . htmlspecialchars(date('h:i A', strtotime($departure['time']))) . '</p>
                                        <p class="fare">₹' . htmlspecialchars(number_format($departure['fare'], 2)) . '</p>
                                        <a href="' . $link . '" class="btn btn-primary mt-auto">Book Ticket</a>
                                    </div>
                                </div>';
                        }
                        echo '</div>'; // end row
                        echo '</div>'; // end carousel-item
                        $is_first_item_next_hour = false;
                    }
                    ?>
                </div>
                <?php if (count($chunked_departures_next_hour) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#upcomingDeparturesCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#upcomingDeparturesCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <div class="carousel-indicators">
                        <?php foreach ($chunked_departures_next_hour as $chunk_index => $chunk): ?>
                            <button type="button" data-bs-target="#upcomingDeparturesCarousel" data-bs-slide-to="<?php echo $chunk_index; ?>" class="<?php echo ($chunk_index === 0) ? 'active' : ''; ?>" aria-current="<?php echo ($chunk_index === 0) ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $chunk_index + 1; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    <!-- END Section for "Departures in the Next Hour" -->

    <!-- NEW SECTION: Top 10 Upcoming Buses -->
    <h2 class="section-title">Top 10 Upcoming Buses</h2>
    <section class="container top-buses-section mb-5">
        <?php if ($db_connection_failed): ?>
            <div class="alert alert-danger text-center" role="alert">
                Unable to fetch top buses due to a database connection issue.
            </div>
        <?php elseif (empty($top_10_upcoming_buses)): ?>
            <div class="alert alert-info text-center" role="alert">
                No top upcoming buses found. Please check back later!
            </div>
        <?php else: ?>
            <div id="topBusesCarousel" class="carousel slide carousel-container" data-bs-ride="carousel" data-bs-interval="false">
                <div class="carousel-inner">
                    <?php
                    $chunked_top_buses = array_chunk($top_10_upcoming_buses, 3); // 3 cards per slide for large screens
                    $is_first_top_bus_item = true;

                    foreach ($chunked_top_buses as $chunk_index => $chunk) {
                        echo '<div class="carousel-item ' . ($is_first_top_bus_item ? 'active' : '') . '">';
                        echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
                        foreach ($chunk as $bus) {
                            $link_params = [
                                'mode' => 'bus',
                                'id' => $bus['id'] // Pass the ID for booking
                            ];
                            $link = "book_bus.php?" . http_build_query($link_params);

                            echo '<div class="col d-flex">
                                    <div class="departure-card flex-fill">
                                        <i class="' . htmlspecialchars($bus['icon_class']) . ' mode-icon"></i>
                                        <h5>' . htmlspecialchars($bus['from_city']) . ' to ' . htmlspecialchars($bus['to_city']) . '</h5>
                                        <p>Departure: ' . htmlspecialchars(date('d M', strtotime($bus['date']))) . ' at ' . htmlspecialchars(date('h:i A', strtotime($bus['time']))) . '</p>
                                        <p>Class: ' . htmlspecialchars($bus['coach_class']) . '</p>
                                        <p class="fare">₹' . htmlspecialchars(number_format($bus['fare'], 2)) . '</p>
                                        <a href="' . $link . '" class="btn btn-primary mt-auto">Book Ticket</a>
                                    </div>
                                </div>';
                        }
                        echo '</div>'; // end row
                        echo '</div>'; // end carousel-item
                        $is_first_top_bus_item = false;
                    }
                    ?>
                </div>
                <?php if (count($chunked_top_buses) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#topBusesCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#topBusesCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <div class="carousel-indicators">
                        <?php foreach ($chunked_top_buses as $chunk_index => $chunk): ?>
                            <button type="button" data-bs-target="#topBusesCarousel" data-bs-slide-to="<?php echo $chunk_index; ?>" class="<?php echo ($chunk_index === 0) ? 'active' : ''; ?>" aria-current="<?php echo ($chunk_index === 0) ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $chunk_index + 1; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    <!-- END NEW SECTION: Top 10 Upcoming Buses -->

    <!-- NEW SECTION: Top 10 Upcoming Trains (Table Format) -->
    <h2 class="section-title">Top 10 Upcoming Trains</h2>
    <section class="container top-trains-section mb-5">
        <?php if ($db_connection_failed): ?>
            <div class="alert alert-danger text-center" role="alert">
                Unable to fetch top trains due to a database connection issue.
            </div>
        <?php elseif (empty($top_10_upcoming_trains)): ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                No top upcoming trains found. Please check back later!
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover train-schedule-table">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Class</th>
                            <th>Fare</th>
                            <th>Available Seats</th>
                            <th>Book Now</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_10_upcoming_trains as $train): ?>
                            <tr>
                                <td data-label="From"><?= htmlspecialchars($train['from_city']) ?></td>
                                <td data-label="To"><?= htmlspecialchars($train['to_city']) ?></td>
                                <td data-label="Date"><?= htmlspecialchars(date('d M', strtotime($train['date']))) ?></td>
                                <td data-label="Time"><?= htmlspecialchars(date('h:i A', strtotime($train['time']))) ?></td>
                                <td data-label="Class"><?= htmlspecialchars($train['coach_class']) ?></td>
                                <td data-label="Fare">₹<?= htmlspecialchars(number_format($train['fare'], 2)) ?></td>
                                <td data-label="Available Seats"><?= htmlspecialchars($train['available_seats']) ?></td>
                                <td data-label="Book Now">
                                    <?php
                                        // Ensure the mode is correctly passed as 'train' for book_train.php
                                        $book_link = "book_train.php?id=" . htmlspecialchars($train['id']) . "&mode=train";
                                    ?>
                                    <a href="<?= $book_link ?>" class="btn btn-primary btn-sm">Book Now</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
    <!-- END NEW SECTION: Top 10 Upcoming Trains (Table Format) -->


    <!-- NEW SECTION: Top 10 Upcoming Flights -->
    <h2 class="section-title">Top 10 Upcoming Flights</h2>
    <section class="container top-flights-section mb-5">
        <?php if ($db_connection_failed): ?>
            <div class="alert alert-danger text-center" role="alert">
                Unable to fetch top flights due to a database connection issue.
            </div>
        <?php elseif (empty($top_10_upcoming_flights)): ?>
            <div class="alert alert-info text-center" role="alert">
                No top upcoming flights found. Please check back later!
            </div>
        <?php else: ?>
            <div id="topFlightsCarousel" class="carousel slide carousel-container" data-bs-ride="carousel" data-bs-interval="false">
                <div class="carousel-inner">
                    <?php
                    $chunked_top_flights = array_chunk($top_10_upcoming_flights, 3); // 3 cards per slide for large screens
                    $is_first_top_flight_item = true;

                    foreach ($chunked_top_flights as $chunk_index => $chunk) {
                        echo '<div class="carousel-item ' . ($is_first_top_flight_item ? 'active' : '') . '">';
                        echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
                        foreach ($chunk as $flight) {
                            $link_params = [
                                'mode' => 'flight',
                                'id' => $flight['id'] // Pass the ID for booking
                            ];
                            $link = "book_plane.php?" . http_build_query($link_params); // Link to book_plane.php


                            echo '<div class="col d-flex">
                                    <div class="departure-card flex-fill">
                                        <i class="' . htmlspecialchars($flight['icon_class']) . ' mode-icon"></i>
                                        <h5>' . htmlspecialchars($flight['from_city']) . ' to ' . htmlspecialchars($flight['to_city']) . '</h5>
                                        <p>Departure: ' . htmlspecialchars(date('d M', strtotime($flight['date']))) . ' at ' . htmlspecialchars(date('h:i A', strtotime($flight['time']))) . '</p>
                                        <p>Class: ' . htmlspecialchars($flight['coach_class']) . '</p>
                                        <p class="fare">₹' . htmlspecialchars(number_format($flight['fare'], 2)) . '</p>
                                        <a href="' . $link . '" class="btn btn-primary mt-auto">Book Ticket</a>
                                    </div>
                                </div>';
                        }
                        echo '</div>'; // end row
                        echo '</div>'; // end carousel-item
                        $is_first_top_flight_item = false;
                    }
                    ?>
                </div>
                <?php if (count($chunked_top_flights) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#topFlightsCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#topFlightsCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                    <div class="carousel-indicators">
                        <?php foreach ($chunked_top_flights as $chunk_index => $chunk): ?>
                            <button type="button" data-bs-target="#topFlightsCarousel" data-bs-slide-to="<?php echo $chunk_index; ?>" class="<?php echo ($chunk_index === 0) ? 'active' : ''; ?>" aria-current="<?php echo ($chunk_index === 0) ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $chunk_index + 1; ?>"></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    <!-- END NEW SECTION: Top 10 Upcoming Flights -->


    <!-- NEW SECTION: Popular Destinations/Routes -->
    <h2 class="section-title">Popular Destinations & Routes</h2>
    <section class="container popular-routes-section mb-5">
        <div id="popularRoutesCarousel" class="carousel slide carousel-container" data-bs-ride="carousel" data-bs-interval="false">
            <div class="carousel-inner">
                <?php
                $popular_routes_data = [
                    [
                        'mode' => 'bus', 'icon' => 'fas fa-bus',
                        'from' => 'Mumbai', 'to' => 'Goa',
                        'description' => 'Beach paradise await! Direct buses daily.',
                        'fare' => 700
                    ],
                    [
                        'mode' => 'train', 'icon' => 'fas fa-train',
                        'from' => 'Delhi', 'to' => 'Shimla',
                        'description' => 'Hill station retreat. Scenic train journeys.',
                        'fare' => 950
                    ],
                    [
                        'mode' => 'plane', 'icon' => 'fas fa-plane', // Changed 'flight' to 'plane'
                        'from' => 'Bengaluru', 'to' => 'Delhi',
                        'description' => 'Connects major metros. Fast & convenient.',
                        'fare' => 4500
                    ],
                    [
                        'mode' => 'bus', 'icon' => 'fas fa-bus',
                        'from' => 'Pune', 'to' => 'Nashik',
                        'description' => 'Vineyard tours & spiritual escape.',
                        'fare' => 400
                    ],
                    [
                        'mode' => 'train', 'icon' => 'fas fa-train',
                        'from' => 'Kolkata', 'to' => 'Darjeeling',
                        'description' => 'Toy train rides and stunning views.',
                        'fare' => 800
                    ],
                    [
                        'mode' => 'plane', 'icon' => 'fas fa-plane', // Changed 'flight' to 'plane'
                        'from' => 'Hyderabad', 'to' => 'Chennai',
                        'description' => 'Quick access to the IT hub of the South.',
                        'fare' => 2800
                    ],
                ];

                $chunked_popular_routes = array_chunk($popular_routes_data, 3);
                $is_first_popular_route_item = true;

                foreach ($chunked_popular_routes as $chunk_index => $chunk) {
                    echo '<div class="carousel-item ' . ($is_first_popular_route_item ? 'active' : '') . '">';
                    echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
                    foreach ($chunk as $route) {
                        // Construct the link dynamically
                        $link_params = ['mode' => $route['mode']];
                        // If linking directly to booking page, need 'id' not city names
                        // For popular routes, we're assuming they lead to a general search or specific booking page with predefined params
                        $link_base = '';
                        if ($route['mode'] == 'bus') {
                            $link_base = 'search_results.php';
                            $link_params['from_city'] = $route['from'];
                            $link_params['to_city'] = $route['to'];
                        } elseif ($route['mode'] == 'train') {
                             $link_base = 'search_results.php'; // Still leads to search for train
                            $link_params['from_city'] = $route['from']; // Use from_city as per updated home search
                            $link_params['to_city'] = $route['to']; // Use to_city as per updated home search
                        } elseif ($route['mode'] == 'plane') {
                             $link_base = 'search_results.php'; // Still leads to search for plane
                            $link_params['from_city'] = $route['from']; // Use from_city as per updated home search
                            $link_params['to_city'] = $route['to']; // Use to_city as per updated home search
                        }
                        $link_params['date'] = date('Y-m-d'); // Default to today for search
                        $link = "{$link_base}?" . http_build_query($link_params);

                        echo '<div class="col d-flex">
                                <div class="departure-card flex-fill">
                                    <i class="' . htmlspecialchars($route['icon']) . ' mode-icon"></i>
                                    <h5>' . htmlspecialchars($route['from']) . ' to ' . htmlspecialchars($route['to']) . '</h5>
                                    <p>' . htmlspecialchars($route['description']) . '</p>
                                    <p class="fare">Starting from ₹' . htmlspecialchars(number_format($route['fare'], 2)) . '</p>
                                    <a href="' . $link . '" class="btn btn-primary mt-auto">Explore ' . ucfirst($route['mode']) . 's</a>
                                </div>
                            </div>';
                    }
                    echo '</div>'; // end row
                    echo '</div>'; // end carousel-item
                    $is_first_popular_route_item = false;
                }
                ?>
            </div>
            <?php if (count($chunked_popular_routes) > 1): ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#popularRoutesCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#popularRoutesCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
                <div class="carousel-indicators">
                    <?php foreach ($chunked_popular_routes as $chunk_index => $chunk): ?>
                        <button type="button" data-bs-target="#popularRoutesCarousel" data-bs-slide-to="<?php echo $chunk_index; ?>" class="<?php echo ($chunk_index === 0) ? 'active' : ''; ?>" aria-current="<?php echo ($chunk_index === 0) ? 'true' : 'false'; ?>" aria-label="Slide <?php echo $chunk_index + 1; ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- END NEW SECTION: Popular Destinations/Routes -->


    <h2 class="section-title">Exclusive Offers & Deals</h2>
    <section class="container offers-section">
        <div class="d-flex justify-content-center mb-4 offers-tabs">
            <button class="nav-link active" data-filter="all">All Offers</button>
            <button class="nav-link" data-filter="bus">Bus Offers</button>
            <button class="nav-link" data-filter="train">Train Offers</button>
            <button class="nav-link" data-filter="flight">Flight Offers</button>
        </div>

        <div class="offers-container">
            <div class="offer-card bus">
                <div class="offer-card-header">BUS OFFER</div>
                <div class="offer-card-body">
                    <div class="offer-card-image" style="background-image: url('assets/images/bus_offer1.png');"></div>
                    <h6>Flat 10% Off on Bus Tickets!</h6>
                    <p>Book your bus tickets and get exciting cashback rewards.</p>
                    <span class="offer-code">BUS10OFF</span>
                </div>
            </div>
            <div class="offer-card train">
                <div class="offer-card-header">TRAIN OFFER</div>
                <div class="offer-card-body">
                    <div class="offer-card-image" style="background-image: url('assets/images/train_offer1.jpg');"></div>
                    <h6>Up to ₹200 Cashback on Train Bookings</h6>
                    <p>Book your train tickets and get exciting cashback rewards.</p>
                    <span class="offer-code">TRAINCASH</span>
                </div>
            </div>
            <div class="offer-card flight">
                <div class="offer-card-header">FLIGHT OFFER</div>
                <div class="offer-card-body">
                    <div class="offer-card-image" style="background-image: url('assets/images/flight_offer1.jpg');"></div>
                    <h6>Save Big on Domestic Flights!</h6>
                    <p>Grab the best deals on domestic flight bookings. Fly affordably.</p>
                    <span class="offer-code">FLYSAFE</span>
                </div>
            </div>
            <div class="offer-card bus">
                <div class="offer-card-header">BUS OFFER</div>
                <div class="offer-card-body">
                    <div class="offer-card-image" style="background-image: url('https://placehold.co/400x150/dc3545/ffffff?text=BUS+DEAL');"></div>
                    <h6>Weekend Bus Bonanza - 15% Off!</h6>
                    <p>Plan your weekend getaway by bus and save more.</p>
                    <span class="offer-code">WEEKEND15</span>
                </div>
            </div>
            <div class="offer-card train">
                <div class="offer-card-header">TRAIN OFFER</div>
                <div class="offer-card-body">
                    <div class="offer-card-image" style="background-image: url('assets/images/train_card.jpg');"></div>
                    <h6>First Time Train Booking - ₹100 Off</h6>
                    <p>New to Sair Karo? Get a special discount on your first train ticket.</p>
                    <span class="offer-code">NEWTRAIN</span>
                </div>
            </div>
            <div class="offer-card flight">
                <div class="offer-card-header">FLIGHT OFFER</div>
                <div class="offer-card-body">
                    <div class="offer-card-image" style="background-image: url('assets/images/flight_icon.png');"></div>
                    <h6>International Flight Deals - Up to 20% Off</h6>
                    <p>Explore the world with amazing discounts on international flights.</p>
                    <span class="offer-code">GLOBALFLY</span>
                </div>
            </div>
        </div>
    </section>

    <h2 class="section-title">What's New at Sair Karo</h2>
    <section class="container whats-new-section mb-5">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="whats-new-card">
                    <div class="content">
                        <h5>Introducing Live Bus Tracking!</h5>
                        <p>Track your bus in real-time, get accurate ETAs, and never miss your ride.</p>
                        <a href="#" class="link">Learn More <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <img src="assets/images/live_tracking_icon.png" alt="Live Tracking" class="icon-img">
                </div>
            </div>
            <div class="col-md-6">
                <div class="whats-new-card">
                    <div class="content">
                        <h5>Enhanced Customer Support 24/7</h5>
                        <p>Our dedicated support team is now available around the clock to assist you.</p>
                        <a href="contact.php" class="link">Contact Us <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <img src="assets/images/customer_support_icon.png" alt="Customer Support" class="icon-img">
                </div>
            </div>
        </div>
    </section>

    <h2 class="section-title">Featured Departures</h2>
    <section class="container departures-section mb-5">
        <div id="featuredDeparturesCarousel" class="carousel slide carousel-container" data-bs-ride="carousel" data-bs-interval="false">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <div class="col d-flex">
                            <div class="departure-card flex-fill">
                                <i class="fas fa-bus mode-icon"></i>
                                <h5>Mumbai to Pune</h5>
                                <p>Frequent buses, comfortable journey.</p>
                                <p class="fare">Starting from ₹350</p>
                                <a href="search_results.php?mode=bus&from_city=Mumbai&to_city=Pune" class="btn btn-primary mt-auto">Book Bus</a>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="departure-card flex-fill">
                                <i class="fas fa-train mode-icon"></i>
                                <h5>Delhi to Agra</h5>
                                <p>Fastest way to Taj Mahal, daily trains.</p>
                                <p class="fare">Starting from ₹450</p>
                                <a href="search_results.php?mode=train&from_city=Delhi&to_city=Agra" class="btn btn-primary mt-auto">Book Train</a>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="departure-card flex-fill">
                                <i class="fas fa-plane mode-icon"></i>
                                <h5>Bengaluru to Chennai</h5>
                                <p>Quick flights for business or leisure.</p>
                                <p class="fare">Starting from ₹2,500</p>
                                <a href="search_results.php?mode=flight&from_city=Bengaluru&to_city=Chennai" class="btn btn-primary mt-auto">Book Flight</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <div class="col d-flex">
                            <div class="departure-card flex-fill">
                                <i class="fas fa-bus mode-icon"></i>
                                <h5>Chennai to Bengaluru</h5>
                                <p>Connects major IT hubs, multiple timings.</p>
                                <p class="fare">Starting from ₹400</p>
                                <a href="search_results.php?mode=bus&from_city=Chennai&to_city=Bengaluru" class="btn btn-primary mt-auto">Book Bus</a>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="departure-card flex-fill">
                                <i class="fas fa-train mode-icon"></i>
                                <h5>Kolkata to Puri</h5>
                                <p>Pilgrimage special, comfortable overnight trains.</p>
                                <p class="fare">Starting from ₹600</p>
                                <a href="search_results.php?mode=train&from_city=Kolkata&to_city=Puri" class="btn btn-primary mt-auto">Book Train</a>
                            </div>
                        </div>
                        <div class="col d-flex">
                            <div class="departure-card flex-fill">
                                <i class="fas fa-plane mode-icon"></i>
                                <h5>Delhi to Mumbai</h5>
                                <p>India's busiest route, frequent flights.</p>
                                <p class="fare">Starting from ₹3,000</p>
                                <a href="search_results.php?mode=flight&from_city=Delhi&to_city=Mumbai" class="btn btn-primary mt-auto">Book Flight</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#featuredDeparturesCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#featuredDeparturesCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#featuredDeparturesCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#featuredDeparturesCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            </div>
        </div>
    </section>


    <h2 class="section-title">What Our Happy Clients Say</h2>
    <section class="container happy-clients-section">
        <div class="row g-4">
            <?php if (!empty($recent_feedback)): ?>
                <?php foreach ($recent_feedback as $feedback): ?>
                    <div class="col-md-4 d-flex">
                        <div class="happy-client-card flex-fill">
                            <h5>Excellent Service!</h5>
                            <p>"<?= htmlspecialchars($feedback['feedback']) ?>"</p>
                            <div class="client-info">- <?= htmlspecialchars($feedback['name']) ?>, <span><?= htmlspecialchars($feedback['city']) ?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No feedback available yet. Be the first to share your experience!</p>
                    <a href="feedback.php" class="btn btn-outline-primary">Submit Feedback</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

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
                    <li><a href="careers.html">Careers</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Legal</h5>
                <ul>
                    <li><a href="privacy_policy.html">Privacy Policy</a></li>
                    <li><a href="terms_conditions.html">Terms & Conditions</a></li>
                    <li><a href="refund_policy.html">Refund Policy</a></li>
                    <li><a href="cookie_policy.html">Cookie Policy</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Quick Links</h5>
                <ul>
                    <li><a href="my_bookings.php">My Bookings</a></li>
                    <li><a href="check_schedule.php">Check Schedules</a></li>
                    <li><a href="feedback.php">Submit Feedback</a></li>
                    <li><a href="faq.html">FAQs</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4 mb-md-0">
                <h5>Support</h5>
                <ul>
                    <li><a href="contact.php">Help Center</a></li>
                    <li><a href="report_issue.html">Report an Issue</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; <?= date("Y"); ?> Sair Karo. All rights reserved.
        </div>
    </div>
</footer>

<!-- Floating Chat Button -->
<div class="chat-button" id="chatButton">
    <i class="fas fa-comment-dots"></i>
</div>

<!-- Draggable Chat Widget Container -->
<div id="chat-widget-container">
    <div id="chat-widget-header">
        Sair Karo Bot
        <button id="closeChatWidget"><i class="fas fa-times"></i></button>
    </div>
    <div id="chat-widget-body">
        <div id="chat-messages">
            <div class="chat-message bot-message">
                Hello! I'm Sair Karo Bot. How can I help you today?
            </div>
        </div>
        <div class="quick-questions">
            <button class="btn btn-sm" data-question="What services do you offer?">Services Offered</button>
            <button class="btn btn-sm" data-question="How can I book a ticket?">How to Book?</button>
            <button class="btn btn-sm" data-question="Can I cancel my ticket?">Ticket Cancellation</button>
            <button class="btn btn-sm" data-question="How do I contact customer support?">Contact Support</button>
            <button class="btn btn-sm" data-question="What payment methods are accepted?">Payment Methods</button>
            <button class="btn btn-sm" data-question="Do you have any offers?">Current Offers</button>
            <button class="btn btn-sm" data-question="How do I reset my password?">Reset Password</button>
            <button class="btn btn-sm" data-question="Where can I find my bookings?">My Bookings</button>
            <button class="btn btn-sm" data-question="Is Sair Karo available on mobile?">Mobile App</button>
        </div>
        <div class="chat-input-area">
            <input type="text" id="chatInput" placeholder="Type your question...">
            <button id="sendChat"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr for date inputs
        flatpickr("#busDate", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                // When busDate changes, update busReturnDate's minDate
                busReturnFlatpickr.set('minDate', dateStr);
            }
        });
        flatpickr("#trainDate", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                trainReturnFlatpickr.set('minDate', dateStr);
            }
        });
        flatpickr("#planeDate", {
            dateFormat: "Y-m-d",
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                planeReturnFlatpickr.set('minDate', dateStr);
            }
        });

        const busReturnFlatpickr = flatpickr("#busReturnDate", {
            dateFormat: "Y-m-d",
            minDate: "today"
        });
        const trainReturnFlatpickr = flatpickr("#trainReturnDate", {
            dateFormat: "Y-m-d",
            minDate: "today"
        });
        const planeReturnFlatpickr = flatpickr("#planeReturnDate", {
            dateFormat: "Y-m-d",
            minDate: "today"
        });


        // Swap button functionality
        function setupSwapButton(fromId, toId, swapButtonId) {
            document.getElementById(swapButtonId).addEventListener('click', function() {
                const fromInput = document.getElementById(fromId);
                const toInput = document.getElementById(toId); /* Corrected: Used document.getElementById to get the element correctly */
                const temp = fromInput.value;
                fromInput.value = toInput.value;
                toInput.value = temp;
            });
        }

        setupSwapButton('busFrom', 'busTo', 'swapBusCities');
        setupSwapButton('trainFrom', 'trainTo', 'swapTrainStations');
        setupSwapButton('planeFrom', 'planeTo', 'swapPlaneAirports');

        // Return date switch functionality
        function setupReturnSwitch(switchId, dateRowId, dateInputId) {
            const returnSwitch = document.getElementById(switchId);
            const returnDateRow = document.getElementById(dateRowId);
            const returnDateInput = document.getElementById(dateInputId);

            returnSwitch.addEventListener('change', function() {
                if (this.checked) {
                    returnDateRow.style.display = 'flex'; // Use flex for Bootstrap row
                    returnDateInput.setAttribute('required', 'required');
                } else {
                    returnDateRow.style.display = 'none';
                    returnDateInput.removeAttribute('required');
                    returnDateInput.value = ''; // Clear the date when unchecked
                }
            });
        }

        setupReturnSwitch('busReturnSwitch', 'busReturnDateRow', 'busReturnDate');
        setupReturnSwitch('trainReturnSwitch', 'trainReturnDateRow', 'trainReturnDate');
        setupReturnSwitch('planeReturnSwitch', 'planeReturnDateRow', 'planeReturnDate');

        // "Today" and "Tomorrow" buttons for date inputs
        document.querySelectorAll('.date-options button').forEach(button => {
            button.addEventListener('click', function() {
                const offset = parseInt(this.dataset.dateOffset);
                const date = new Date();
                date.setDate(date.getDate() + offset);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const formattedDate = `${year}-${month}-${day}`;

                // Determine which date input to update based on active tab
                const activeTab = document.querySelector('.nav-pills .nav-link.active');
                if (activeTab.id === 'bus-tab') {
                    document.getElementById('busDate').value = formattedDate;
                    flatpickr("#busDate").setDate(formattedDate);
                } else if (activeTab.id === 'train-tab') {
                    document.getElementById('trainDate').value = formattedDate;
                    flatpickr("#trainDate").setDate(formattedDate);
                } else if (activeTab.id === 'plane-tab') {
                    document.getElementById('planeDate').value = formattedDate;
                    flatpickr("#planeDate").setDate(formattedDate);
                }

                // Update active state for date buttons (optional, but good UX)
                this.parentNode.querySelectorAll('button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Offers tab filtering
        document.querySelectorAll('.offers-tabs .nav-link').forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                document.querySelectorAll('.offers-tabs .nav-link').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');

                const filter = this.dataset.filter;
                document.querySelectorAll('.offer-card').forEach(card => {
                    if (filter === 'all' || card.classList.contains(filter)) {
                        card.style.display = 'block'; // Show card
                    } else {
                        card.style.display = 'none'; // Hide card
                    }
                });
            });
        });

        // Handle carousel for multiple items per slide on larger screens
        function setupCarouselMultiItem(carouselId) {
            const carousel = document.getElementById(carouselId);
            if (carousel) {
                // Use Bootstrap's built-in slide event but adjust `e.to` for multi-item behavior
                carousel.addEventListener('slide.bs.carousel', function (e) {
                    // Determine how many items should be visible based on current screen width
                    let itemsPerVisibleGroup;
                    if (window.innerWidth >= 992) { // Desktop
                        itemsPerVisibleGroup = 3;
                    } else if (window.innerWidth >= 768) { // Tablet
                        itemsPerVisibleGroup = 2;
                    } else { // Mobile
                        itemsPerVisibleGroup = 1;
                    }

                    const totalItems = this.querySelectorAll('.carousel-item').length; // Number of distinct carousel items (each containing a 'row' of cards)
                    let nextIndex = e.to; // The index Bootstrap is trying to go to

                    // Logic to make the carousel "wrap around" correctly for multiple items
                    if (itemsPerVisibleGroup > 1) { // Only apply this custom logic if more than 1 item is visible
                        if (e.direction === 'next') {
                            // If the next slide would show cards beyond the last available, wrap to the beginning
                            if (nextIndex + itemsPerVisibleGroup > totalItems) {
                                e.preventDefault(); // Stop Bootstrap's default slide
                                this.querySelector('.carousel-inner').style.transform = `translateX(0)`; // Manually reset to first slide
                                this.querySelector('.carousel-item.active').classList.remove('active');
                                this.querySelector('.carousel-item:first-child').classList.add('active');
                            }
                        } else if (e.direction === 'prev') {
                            // If going backwards from the first slide, wrap to the end
                            if (nextIndex < 0) {
                                e.preventDefault(); // Stop Bootstrap's default slide
                                const lastVisibleIndex = totalItems - itemsPerVisibleGroup;
                                const lastItem = this.querySelectorAll('.carousel-item')[lastVisibleIndex];
                                this.querySelector('.carousel-inner').style.transform = `translateX(-${lastVisibleIndex * 100 / totalItems}%)`; // Adjust transform
                                this.querySelector('.carousel-item.active').classList.remove('active');
                                lastItem.classList.add('active');
                            }
                        }
                    }
                });
            }
        }

        setupCarouselMultiItem('featuredDeparturesCarousel');
        setupCarouselMultiItem('upcomingDeparturesCarousel'); // Apply to the new carousel
        setupCarouselMultiItem('topBusesCarousel'); // Apply to the new top buses carousel
        // Removed setupCarouselMultiItem('topTrainsCarousel'); as it's now a table
        setupCarouselMultiItem('topFlightsCarousel'); // Apply to the new top flights carousel
        setupCarouselMultiItem('popularRoutesCarousel'); // Apply to the new popular routes carousel


        // Pause/Play Carousel on Hover
        function setupCarouselHoverPause(carouselId) {
            const carouselElement = document.getElementById(carouselId);
            if (carouselElement) {
                const bsCarousel = new bootstrap.Carousel(carouselElement, {
                    interval: 5000, // Set default interval
                    pause: 'hover' // Pause on hover is already built-in, but explicit for clarity
                });

                // For touch devices or if `pause: 'hover'` isn't enough, you can manually do:
                carouselElement.addEventListener('mouseenter', () => {
                    bsCarousel.pause();
                });
                carouselElement.addEventListener('mouseleave', () => {
                    bsCarousel.cycle();
                });
            }
        }

        setupCarouselHoverPause('upcomingDeparturesCarousel');
        setupCarouselHoverPause('topBusesCarousel');
        // Removed setupCarouselHoverPause('topTrainsCarousel'); as it's now a table
        setupCarouselHoverPause('topFlightsCarousel');
        setupCarouselHoverPause('popularRoutesCarousel');
        setupCarouselHoverPause('featuredDeparturesCarousel');


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

        // Dismiss Bootstrap alerts after a few seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000); // 5 seconds
        });


        // --- Chatbot Button and Draggable Widget Functionality ---
        const chatButton = document.getElementById('chatButton');
        const chatWidgetContainer = document.getElementById('chat-widget-container');
        const chatWidgetHeader = document.getElementById('chat-widget-header');
        const closeChatWidgetButton = document.getElementById('closeChatWidget');
        const chatMessagesDiv = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chatInput');
        const sendChatButton = document.getElementById('sendChat');
        const quickQuestionButtons = document.querySelectorAll('.quick-questions button');


        let isDragging = false;
        let offsetX, offsetY;

        // Pass PHP session username to JavaScript
        <?php
            // Safely get username from session, default to 'Guest'
            $loggedInUsernameForJs = 'Guest';
            if (isset($_SESSION['username'])) {
                $loggedInUsernameForJs = $_SESSION['username'];
            }
        ?>
        const loggedInUsername = <?php echo json_encode($loggedInUsernameForJs); ?>;

        // Predefined Q&A pairs for the bot
        const qaPairs = [
            { question: "What services do you offer?", answer: "We offer bus, train, and flight ticket booking services across India." },
            { question: "How can I book a ticket?", answer: "You can book a ticket by using the search widget at the top of the page. Select your mode of transport, enter details, and click search." },
            { question: "Can I cancel my ticket?", answer: "Yes, you can cancel your ticket through 'My Bookings' section, subject to our cancellation policy. Please refer to the Refund Policy for details." },
            { question: "How do I contact customer support?", answer: "Our customer support is available 24/7. You can reach us via phone: +91 98765 43210 or email: support@sairkaro.com, or directly through this chat." },
            { question: "What payment methods are accepted?", answer: "We accept various payment methods including credit/debit cards, net banking, and popular UPI apps." },
            { question: "Do you have any offers?", answer: "Yes, please check the 'Exclusive Offers & Deals' section on our homepage for the latest discounts and promotions!" },
            { question: "How do I reset my password?", answer: "You can reset your password by going to the login page and clicking on the 'Forgot Password' link." },
            { question: "Where can I find my bookings?", answer: "All your bookings can be found under the 'My Bookings' section in your user profile after you log in." },
            { question: "Is Sair Karo available on mobile?", answer: "Yes, our website is fully responsive and optimized for mobile devices. We also have plans for a dedicated mobile app in the future!" },
            { question: "How to check train schedule?", answer: "You can check the train schedule by navigating to the 'Check Schedules' page from the main navigation menu." },
            { question: "Are e-tickets valid?", answer: "Yes, e-tickets are fully valid. You can show the digital copy on your mobile device during boarding." },
            { question: "Can I get a refund for a cancelled ticket?", answer: "Refunds for cancelled tickets are processed according to our Refund Policy. The amount will be credited to your original payment method within 5-7 business days." },
            { question: "What are the baggage rules for flights?", answer: "Baggage rules vary by airline and ticket class. Please check the specific airline's website or your ticket details for precise information." }
        ];

        // Function to add a message to the chat window
        function addMessage(sender, text) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('chat-message');
            if (sender === 'user') {
                messageDiv.classList.add('user-message');
            } else {
                messageDiv.classList.add('bot-message');
            }
            messageDiv.textContent = text;
            chatMessagesDiv.appendChild(messageDiv);
            // Scroll to the bottom of the chat
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
        }

        // Function to handle user queries
        function handleUserQuery(query) {
            let botResponse = "I'm sorry, I don't understand that question. Please try rephrasing or choose from the quick questions.";
            const normalizedQuery = query.toLowerCase().trim();

            for (const qa of qaPairs) {
                if (normalizedQuery.includes(qa.question.toLowerCase()) || qa.question.toLowerCase().includes(normalizedQuery)) {
                    botResponse = qa.answer;
                    break;
                }
            }
            // Simulate typing delay
            setTimeout(() => {
                addMessage('bot', botResponse);
            }, 500);
        }

        // Event listener for quick question buttons
        quickQuestionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const question = this.dataset.question;
                addMessage('user', question);
                handleUserQuery(question);
            });
        });

        // Event listener for send button
        if (sendChatButton) {
            sendChatButton.addEventListener('click', function() {
                const query = chatInput.value.trim();
                if (query) {
                    addMessage('user', query);
                    handleUserQuery(query);
                    chatInput.value = ''; // Clear input field
                }
            });
        }

        // Event listener for Enter key in chat input
        if (chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendChatButton.click(); // Trigger send button click
                }
            });
        }


        // Toggle chat widget visibility
        if (chatButton && chatWidgetContainer) {
            chatButton.addEventListener('click', function() {
                if (chatWidgetContainer.style.display === 'flex') {
                    chatWidgetContainer.style.display = 'none';
                    chatButton.innerHTML = '<i class="fas fa-comment-dots"></i>'; // Change icon back to chat
                } else {
                    chatWidgetContainer.style.display = 'flex';
                    chatButton.innerHTML = '<i class="fas fa-times"></i>'; // Change icon to close
                    // Ensure widget is within viewport if it was dragged off-screen
                    centerWidgetIfOffscreen();
                    chatInput.focus(); // Focus on input when chat opens
                }
            });
        }

        // Close chat widget from inside
        if (closeChatWidgetButton && chatWidgetContainer && chatButton) {
            closeChatWidgetButton.addEventListener('click', function() {
                chatWidgetContainer.style.display = 'none';
                chatButton.innerHTML = '<i class="fas fa-comment-dots"></i>'; // Change icon back to chat
            });
        }

        // Make chat widget draggable
        if (chatWidgetHeader && chatWidgetContainer) {
            chatWidgetHeader.addEventListener('mousedown', (e) => {
                isDragging = true;
                chatWidgetContainer.classList.add('dragging');
                offsetX = e.clientX - chatWidgetContainer.getBoundingClientRect().left;
                offsetY = e.clientY - chatWidgetContainer.getBoundingClientRect().top;
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                e.preventDefault(); // Prevent text selection etc.
                const newX = e.clientX - offsetX;
                const newY = e.clientY - offsetY;

                // Keep within viewport boundaries
                const maxX = window.innerWidth - chatWidgetContainer.offsetWidth;
                const maxY = window.innerHeight - chatWidgetContainer.offsetHeight;

                chatWidgetContainer.style.left = `${Math.max(0, Math.min(newX, maxX))}px`;
                chatWidgetContainer.style.top = `${Math.max(0, Math.min(newY, maxY))}px`;
                chatWidgetContainer.style.right = 'auto'; // Disable right/bottom positioning when dragging
                chatWidgetContainer.style.bottom = 'auto';
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
                chatWidgetContainer.classList.remove('dragging');
            });

            // For touch devices (drag)
            chatWidgetHeader.addEventListener('touchstart', (e) => {
                isDragging = true;
                chatWidgetContainer.classList.add('dragging');
                const touch = e.touches[0];
                offsetX = touch.clientX - chatWidgetContainer.getBoundingClientRect().left;
                offsetY = touch.clientY - chatWidgetContainer.getBoundingClientRect().top;
                e.preventDefault(); // Prevent scrolling
            });

            document.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                const touch = e.touches[0];
                const newX = touch.clientX - offsetX;
                const newY = touch.clientY - offsetY;

                const maxX = window.innerWidth - chatWidgetContainer.offsetWidth;
                const maxY = window.innerHeight - chatWidgetContainer.offsetHeight;

                chatWidgetContainer.style.left = `${Math.max(0, Math.min(newX, maxX))}px`;
                chatWidgetContainer.style.top = `${Math.max(0, Math.min(newY, maxY))}px`;
                chatWidgetContainer.style.right = 'auto';
                chatWidgetContainer.style.bottom = 'auto';
                e.preventDefault();
            });

            document.addEventListener('touchend', () => {
                isDragging = false;
                chatWidgetContainer.classList.remove('dragging');
            });
        }

        // Function to reposition widget if it goes off screen after resize
        function centerWidgetIfOffscreen() {
            if (chatWidgetContainer.style.display === 'flex') {
                const rect = chatWidgetContainer.getBoundingClientRect();
                if (rect.right > window.innerWidth || rect.bottom > window.innerHeight || rect.left < 0 || rect.top < 0) {
                    // Reset to default position (bottom-right) or center it
                    chatWidgetContainer.style.left = 'auto';
                    chatWidgetContainer.style.top = 'auto';
                    chatWidgetContainer.style.right = '30px';
                    chatWidgetContainer.style.bottom = '100px';
                }
            }
        }
        window.addEventListener('resize', centerWidgetIfOffscreen);

        // Set initial welcome message with username
        const initialBotMessageElement = chatMessagesDiv.querySelector('.bot-message');
        if (initialBotMessageElement) {
            initialBotMessageElement.textContent = `Hello, ${loggedInUsername}! I'm Sair Karo Bot. How can I help you today?`;
        }

    });
</script>
</body>
</html>
