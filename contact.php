<?php
// Start session at the very beginning of the file, BEFORE any HTML or whitespace.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// You can include your database connection here if needed for form processing,
// but for the contact page, it's usually not direct database interaction on submission.
// include 'include/db.php'; // Uncomment if you need DB connection for contact form submission to a DB.

// Simple form submission handler (for demonstration)
$message_status = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name'] ?? '');
    $email = htmlspecialchars($_POST['email'] ?? '');
    $phone = htmlspecialchars($_POST['phone'] ?? '');
    $message = htmlspecialchars($_POST['message'] ?? '');

    // Basic validation
    if (empty($name) || empty($email) || empty($phone) || empty($message)) {
        $message_status = 'All fields are required.';
        $message_type = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message_status = 'Invalid email format.';
        $message_type = 'danger';
    } else {
        // In a real application, you would send this data to an email, database, etc.
        // For now, we'll just simulate success.
        // Example: mail('info@sairkaro.com', 'New Contact Form Submission', $message, "From: $email");

        $message_status = 'Your message has been sent successfully! We will get back to you shortly.';
        $message_type = 'success';

        // Clear form fields after successful submission (optional)
        $_POST = array(); // Clear $_POST to reset form on refresh
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Sair Karo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variables for easy color management - UPDATED to Red */
        :root {
            --primary-color: #dc3545; /* Red */
            --primary-hover-color: #c82333; /* Darker Red for hover */
            --primary-focus-shadow: rgba(220, 53, 69, 0.25); /* Lighter red with transparency for focus */

            --secondary-color: #6c757d; /* Standard Grey */
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --white-color: #ffffff;
            --accent-color: #ffc107; /* Bright Yellow/Orange - remains as accent */
            --heading-font: 'Poppins', sans-serif;
            --body-font: 'Poppins', sans-serif;

            /* Specific colors for navbar/footer if they are part of a shared layout */
            --footer-bg: #212529; /* Original Dark Grey for footer */
            --footer-text: #adb5bd; /* Light Grey for footer text */
            --call-to-action-bg: #fd7e14; /* Strong Orange - For specific banners/CTAs */
        }

        body {
            font-family: var(--body-font);
            background-color: var(--light-bg);
            color: var(--dark-text);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar if it exists */
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
        a, .btn, .form-control, .form-select, .card {
            transition: all 0.3s ease-in-out;
        }

        /* --- Navbar Styling (Included for consistency with other pages) --- */
        .navbar {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background-color: var(--primary-color) !important; /* Uses new primary color */
            padding: 0.75rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 2rem;
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
            height: 45px;
            margin-right: 12px;
            filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
        }

        .nav-link {
            font-weight: 500;
            color: var(--white-color) !important;
            margin: 0 12px;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
        }

        .nav-link:hover, .nav-link.active {
            color: var(--accent-color) !important;
            background-color: rgba(255, 255, 255, 0.1);
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

        /* --- Main Content Area (Contact Section) --- */
        .contact-section {
            flex: 1; /* Allows section to grow and push footer down */
            padding: 80px 0; /* Increased top/bottom padding */
            background: linear-gradient(rgba(220, 53, 69, 0.05), rgba(220, 53, 69, 0.02)), var(--light-bg); /* Subtle red tint on background */
        }

        .contact-box {
            max-width: 980px; /* Slightly wider for a more spacious feel */
            margin: 0 auto; /* Center the box */
            background: var(--white-color);
            padding: 50px; /* More generous padding */
            border-radius: 20px; /* More rounded */
            box-shadow: 0 15px 40px rgba(0,0,0,0.1); /* Softer, larger shadow */
            display: flex;
            flex-wrap: wrap; /* Allow columns to wrap on smaller screens */
            gap: 50px; /* More space between info and form */
            border: 1px solid rgba(0,0,0,0.05); /* Subtle border */
        }

        .contact-info {
            flex: 1; /* Allows it to take available space */
            min-width: 300px; /* Minimum width before wrapping */
            padding-right: 25px; /* Space from form */
        }
        .contact-info h3 {
            color: var(--primary-color);
            font-weight: 700; /* Bolder */
            margin-bottom: 30px; /* More margin */
            font-size: 2.2rem; /* Larger heading */
            position: relative;
            padding-bottom: 15px;
        }
        .contact-info h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 70px; /* Wider underline */
            height: 4px; /* Thicker underline */
            background-color: var(--accent-color);
            border-radius: 2px;
        }

        .contact-info p {
            font-size: 1.05rem;
            line-height: 1.8;
            color: var(--secondary-color);
            margin-bottom: 35px; /* More margin */
            max-width: 90%;
        }
        .contact-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .contact-info ul li {
            margin-bottom: 20px; /* More space between items */
            display: flex;
            align-items: flex-start; /* Align icon to top of multi-line text */
            color: var(--dark-text);
            font-size: 1.1rem; /* Slightly larger text */
        }
        .contact-info ul li i {
            color: var(--primary-color); /* Icons use primary color */
            margin-right: 20px; /* More margin */
            font-size: 1.5rem; /* Larger icons */
            width: 30px; /* Fixed width for icons for better alignment */
            text-align: center;
        }
        .contact-info ul li a {
            color: var(--dark-text);
            text-decoration: none;
            word-break: break-all; /* Prevent long emails/phones from breaking layout */
        }
        .contact-info ul li a:hover {
            color: var(--primary-color);
        }

        .contact-info .social-icons {
            margin-top: 40px; /* More margin */
        }
        .contact-info .social-icons h5 {
            color: var(--dark-text);
            font-weight: 700;
            margin-bottom: 20px; /* More margin */
            font-size: 1.2rem;
        }
        .contact-info .social-icons a {
            color: var(--primary-color); /* Social icons use primary color */
            font-size: 2rem; /* Larger icons */
            margin-right: 25px; /* More space */
            text-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .contact-info .social-icons a:hover {
            color: var(--accent-color); /* Hover remains accent */
            transform: translateY(-5px) scale(1.1); /* More pronounced lift and scale */
        }

        .contact-form {
            flex: 1.8; /* Allows it to take more space than info */
            min-width: 400px; /* Minimum width before wrapping */
        }
        .contact-form h2 {
            color: var(--dark-text); /* Form heading in dark text for contrast */
            font-weight: 800; /* Extra bold */
            text-align: left;
            margin-bottom: 40px; /* More margin */
            font-size: 2.5rem; /* Larger heading */
        }
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 10px; /* More margin */
            display: block;
            font-size: 1.05rem;
        }
        .form-control {
            border-radius: 12px; /* More rounded */
            padding: 14px 20px; /* More padding */
            border: 1px solid #ced4da;
            font-size: 1.05rem;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); /* Subtle inner shadow */
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem var(--primary-focus-shadow), inset 0 1px 3px rgba(0,0,0,0.05); /* Stronger, softer focus shadow */
            outline: none;
        }
        textarea.form-control {
            min-height: 150px; /* Ensure textarea has a decent height */
            resize: vertical;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 16px 35px; /* More padding */
            font-weight: 700; /* Bolder */
            border-radius: 12px; /* More rounded */
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1.2rem; /* Larger text */
            box-shadow: 0 6px 20px rgba(0,0,0,0.15); /* Button shadow */
        }
        .btn-primary:hover {
            background-color: var(--primary-hover-color); /* Darker red on hover */
            border-color: var(--primary-hover-color);
            transform: translateY(-3px); /* More pronounced lift */
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
        }
        .alert {
            margin-bottom: 30px; /* More margin */
            border-radius: 12px;
            font-weight: 500;
            padding: 18px 25px;
            font-size: 1.05rem;
        }
        .text-danger {
            font-weight: 600; /* Ensure the asterisk is visible and stands out */
        }

        /* --- Footer Styling (Included for consistency with other pages) --- */
        .main-footer {
            background: var(--footer-bg); /* Uses original dark grey */
            color: var(--footer-text);
            padding: 70px 0 40px; /* More padding */
            margin-top: auto;
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
            .contact-box {
                padding: 40px;
                gap: 30px;
            }
            .contact-info h3 {
                font-size: 2rem;
            }
            .contact-form h2 {
                font-size: 2.2rem;
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
            .contact-section {
                padding: 40px 0;
            }
            .contact-box {
                flex-direction: column; /* Stack info and form on small screens */
                padding: 30px;
                margin: 20px auto;
                width: 95%; /* Adjust width for smaller screens */
                gap: 30px;
            }
            .contact-info {
                padding-right: 0; /* Remove right padding when stacked */
                margin-bottom: 20px; /* Space between stacked sections */
                text-align: center; /* Center content on small screens */
            }
            .contact-info h3 {
                font-size: 1.8rem;
            }
            .contact-info h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
            .contact-info p {
                max-width: 100%;
            }
            .contact-info ul li {
                justify-content: center; /* Center list items */
                text-align: left; /* Keep text alignment for multi-line */
            }
            .contact-info ul li i {
                margin-right: 15px; /* Adjust icon margin */
            }
            .contact-info .social-icons {
                text-align: center;
            }
            .contact-info .social-icons a {
                margin: 0 10px;
                font-size: 1.8rem;
            }
            .contact-form h2 {
                text-align: center; /* Center form heading on small screens */
                font-size: 2rem;
                margin-bottom: 30px;
            }
            .btn-primary {
                padding: 14px 25px;
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
            .contact-box {
                padding: 25px;
            }
            .contact-info h3 {
                font-size: 1.6rem;
            }
            .contact-info p {
                font-size: 0.95rem;
            }
            .contact-info ul li {
                font-size: 1rem;
            }
            .contact-info ul li i {
                font-size: 1.3rem;
            }
            .contact-form h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

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
                <li class="nav-item"><a class="nav-link active" aria-current="page" href="contact.php">Contact Us</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                <?php if (isset($_SESSION) && !isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-2 px-3 mt-2 mt-lg-0" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-warning ms-lg-2 px-3 text-dark mt-2 mt-lg-0" href="signup.php">Signup</a></li>
                <?php elseif (isset($_SESSION) && isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light ms-lg-2 px-3 mt-2 mt-lg-0" href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="contact-section container">
    <div class="contact-box">
        <div class="contact-info">
            <h3>Get in Touch</h3>
            <p>We're here to help and answer any questions you might have. We look forward to hearing from you. Please fill out the form or reach us directly using the details below.</p>
            <ul>
                <li><i class="fas fa-map-marker-alt"></i> Pimpri-Chinchwad, Maharashtra, India</li>
                <li><i class="fas fa-phone-alt"></i> <a href="tel:+919876543210">+91 98765 43210</a></li>
                <li><i class="fas fa-envelope"></i> <a href="mailto:info@sairkaro.com">info@sairkaro.com</a></li>
                <li><i class="fas fa-clock"></i> Mon - Fri: 9:00 AM - 6:00 PM IST</li>
            </ul>
            <div class="social-icons">
                <h5>Follow Us</h5>
                <a href="https://www.facebook.com/" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://twitter.com/" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="https://www.instagram.com/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.linkedin.com/" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>

        <div class="contact-form">
            <h2>Send Us a Message</h2>
            <?php if ($message_status): ?>
                <div class="alert alert-<?= $message_type ?>" role="alert">
                    <?= $message_status ?>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Your Full Name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="phone">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="+91-9876543210" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="message">Message <span class="text-danger">*</span></label>
                    <textarea name="message" id="message" rows="5" class="form-control" placeholder="Write your message here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </div>
    </div>
</main>

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
                    <a href="https://www.facebook.com/" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.linkedin.com/" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
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