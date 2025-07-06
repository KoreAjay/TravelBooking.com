<?php
include 'include/db.php'; // Ensure this connects to your database
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info from 'users' table
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    // Handle error if statement preparation fails
    error_log("Failed to prepare user fetch statement: " . mysqli_error($conn));
    $user = null; // Ensure $user is null if there's an issue
}

// --- Fetch profile image path from 'profile_images' table ---
$profile_image_path = 'assets/images/default_user.png'; // Default image

if ($user) { // Only try to fetch image if user data was successfully retrieved
    $sql_profile_img = "SELECT image_path FROM profile_images WHERE user_id = ?";
    $stmt_profile_img = mysqli_prepare($conn, $sql_profile_img);
    if ($stmt_profile_img) {
        mysqli_stmt_bind_param($stmt_profile_img, "i", $user_id);
        mysqli_stmt_execute($stmt_profile_img);
        $result_profile_img = mysqli_stmt_get_result($stmt_profile_img);
        if ($profile_img_row = mysqli_fetch_assoc($result_profile_img)) {
            // Check if the file actually exists on the server
            // This is crucial to prevent broken image icons if the DB path is wrong or file deleted
            if (file_exists($profile_img_row['image_path']) && !is_dir($profile_img_row['image_path'])) {
                   $profile_image_path = htmlspecialchars($profile_img_row['image_path']);
            }
        }
        mysqli_stmt_close($stmt_profile_img);
    } else {
        error_log("Failed to prepare profile image fetch statement: " . mysqli_error($conn));
    }
}
// -----------------------------------------------------------


// Count bookings
$total_bookings = 0; // Initialize
if ($user) { // Only count bookings if user data was successfully retrieved
    $booking_sql = "SELECT COUNT(*) AS total FROM (
        SELECT id FROM train_bookings WHERE user_id = ?
        UNION ALL
        SELECT id FROM bus_bookings WHERE user_id = ?
        UNION ALL
        SELECT id FROM plane_bookings WHERE user_id = ?
    ) AS all_bookings";

    $stmt = mysqli_prepare($conn, $booking_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $user_id);
        mysqli_stmt_execute($stmt);
        $booking_result = mysqli_stmt_get_result($stmt);
        $total_bookings = mysqli_fetch_assoc($booking_result)['total'];
        mysqli_stmt_close($stmt);
    } else {
        error_log("Failed to prepare booking count statement: " . mysqli_error($conn));
    }
}

