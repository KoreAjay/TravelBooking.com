<?php
include 'include/db.php'; // Ensure this path is correct
session_start();

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim(mysqli_real_escape_string($conn, $_POST['name']));
    $city = trim(mysqli_real_escape_string($conn, $_POST['city']));
    $feedback = trim(mysqli_real_escape_string($conn, $_POST['feedback']));

    if ($name && $city && $feedback) {
        $query = "INSERT INTO feedback (name, city, feedback) VALUES ('$name', '$city', '$feedback')";
        if (mysqli_query($conn, $query)) {
            $success = "🎉 Thanks for your feedback!";
            // Redirect to home.php after 2 seconds
            header("refresh:2;url=home.php");
            exit(); // Always exit after a header redirect
        } else {
            $error = "❌ Something went wrong. Please try again later. Error: " . mysqli_error($conn); // Added mysqli_error for debugging
        }
    } else {
        $error = "⚠️ All fields are required. Please fill them out.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Feedback - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variables from home.php for consistency */
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
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--heading-font);
            color: var(--dark-text);
        }

        /* --- Navbar Styling (copied from home.php) --- */
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
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* --- Main Content Styling --- */
        .feedback-container {
            margin-top: 50px; /* Adjust based on navbar height */
            margin-bottom: 50px;
            max-width: 700px;
            background: var(--white-color);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .feedback-container h3 {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary-color); /* Match theme red */
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }
        .feedback-container h3::after {
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

        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #ced4da;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25); /* Primary color with transparency */
            outline: none;
        }

        .btn-submit-feedback {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-submit-feedback:hover {
            background-color: #c82333; /* Darker red */
            border-color: #c82333;
            transform: translateY(-2px);
        }
        .btn-submit-feedback:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 10px;
            font-weight: 500;
            padding: 15px 20px;
            margin-bottom: 25px;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- Footer Styling (copied from home.php) --- */
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

        /* Responsive Adjustments */
        @media (max-width: 767.98px) {
            body { padding-top: 60px; }
            .navbar-brand { font-size: 1.5rem; }
            .navbar-collapse { background-color: var(--primary-color); padding: 15px 0; }
            .nav-link { margin: 5px 0; text-align: center; }
            .feedback-container { margin-top: 30px; padding: 25px; }
            .feedback-container h3 { font-size: 1.8rem; margin-bottom: 20px; }
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
                <li class="nav-item"><a class="nav-link" href="check_schedule.php">Check Schedules</a></li>
                <li class="nav-item"><a class="nav-link" href="my_bookings.php">My Bookings</a></li>
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="feedback.php">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                <?php if (!isset($_SESSION['user_id'])): // Check if user is NOT logged in ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-2 px-3 mt-2 mt-lg-0" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-warning ms-lg-2 px-3 text-dark mt-2 mt-lg-0" href="signup.php">Signup</a></li>
                <?php else: // If user IS logged in ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-2 px-3 mt-2 mt-lg-0" href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container feedback-container">
    <h3 class="mb-4">📝 Submit Your Feedback</h3>

    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center justify-content-center" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <div><?= $success ?></div>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger d-flex align-items-center justify-content-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div class="mb-3">
            <label for="name" class="form-label">Your Name</label>
            <input type="text" name="name" id="name" class="form-control" required placeholder="Enter your name">
        </div>
        <div class="mb-3">
            <label for="city" class="form-label">Your City</label>
            <input type="text" name="city" id="city" class="form-control" required placeholder="E.g., Pune, Mumbai">
        </div>
        <div class="mb-4">
            <label for="feedback" class="form-label">Your Feedback</label>
            <textarea name="feedback" id="feedback" rows="5" class="form-control" required placeholder="Share your experience or suggestions..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-submit-feedback w-100">📨 Send Feedback</button>
    </form>
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
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms & Conditions</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Contact Info</h5>
                <p><i class="fas fa-map-marker-alt me-2"></i> 123 Travel Lane, City, Country</p>
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