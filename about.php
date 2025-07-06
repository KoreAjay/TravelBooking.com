<?php
// Ensure session_start() is at the very beginning of the script
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include 'include/db.php'; // Include your database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>About Us - Sair Karo</title>
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
            --heading-font: 'Poppins', sans-serif;
            --body-font: 'Poppins', sans-serif;

            /* Footer specific colors */
            --footer-bg: #2C0F0E; /* Very dark, almost black, red-tinted for footer */
            --footer-text: #F0E0E0; /* Off-white for footer text on dark background */
        }

        body {
            font-family: var(--body-font);
            background-color: var(--light-bg);
            color: var(--dark-text);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: 70px; /* Space for fixed navbar */
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

        /* --- Global Transitions for Smooth Effects --- */
        a, .btn, .form-control, .card, .nav-link {
            transition: all 0.3s ease-in-out;
        }

        /* --- Navbar Styling (consistent with other pages) --- */
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

        .navbar-brand:hover {
            color: var(--accent-color) !important; /* Accent Yellow */
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

        /* --- Main Content Area --- */
        .container-main {
            flex: 1;
            margin-top: 40px;
            padding-bottom: 60px;
        }

        /* Hero Section */
        .hero-section {
            /* Use a suitable image, possibly one that blends well with red or is abstract */
            background: linear-gradient(rgba(229, 57, 53, 0.75), rgba(198, 40, 40, 0.75)), url('assets/images/about-hero-bg.jpg') no-repeat center center/cover; /* Red gradient overlay */
            height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--white-color);
            text-shadow: 0 3px 10px rgba(0,0,0,0.5);
            border-radius: 25px;
            margin-bottom: 70px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        .hero-section h1 {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -1px;
            position: relative;
            padding-bottom: 20px;
            color: var(--white-color); /* Ensure hero text is white */
        }
        .hero-section h1::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 180px;
            height: 7px;
            background-color: var(--accent-color); /* Accent Yellow */
            border-radius: 4px;
        }
        .hero-section p.lead {
            font-size: 1.5rem;
            font-weight: 400;
            max-width: 700px;
            text-align: center;
            margin-top: 20px;
            opacity: 0.9;
            color: var(--white-color); /* Ensure hero text is white */
        }

        /* Section Titles */
        .section-title {
            color: var(--primary-dark); /* Darker Red for titles */
            font-weight: 800;
            text-align: center;
            margin-bottom: 60px;
            font-size: 3rem;
            position: relative;
            padding-bottom: 20px;
        }
        .section-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 120px;
            height: 5px;
            background-color: var(--accent-color); /* Accent Yellow */
            border-radius: 3px;
        }

        /* Content Boxes */
        .content-box {
            background-color: var(--white-color);
            border-radius: 25px;
            padding: 45px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
            margin-bottom: 60px;
            line-height: 1.8;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .content-box p {
            font-size: 1.15rem;
            color: var(--dark-text);
            margin-bottom: 1.5rem;
        }
        .content-box strong {
            color: var(--primary-dark); /* Darker Red */
            font-weight: 700;
        }

        /* Mission & Vision Boxes */
        .mission-vision-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            justify-content: center;
            margin-bottom: 60px;
        }
        .mission-vision-box {
            background-color: var(--white-color);
            border-left: 8px solid var(--accent-color); /* Accent Yellow Border */
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            flex: 1;
            min-width: 320px;
            max-width: 48%;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .mission-vision-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .mission-vision-box h4 {
            color: var(--primary-dark); /* Darker Red */
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 10px;
        }
        .mission-vision-box h4::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color); /* Primary Red */
            border-radius: 2px;
        }
        .mission-vision-box p {
            font-size: 1.08rem;
            color: var(--secondary-color);
            line-height: 1.7;
        }
        .mission-vision-box i {
            color: var(--accent-color); /* Accent Yellow for Icons */
            font-size: 2.8rem;
            margin-bottom: 25px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Team Section */
        .team-member-card {
            background-color: var(--white-color);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .team-member-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }
        .team-member-card img {
            border-radius: 50%;
            width: 160px;
            height: 160px;
            object-fit: cover;
            border: 6px solid var(--primary-color); /* Primary Red Border */
            padding: 5px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .team-member-card h5 {
            font-weight: 700;
            color: var(--primary-dark); /* Darker Red */
            font-size: 1.6rem;
            margin-bottom: 5px;
        }
        .team-member-card p.text-muted {
            color: var(--secondary-color);
            font-size: 1.05rem;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .team-social-icons a {
            color: var(--primary-color); /* Primary Red */
            font-size: 1.6rem;
            margin: 0 12px;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .team-social-icons a:hover {
            color: var(--accent-color); /* Accent Yellow */
            transform: translateY(-5px);
        }

        /* --- Footer Styling (Consistent with other pages) --- */
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

        /* Responsive Adjustments */
        @media (max-width: 991.98px) {
            .hero-section {
                height: 350px;
                margin-bottom: 50px;
            }
            .hero-section h1 {
                font-size: 3.5rem;
            }
            .hero-section p.lead {
                font-size: 1.2rem;
            }
            .section-title {
                font-size: 2.5rem;
                margin-bottom: 40px;
            }
            .content-box {
                padding: 35px;
                margin-bottom: 40px;
            }
            .content-box p {
                font-size: 1.05rem;
            }
            .mission-vision-grid {
                gap: 30px;
            }
            .mission-vision-box {
                max-width: 100%;
                padding: 30px;
            }
            .mission-vision-box h4 {
                font-size: 1.6rem;
            }
            .mission-vision-box i {
                font-size: 2.5rem;
                margin-bottom: 20px;
            }
            .team-member-card img {
                width: 140px;
                height: 140px;
            }
            .team-member-card h5 {
                font-size: 1.4rem;
            }
            .team-member-card p.text-muted {
                font-size: 1rem;
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
            .hero-section {
                height: 300px;
                margin-bottom: 40px;
                border-radius: 15px;
            }
            .hero-section h1 {
                font-size: 2.8rem;
                padding-bottom: 15px;
            }
            .hero-section h1::after {
                width: 120px;
                height: 5px;
            }
            .hero-section p.lead {
                font-size: 1.1rem;
            }
            .section-title {
                font-size: 2.2rem;
                margin-bottom: 30px;
                padding-bottom: 15px;
            }
            .section-title::after {
                width: 80px;
                height: 4px;
            }
            .content-box, .mission-vision-box, .team-member-card {
                padding: 25px;
                margin-bottom: 30px;
            }
            .content-box p {
                font-size: 1rem;
            }
            .mission-vision-box {
                border-radius: 15px;
                border-left-width: 6px;
            }
            .mission-vision-box h4 {
                font-size: 1.4rem;
                padding-bottom: 8px;
            }
            .mission-vision-box h4::after {
                width: 40px;
                height: 2px;
            }
            .mission-vision-box p {
                font-size: 0.95rem;
            }
            .mission-vision-box i {
                font-size: 2.2rem;
                margin-bottom: 15px;
            }
            .team-member-card img {
                width: 120px;
                height: 120px;
                border-width: 4px;
            }
            .team-member-card h5 {
                font-size: 1.2rem;
            }
            .team-member-card p.text-muted {
                font-size: 0.95rem;
                margin-bottom: 15px;
            }
            .team-social-icons a {
                font-size: 1.4rem;
                margin: 0 8px;
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
            .hero-section h1 {
                font-size: 2.2rem;
            }
            .hero-section p.lead {
                font-size: 1rem;
            }
            .section-title {
                font-size: 1.8rem;
            }
            .content-box, .mission-vision-box, .team-member-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark px-md-4">
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
                            <a class="nav-link" href="profile.php"><i class="fas fa-user-circle me-1"></i> Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="signup.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="about.php"><i class="fas fa-info-circle me-1"></i> About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><i class="fas fa-envelope me-1"></i> Contact Us</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="hero-section">
            <h1>About Sair Karo</h1>
            <p class="lead">Your journey, simplified. Discover seamless travel experiences with us.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="section-title">Who We Are</h2>
                <div class="content-box">
                    <p>
                        <strong>Sair Karo</strong> is your premier all-in-one travel management platform, meticulously crafted to simplify ticket bookings for **Train, Bus, and Flight** journeys across India. Whether you're embarking on a spontaneous adventure, a crucial business trip, or a heartfelt visit to your hometown, we are dedicated to making your travel experience **seamless, reliable, and affordable**.
                    </p>
                    <p>
                        Our robust platform provides comprehensive services, from **real-time schedule checking** and **effortless booking management** to **timely travel updates**. We are committed to ensuring comfort and convenience at every step of your journey, transforming the way you travel.
                    </p>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="section-title">Our Vision & Mission</h2>
                <div class="mission-vision-grid">
                    <div class="mission-vision-box">
                        <i class="fas fa-bullseye"></i>
                        <h4>Our Mission</h4>
                        <p>
                            Our mission is to **bridge the gap** between travelers and transport providers by leveraging cutting-edge technology. We are passionate about fostering transparency, offering unparalleled flexibility, and prioritizing user convenience in every interaction. We aim to be the **most trusted and preferred choice** for smart travel booking and truly hassle-free experiences in India.
                        </p>
                    </div>
                    <div class="mission-vision-box">
                        <i class="fas fa-eye"></i>
                        <h4>Our Vision</h4>
                        <p>
                            To be the **leading digital travel platform** in India, empowering millions of travelers with intelligent, personalized, and eco-friendly travel solutions. We envision a future where every journey is effortlessly planned, booked, and enjoyed, contributing positively to communities and the environment.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h2 class="section-title">Meet the Team</h2>
                <div class="row text-center">
                    <div class="col-md-4 mb-4 d-flex align-items-stretch">
                        <div class="team-member-card">
                            <img src="assets/images/team1.png" alt="Ajay Kore - Founder">
                            <h5>Ajay Kore</h5>
                            <p class="text-muted">Founder & Lead Developer</p>
                            <div class="team-social-icons mt-auto">
                                <a href="#" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                                <a href="#" target="_blank" aria-label="GitHub"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4 d-flex align-items-stretch">
                        <div class="team-member-card">
                            <img src="assets/images/team2.jpg" alt="Muskan - Customer Support Lead">
                            <h5>Muskan</h5>
                            <p class="text-muted">Customer Support Lead</p>
                            <div class="team-social-icons mt-auto">
                                <a href="#" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                                <a href="#" target="_blank" aria-label="Email"><i class="fas fa-envelope"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4 d-flex align-items-stretch">
                        <div class="team-member-card">
                            <img src="assets/images/team3.png" alt="Rahul Bhoge - Marketing & Outreach">
                            <h5>Rahul Bhoge</h5>
                            <p class="text-muted">Marketing & Outreach</p>
                            <div class="team-social-icons mt-auto">
                                <a href="#" target="_blank" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                                <a href="#" target="_blank" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
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