// If user data couldn't be fetched, redirect or show error
if (!$user) {
    // Optionally, handle this more gracefully, e.g., show an error message
    // For now, redirecting back to login or a generic error page
    header("Location: login.php?error=user_not_found");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>User Profile - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variables for easy color management - RED THEME */
        :root {
            --primary-color: #E53935; /* A strong, material design red */
            --primary-dark: #C62828; /* Darker red for hover/active */
            --secondary-color: #6c757d; /* Keep secondary text grey */
            --light-bg: #f8f9fa; /* Off-white for general background */
            --dark-text: #343a40; /* Dark grey for main text */
            --white-color: #ffffff;
            --accent-color: #FFC107; /* Vibrant yellow/orange - good contrast with red */
            --accent-dark: #E0A800; /* Darker accent for hover */
            --danger-color: #dc3545; /* Standard red for danger actions */
            --info-light: #FFEBEE; /* Very light red for backgrounds (Material Red 50) */

            /* Footer specific colors */
            --footer-bg: #2C0F0E; /* Very dark, almost black, red-tinted for footer */
            --footer-text: #F0E0E0; /* Off-white for footer text on dark background */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light-bg) 0%, #E9EDF2 100%); /* Softer gradient background, subtle light greyish-blue */
            min-height: 100vh;
            display: flex;
            flex-direction: column; /* Allows footer to stick to bottom */
            padding-top: 70px; /* Space for fixed navbar */
        }

        /* Navbar Styling (consistent with homepage) */
        .navbar {
            background-color: var(--primary-color) !important; /* Primary Red */
            padding: 0.85rem 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 2rem;
            display: flex;
            align-items: center;
            color: var(--white-color) !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand img {
            height: 45px;
            margin-right: 12px;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
        }

        .navbar-brand:hover {
            color: var(--accent-color) !important; /* Accent Yellow */
            transform: translateY(-2px);
        }

        .nav-link {
            font-weight: 500;
            color: var(--white-color) !important;
            margin: 0 10px;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            position: relative;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--accent-color) !important; /* Accent Yellow */
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        /* Active link indicator */
        .nav-link.active::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 5px;
            transform: translateX(-50%);
            width: 70%;
            height: 3px;
            background-color: var(--accent-color); /* Accent Yellow */
            border-radius: 2px;
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

        .container-main {
            flex: 1; /* Pushes footer to the bottom */
            margin-top: 40px;
            padding-bottom: 60px;
        }

        /* Profile Hero Section */
        .profile-hero {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--primary-dark) 100%); /* Red gradient */
            color: var(--white-color);
            padding: 50px 0;
            margin-bottom: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-align: center;
        }
        .profile-hero .profile-image-lg {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 6px solid rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
        }
        .profile-hero h2 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .profile-hero p {
            font-size: 1.1rem;
            margin-bottom: 5px;
            opacity: 0.9;
        }
        .profile-hero .location {
            font-size: 0.95rem;
            opacity: 0.8;
        }

        /* Profile Details Card */
        .profile-details-card {
            background: var(--white-color);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 30px;
            margin-bottom: 30px;
        }
        .profile-details-card h4 {
            color: var(--primary-dark); /* Darker red for heading */
            font-weight: 700;
        }
        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed rgba(0,0,0,0.08);
        }
        .detail-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        .detail-item i {
            color: var(--primary-color); /* Primary Red for icons */
            font-size: 1.3rem;
            margin-right: 20px;
            width: 25px; /* Fixed width for alignment */
            text-align: center;
        }
        .detail-item strong {
            color: var(--dark-text);
            font-weight: 600;
            min-width: 90px; /* Align content */
        }
        .detail-item span {
            color: var(--secondary-color);
            flex-grow: 1;
        }

        /* Stats Section */
        .stats-section {
            display: flex;
            justify-content: space-around;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .stats-box {
            background-color: var(--info-light); /* Light red background */
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            flex: 1; /* Distribute space evenly */
            min-width: 200px; /* Minimum width before wrapping */
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stats-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stats-box h4 {
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 2.5rem;
            color: var(--primary-color); /* Primary Red */
        }
        .stats-box p {
            margin: 0;
            font-size: 1.1rem;
            color: var(--dark-text);
        }
        .stats-box i {
            font-size: 2.5rem;
            color: var(--accent-color); /* Accent Yellow */
            margin-bottom: 15px;
        }

        /* Action Buttons */
        .profile-actions {
            margin-top: 40px;
            text-align: center;
        }
        .btn-profile-action {
            padding: 12px 30px;
            border-radius: 30px; /* Pill shape */
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        .btn-edit-profile {
            background-color: var(--primary-color); /* Primary Red */
            color: var(--white-color);
            border: 2px solid var(--primary-color);
            box-shadow: 0 4px 15px rgba(229, 57, 53, 0.2); /* Red shadow */
        }
        .btn-edit-profile:hover {
            background-color: var(--primary-dark); /* Darker Red */
            border-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(198, 40, 40, 0.3); /* Darker red shadow */
        }
        .btn-view-bookings {
            background-color: var(--white-color);
            color: var(--primary-color); /* Primary Red */
            border: 2px solid var(--primary-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        .btn-view-bookings:hover {
            background-color: var(--primary-color); /* Primary Red */
            color: var(--white-color);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(229, 57, 53, 0.3); /* Red shadow */
        }


        /* Footer (consistent with homepage) */
        .main-footer {
            background-color: var(--footer-bg); /* Dark Red-tinted footer */
            color: var(--footer-text); /* Off-white footer text */
            padding: 60px 0 30px;
            margin-top: auto;
            font-size: 0.95rem;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.15);
        }

        .main-footer h5 {
            color: var(--white-color); /* White for footer headings */
            font-weight: 700;
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
            background-color: var(--accent-color); /* Accent Yellow */
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
            transition: color 0.3s ease, transform 0.3s ease;
            display: inline-block;
        }
        .main-footer ul li a:hover {
            color: var(--accent-color); /* Accent Yellow */
            transform: translateX(5px);
        }
        .main-footer .social-icons a {
            color: var(--white-color); /* White for footer social icons */
            font-size: 1.8rem;
            margin-right: 18px;
            transition: color 0.3s ease, transform 0.3s ease;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .main-footer .social-icons a:hover {
            color: var(--accent-color); /* Accent Yellow */
            transform: translateY(-5px) scale(1.1);
        }
        .main-footer .copyright {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 25px;
            margin-top: 40px;
            text-align: center;
            color: var(--footer-text);
            font-size: 0.88rem;
            opacity: 0.9;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .profile-hero {
                padding: 40px 0;
            }
            .profile-hero h2 {
                font-size: 2rem;
            }
            .profile-hero p {
                font-size: 1.1rem;
            }
            .profile-details-card {
                padding: 25px;
            }
            .stats-section {
                flex-direction: column; /* Stack stats boxes on smaller screens */
                align-items: center;
            }
            .stats-box {
                width: 80%; /* Make them take more width when stacked */
                margin-bottom: 20px;
            }
            .profile-actions .btn-profile-action { /* Adjusted to target the specific class */
                width: 100%;
                margin: 10px 0;
            }
            .main-footer h5 {
                text-align: center;
            }
            .main-footer h5::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .main-footer ul, .main-footer .social-icons {
                text-align: center;
                margin-bottom: 25px;
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
                padding: 0.5rem 1rem;
            }
            .nav-link.active::after {
                bottom: 3px;
                width: 50%;
            }
            .profile-hero {
                padding: 30px 0;
                margin-bottom: 30px;
            }
            .profile-hero .profile-image-lg {
                width: 120px;
                height: 120px;
            }
            .profile-hero h2 {
                font-size: 1.8rem;
            }
            .profile-hero p {
                font-size: 1rem;
            }
            .profile-details-card {
                padding: 20px;
            }
            .detail-item i {
                font-size: 1.1rem;
                margin-right: 15px;
            }
            .detail-item strong {
                min-width: 70px;
            }
            .stats-box h4 {
                font-size: 2rem;
            }
            .stats-box p {
                font-size: 1rem;
            }
            .stats-box i {
                font-size: 2rem;
            }
            .btn-profile-action {
                font-size: 1rem;
                padding: 10px 20px;
            }
            .main-footer {
                padding: 40px 0 20px;
            }
            .main-footer .social-icons a {
                margin: 0 10px;
                font-size: 1.5rem;
            }
            .main-footer .copyright {
                margin-top: 25px;
                padding-top: 15px;
            }
        }
        @media (max-width: 575.98px) {
            .profile-hero h2 {
                font-size: 1.5rem;
            }
            .profile-hero p {
                font-size: 0.9rem;
            }
            .profile-details-card {
                padding: 15px;
            }
            .stats-box {
                min-width: unset; /* Remove min-width to allow more flexibility */
                width: 100%; /* Ensure full width on very small screens */
            }
            .btn-profile-action {
                font-size: 0.9rem;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
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
                        <a class="nav-link" href="check_schedule.php"><i class="fas fa-calendar-alt me-1"></i> Schedules</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_bookings.php"><i class="fas fa-ticket-alt me-1"></i> My Bookings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="profile.php"><i class="fas fa-user-circle me-1"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php"><i class="fas fa-info-circle me-1"></i> About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><i class="fas fa-envelope me-1"></i> Contact Us</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="profile-hero">
            <img src="<?= $profile_image_path ?>" alt="Profile Photo" class="profile-image-lg">
            <h2>Hello, <?= htmlspecialchars($user['name'] ?? 'Traveler') ?>!</h2>
            <p><i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($user['email'] ?? 'N/A') ?></p>
            <p><i class="fas fa-phone-alt me-2"></i> <?= htmlspecialchars($user['mobile'] ?? 'N/A') ?></p>
            <span class="location"><i class="fas fa-map-marker-alt me-2"></i> Pimpri-Chinchwad, Maharashtra, India</span>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="profile-details-card mb-4">
                    <h4 class="mb-4 text-center">Your Details</h4> <div class="detail-item">
                        <i class="fas fa-user"></i> <strong>Name:</strong> <span><?= htmlspecialchars($user['name'] ?? 'Not set') ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-envelope"></i> <strong>Email:</strong> <span><?= htmlspecialchars($user['email'] ?? 'Not set') ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-phone-alt"></i> <strong>Mobile:</strong> <span><?= htmlspecialchars($user['mobile'] ?? 'Not set') ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i> <strong>Joined:</strong> <span><?= htmlspecialchars(date('d M Y', strtotime($user['created_at'] ?? 'now'))) ?></span>
                    </div>
                    </div>

                <div class="stats-section">
                    <div class="stats-box">
                        <i class="fas fa-ticket-alt"></i>
                        <h4><?= $total_bookings ?></h4>
                        <p>Total Bookings</p>
                    </div>
                </div>

                <div class="profile-actions d-flex justify-content-center flex-wrap mt-5">
                    <a href="edit_profile.php" class="btn btn-profile-action btn-edit-profile mb-2">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                    <a href="my_bookings.php" class="btn btn-profile-action btn-view-bookings mb-2">
                        <i class="fas fa-ticket-alt me-2"></i> View My Bookings
                    </a>
                </div>
            </div>
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