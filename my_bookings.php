<?php
include 'include/db.php'; // Make sure this path is correct for your database connection
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>My Bookings - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variables for easy color management (consistent with other pages) */
        :root {
            --primary-color:rgb(48, 56, 67); /* Bootstrap's default primary */
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --white-color: #ffffff;
            --accent-color: #ffc107; /* Bootstrap's warning for accent */
            --danger-color: #dc3545;
            --info-light: #e0f2fe; /* Lighter blue for backgrounds */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e0e9f0 100%); /* Softer gradient background */
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Allows footer to stick to bottom */
            padding-top: 70px; /* Space for fixed navbar */
        }

        /* Navbar Styling (consistent with homepage) */
        .navbar {
            background-color: #dc3545;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed; /* Keep navbar fixed */
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
            height: 40px; /* Adjust logo size */
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
            box-shadow: none; /* Remove focus outline */
        }

        .container-main {
            flex: 1; /* Pushes footer to the bottom */
            margin-top: 40px;
            padding-bottom: 60px;
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            font-size: 2.8rem; /* Slightly larger title */
            position: relative;
            padding-bottom: 15px;
        }
        .page-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 120px; /* Underline length */
            height: 5px; /* Underline thickness */
            background-color: var(--accent-color);
            border-radius: 3px;
        }


        /* Booking Card Design */
        .booking-card {
            border: none;
            border-radius: 20px; /* More rounded */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Stronger shadow */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
            background-color: var(--white-color);
            height: 100%; /* Ensure all cards in a row have equal height */
            display: flex;
            flex-direction: column;
            animation: slideInUp 0.6s ease-out forwards; /* Animation for cards */
            opacity: 0; /* Start hidden for animation */
        }
        /* No need for nth-child delays in CSS, handled by PHP now */


        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .booking-card:hover {
            transform: translateY(-10px); /* More pronounced lift */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2); /* Even stronger shadow on hover */
        }

        .card-header-custom {
            background: linear-gradient(90deg, var(--primary-color) 0%, #0a58ca 100%); /* Gradient header */
            color: var(--white-color);
            padding: 18px 25px;
            font-size: 1.4rem; /* Larger header text */
            font-weight: 700;
            border-bottom: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 20px 20px 0 0; /* Match card radius */
        }
        .card-header-custom .mode-label {
            display: flex;
            align-items: center;
        }
        .card-header-custom .mode-icon {
            font-size: 1.8rem; /* Larger icon */
            margin-right: 12px;
            color: var(--accent-color); /* Highlight icon */
        }
        .card-header-custom .pnr-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
        }
        .card-header-custom .pnr-value {
            font-size: 1.1rem;
            font-weight: 700;
        }

        .card-body-custom {
            padding: 25px;
            flex-grow: 1; /* Ensures body takes available space */
        }

        .booking-detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px; /* Consistent spacing */
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.08); /* Subtle separator */
        }
        .booking-detail-item:last-child {
            border-bottom: none; /* No border for the last item */
            margin-bottom: 0;
        }

        .booking-detail-item i {
            color: var(--primary-color); /* Icons in primary color */
            font-size: 1.2rem;
            margin-right: 15px;
            width: 25px; /* Fixed width for icon for alignment */
            text-align: center;
        }
        .booking-detail-item strong {
            color: var(--dark-text);
            font-weight: 600;
            min-width: 90px; /* Align content */
            display: inline-block;
        }
        .booking-detail-item span {
            color: var(--secondary-color);
            flex-grow: 1; /* Take remaining space */
        }

        .btn-cancel {
            background-color: var(--danger-color);
            border: none;
            padding: 12px 20px;
            font-weight: 600;
            border-radius: 10px; /* Match card rounding */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2); /* Shadow for cancel button */
        }

        .btn-cancel:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.3);
        }

        /* No Bookings Message */
        .no-bookings-message {
            text-align: center;
            padding: 60px 30px; /* More padding */
            background-color: var(--white-color);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 50px; /* More space from title */
            animation: fadeIn 0.8s ease-out; /* Match card animation */
        }
        .no-bookings-message h4 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        .no-bookings-message p {
            color: var(--secondary-color);
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .no-bookings-message .btn {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: var(--dark-text);
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 30px; /* Pill-shaped button */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 10px rgba(255, 193, 7, 0.3);
        }
        .no-bookings-message .btn:hover {
            background-color: #e0a800;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(255, 193, 7, 0.4);
        }

        /* Footer (consistent with homepage) */
        footer {
            background-color: var(--primary-color); /* This pulls the primary blue color */
            color: var(--white-color); /* This ensures text is white */
            padding: 25px 0;
            text-align: center;
            margin-top: auto; /* Pushes footer to the bottom */
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }
        /* Footer links */
        footer a {
            color: var(--white-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        footer a:hover {
            color: var(--accent-color);
        }

        /* Social icons in footer */
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
        @media (max-width: 991.98px) { /* Tablets and below */
            .page-title {
                font-size: 2.2rem;
                margin-top: 30px;
            }
            .card-header-custom {
                font-size: 1.2rem;
                padding: 15px 20px;
            }
            .card-header-custom .mode-icon {
                font-size: 1.5rem;
            }
            .card-header-custom .pnr-value {
                font-size: 1rem;
            }
            .card-body-custom {
                padding: 20px;
            }
            .booking-detail-item {
                font-size: 0.95rem;
            }
            .booking-card {
                margin-bottom: 25px; /* Add margin bottom to stacked cards */
            }
        }

        @media (max-width: 767.98px) { /* Small devices (phones) */
            body {
                padding-top: 56px; /* Adjust for smaller navbar height */
            }
            .navbar-brand {
                font-size: 1.5rem;
            }
            .navbar-brand img {
                height: 30px;
            }
            .page-title {
                font-size: 1.8rem;
                margin-bottom: 25px;
            }
            .page-title::after {
                width: 80px;
                height: 4px;
            }
            .card-header-custom {
                font-size: 1.1rem;
            }
            .card-header-custom .mode-icon {
                font-size: 1.3rem;
            }
            .card-body-custom {
                padding: 15px;
            }
            .booking-detail-item {
                font-size: 0.9rem;
                flex-wrap: wrap; /* Allow wrapping of items */
            }
            .booking-detail-item strong {
                min-width: 70px;
            }
            .booking-detail-item i {
                font-size: 1rem;
                margin-right: 10px;
            }
            .btn-cancel {
                font-size: 0.95rem;
                padding: 8px 15px;
            }
            .no-bookings-message {
                padding: 30px 20px;
                margin-top: 30px;
            }
            .no-bookings-message h4 {
                font-size: 1.5rem;
            }
            .no-bookings-message p {
                font-size: 1rem;
            }
            .no-bookings-message .btn {
                font-size: 1rem;
                padding: 10px 20px;
            }
        }
        
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="home.php">
                <img src="assets/images/logo2.png" alt="Sair Karo Logo"> Sair Karo
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="my_bookings.php"><i class="fas fa-ticket-alt me-1"></i> My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user-circle me-1"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <h2 class="page-title"><i class="fas fa-ticket-alt me-3"></i> My Travel Bookings</h2>
        <div class="row">

            <?php
            $all_tables = [
                ['name' => 'train_bookings', 'mode' => 'Train', 'icon' => 'fas fa-train'],
                ['name' => 'bus_bookings', 'mode' => 'Bus', 'icon' => 'fas fa-bus-alt'],
                ['name' => 'plane_bookings', 'mode' => 'Plane', 'icon' => 'fas fa-plane'],
            ];

            $hasBookings = false;
            $booking_count = 0; // Initialize a counter for animation delay

            foreach ($all_tables as $table) {
                // Prepared statement for security
                // Add error checking for prepare
                if (!($stmt = $conn->prepare("SELECT * FROM {$table['name']} WHERE user_id = ? ORDER BY created_at DESC"))) {
                    // Handle error - log it, display generic message, etc.
                    error_log("Prepare failed for {$table['name']}: " . $conn->error);
                    continue; // Skip to next table
                }

                $stmt->bind_param("i", $user_id);
                
                // Add error checking for execute
                if (!$stmt->execute()) {
                    error_log("Execute failed for {$table['name']}: " . $stmt->error);
                    $stmt->close();
                    continue; // Skip to next table
                }

                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $hasBookings = true;
                    while ($row = $result->fetch_assoc()) {
                        $animation_delay = ($booking_count * 0.1) . 's'; // Unique delay for each booking

                        // Safely get and format travel_date to prevent "Undefined array key" warning
                        $travel_date_display = 'N/A';
                        if (isset($row['travel_date']) && !empty($row['travel_date'])) {
                            $travel_date_display = date('D, d M Y', strtotime($row['travel_date']));
                        }

                        // Safely get and format travel_time to prevent "Undefined array key" warning
                        $travel_time_display = 'N/A';
                        if (isset($row['travel_time']) && !empty($row['travel_time'])) {
                            $travel_time_display = date('h:i A', strtotime($row['travel_time']));
                        }

                        echo '<div class="col-lg-6 col-md-12 mb-4">
                                <div class="card booking-card" style="animation-delay: ' . htmlspecialchars($animation_delay) . ';">
                                    <div class="card-header-custom">
                                        <div class="mode-label"><i class="' . htmlspecialchars($table['icon']) . ' mode-icon"></i> ' . htmlspecialchars($table['mode']) . ' Ticket</div>
                                        <div class="pnr-label">PNR: <span class="pnr-value">' . htmlspecialchars($row['pnr_number'] ?? 'N/A') . '</span></div>
                                    </div>
                                    <div class="card-body-custom">
                                        <div class="booking-info">
                                            <div class="booking-detail-item"><i class="fas fa-calendar-alt"></i> <strong>Date:</strong> <span>' . htmlspecialchars($travel_date_display) . '</span></div>
                                            <div class="booking-detail-item"><i class="fas fa-clock"></i> <strong>Time:</strong> <span>' . htmlspecialchars($travel_time_display) . '</span></div>
                                            <div class="booking-detail-item"><i class="fas fa-map-marker-alt"></i> <strong>From:</strong> <span>' . htmlspecialchars($row['from_city'] ?? 'N/A') . '</span></div>
                                            <div class="booking-detail-item"><i class="fas fa-map-marked-alt"></i> <strong>To:</strong> <span>' . htmlspecialchars($row['to_city'] ?? 'N/A') . '</span></div>';

                        // Conditional details based on mode
                        if ($table['mode'] === 'Train') {
                            echo '<div class="booking-detail-item"><i class="fas fa-chair"></i> <strong>Coach:</strong> <span>' . htmlspecialchars($row['coach'] ?? 'N/A') . '</span></div>';
                            echo '<div class="booking-detail-item"><i class="fas fa-person-booth"></i> <strong>Seat:</strong> <span>' . htmlspecialchars($row['seat_number'] ?? 'N/A') . ' (' . htmlspecialchars($row['seat_preference'] ?? '') . ')</span></div>';
                        } elseif ($table['mode'] === 'Plane') {
                            echo '<div class="booking-detail-item"><i class="fas fa-plane-departure"></i> <strong>Class:</strong> <span>' . htmlspecialchars($row['class'] ?? 'N/A') . '</span></div>';
                            echo '<div class="booking-detail-item"><i class="fas fa-person-booth"></i> <strong>Seat:</strong> <span>' . htmlspecialchars($row['seat_number'] ?? 'N/A') . ' (' . htmlspecialchars($row['seat_preference'] ?? '') . ')</span></div>';
                        } else { // Bus
                            echo '<div class="booking-detail-item"><i class="fas fa-person-booth"></i> <strong>Seat:</strong> <span>' . htmlspecialchars($row['seat_number'] ?? 'N/A') . ' (' . htmlspecialchars($row['seat_preference'] ?? '') . ')</span></div>';
                        }

                        echo '             <div class="booking-detail-item"><i class="fas fa-money-check-alt"></i> <strong>Paid Via:</strong> <span>' . htmlspecialchars(ucfirst($row['payment_method'] ?? 'N/A')) . '</span></div>
                                        </div>
                                        <form method="POST" action="cancel_ticket.php" onsubmit="return confirm(\'Are you sure you want to cancel this ticket? This action cannot be undone.\');">
                                            <input type="hidden" name="booking_id" value="' . htmlspecialchars($row['id']) . '">
                                            <input type="hidden" name="mode" value="' . htmlspecialchars($table['mode']) . '">
                                            <button type="submit" class="btn btn-cancel w-100 mt-4"><i class="fas fa-times-circle me-2"></i> Cancel Ticket</button>
                                        </form>
                                    </div>
                                </div>
                            </div>';
                        $booking_count++; // Increment counter for next booking
                    }
                }
                $stmt->close(); // Close the statement for each table
            }

            if (!$hasBookings) {
                echo '<div class="col-12">
                        <div class="no-bookings-message">
                            <h4><i class="fas fa-info-circle me-2"></i> No Bookings Found Yet!</h4>
                            <p>It looks like you haven\'t booked any travel with Sair Karo. Start your journey today!</p>
                            <a href="home.php" class="btn"><i class="fas fa-search me-2"></i> Find Your Next Trip</a>
                        </div>
                    </div>';
            }
            ?>

        </div>
    </div>

    <footer class="main-footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>About Sair Karo</h5>
                    <p>Sair Karo is your trusted partner for hassle-free travel management. We offer seamless booking for trains, buses, and flights, ensuring comfort and convenience every step of your journey.</p>
                </div>

                <div class="col-md-2 offset-md-1 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="home.php">Home</a></li>
                        <li><a href="check_schedule.php">Check Schedules</a></li>
                        <li><a href="my_bookings.php">My Bookings</a></li>
                        <li><a href="feedback.php">Feedback</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="about.php">About Us</a></li>
                    </ul>
                </div>

                <div class="col-md-3 mb-4 mb-md-0">
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
</body>
</html>