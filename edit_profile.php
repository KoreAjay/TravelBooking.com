<?php
include 'include/db.php'; // Your database connection file
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// --- Fetch current user data ---
$user = null;
$sql = "SELECT id, name, email, mobile, created_at FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$user) {
        // User not found, potentially an issue, redirect
        header("Location: login.php?error=user_not_found");
        exit();
    }
} else {
    $error_message = "Database error fetching user data. Please try again.";
    error_log("Failed to prepare user fetch statement: " . mysqli_error($conn));
}

// --- Fetch current profile image path ---
$current_profile_image_path = 'assets/images/default_user.png'; // Default image if none set
$db_image_path = ''; // To store the path from DB for potential deletion later

$sql_profile_img = "SELECT image_path FROM profile_images WHERE user_id = ?";
$stmt_profile_img = mysqli_prepare($conn, $sql_profile_img);
if ($stmt_profile_img) {
    mysqli_stmt_bind_param($stmt_profile_img, "i", $user_id);
    mysqli_stmt_execute($stmt_profile_img);
    $result_profile_img = mysqli_stmt_get_result($stmt_profile_img);
    if ($profile_img_row = mysqli_fetch_assoc($result_profile_img)) {
        $db_image_path = $profile_img_row['image_path'];
        if (file_exists($db_image_path) && !is_dir($db_image_path)) {
            $current_profile_image_path = htmlspecialchars($db_image_path);
        }
    }
    mysqli_stmt_close($stmt_profile_img);
} else {
    error_log("Failed to prepare profile image fetch statement: " . mysqli_error($conn));
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);

    // Basic validation
    if (empty($name) || empty($email) || empty($mobile)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (!preg_match("/^[0-9]{10}$/", $mobile)) {
        $error_message = "Mobile number must be 10 digits.";
    } else {
        // Prepare for image upload
        $new_image_uploaded = false;
        $new_image_db_path = $db_image_path; // Keep old path by default

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['profile_image']['tmp_name'];
            $file_name = $_FILES['profile_image']['name'];
            $file_size = $_FILES['profile_image']['size'];
            $file_type = $_FILES['profile_image']['type'];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_file_size = 5 * 1024 * 1024; // 5 MB

            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Invalid file type. Only JPG, PNG, GIF are allowed.";
            } elseif ($file_size > $max_file_size) {
                $error_message = "File size exceeds 5MB limit.";
            } else {
                // Generate a unique filename to prevent conflicts and security issues
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_filename = uniqid('profile_') . '.' . $extension;
                $upload_dir = 'uploads/profile_pics/';
                $target_file = $upload_dir . $unique_filename;

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true); // Create directory if it doesn't exist
                }

                if (move_uploaded_file($file_tmp_name, $target_file)) {
                    $new_image_uploaded = true;
                    $new_image_db_path = $target_file; // Path to save in DB
                } else {
                    $error_message = "Failed to upload image.";
                }
            }
        }

        if (empty($error_message)) {
            // Update users table
            $sql_update_user = "UPDATE users SET name = ?, email = ?, mobile = ? WHERE id = ?";
            $stmt_update_user = mysqli_prepare($conn, $sql_update_user);
            if ($stmt_update_user) {
                mysqli_stmt_bind_param($stmt_update_user, "sssi", $name, $email, $mobile, $user_id);
                if (mysqli_stmt_execute($stmt_update_user)) {
                    // User data updated
                    $user['name'] = $name; // Update in-memory user data
                    $user['email'] = $email;
                    $user['mobile'] = $mobile;

                    // Update profile_images table if a new image was uploaded
                    if ($new_image_uploaded) {
                        // Delete old image file if it exists and is not the default
                        if (!empty($db_image_path) && $db_image_path !== 'assets/images/default_user.png' && file_exists($db_image_path)) {
                            unlink($db_image_path); // Delete the old file
                        }

                        $sql_upsert_image = "INSERT INTO profile_images (user_id, image_path) VALUES (?, ?)
                                             ON DUPLICATE KEY UPDATE image_path = ?";
                        $stmt_upsert_image = mysqli_prepare($conn, $sql_upsert_image);
                        if ($stmt_upsert_image) {
                            mysqli_stmt_bind_param($stmt_upsert_image, "iss", $user_id, $new_image_db_path, $new_image_db_path);
                            mysqli_stmt_execute($stmt_upsert_image);
                            mysqli_stmt_close($stmt_upsert_image);
                            $current_profile_image_path = htmlspecialchars($new_image_db_path); // Update displayed path
                        } else {
                            $error_message .= " Error saving image path to database.";
                            error_log("Failed to prepare upsert image statement: " . mysqli_error($conn));
                        }
                    }
                    $success_message = "Profile updated successfully!";
                } else {
                    $error_message = "Error updating profile: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt_update_user);
            } else {
                $error_message = "Database error updating profile. Please try again.";
                error_log("Failed to prepare user update statement: " . mysqli_error($conn));
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Profile - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Variables for easy color management */
        :root {
            --primary-color: #4CAF50; /* A fresh green, signifies growth/progress */
            --secondary-color: #6c757d;
            --light-bg: #f5f8fa; /* Lighter background */
            --dark-text: #343a40;
            --white-color: #ffffff;
            --accent-color: #FFD700; /* Gold, for highlights */
            --danger-color: #dc3545;
            --info-light: #e0f7fa; /* Light cyan for info backgrounds */
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-top: 70px; /* Space for fixed navbar */
        }

        /* Navbar Styling */
        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            filter: brightness(0) invert(1); /* Makes logo white */
        }
        .navbar-brand:hover {
            color: var(--accent-color) !important;
        }
        .nav-link {
            color: var(--white-color) !important;
            font-weight: 500;
            margin-right: 15px;
            transition: color 0.3s ease;
            position: relative; /* For underline effect */
        }
        .nav-link:hover {
            color: var(--accent-color) !important;
        }
        .nav-link::after { /* Underline effect */
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -5px;
            left: 0;
            background-color: var(--accent-color);
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.5) !important;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }

        .container-main {
            flex: 1;
            margin-top: 40px;
            padding-bottom: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .edit-profile-card {
            background: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .edit-profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        .edit-profile-card h3 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            padding-bottom: 10px;
        }
        .edit-profile-card h3::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 5px;
        }
        .profile-image-upload-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-image-upload-section img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid var(--primary-color);
            margin-bottom: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        .profile-image-upload-section img:hover {
            transform: scale(1.05);
        }
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 18px;
            border: 1px solid var(--border-color);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 175, 80, 0.25); /* Primary color with transparency */
            outline: none;
        }
        .form-text {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-top: 5px;
        }
        .btn-update-profile {
            background-color: var(--primary-color);
            color: var(--white-color);
            border-radius: 30px;
            padding: 12px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2); /* Primary color with transparency */
            border: none;
        }
        .btn-update-profile:hover {
            background-color: #43A047; /* Slightly darker green */
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.3);
            color: var(--white-color); /* Ensure text color remains white */
        }
        .btn-update-profile:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(76, 175, 80, 0.3);
        }
        .alert {
            border-radius: 10px;
            margin-top: 20px;
            font-weight: 500;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        .alert .btn-close {
            background-color: transparent;
            border: 0;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        .alert .btn-close:hover {
            opacity: 1;
        }


        /* Footer Styling */
        .main-footer {
            background-color: var(--primary-color);
            color: var(--white-color);
            padding: 30px 0;
            text-align: center;
            margin-top: auto;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.1);
        }
        .main-footer h5 {
            color: var(--accent-color);
            margin-bottom: 20px;
            font-weight: 700;
            font-size: 1.3rem;
        }
        .main-footer p, .main-footer li {
            font-size: 0.95rem;
            line-height: 1.8;
        }
        .main-footer ul {
            padding-left: 0;
            list-style: none;
        }
        .main-footer ul li a {
            color: var(--white-color);
            text-decoration: none;
            transition: color 0.3s ease, text-decoration 0.3s ease;
            padding: 5px 0;
            display: block;
        }
        .main-footer ul li a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        .main-footer .social-icons a {
            color: var(--white-color);
            font-size: 1.6rem;
            margin: 0 12px;
            transition: color 0.3s ease, transform 0.3s ease;
            display: inline-block;
        }
        .main-footer .social-icons a:hover {
            color: var(--accent-color);
            transform: translateY(-5px) scale(1.1);
        }
        .main-footer .copyright {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .edit-profile-card {
                padding: 30px;
            }
            .navbar-brand {
                font-size: 1.6rem;
            }
            .navbar-brand img {
                height: 35px;
            }
            .nav-link {
                margin-right: 0;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
        }

        @media (max-width: 767.98px) {
            body {
                padding-top: 56px;
            }
            .navbar-brand {
                font-size: 1.4rem;
            }
            .navbar-brand img {
                height: 28px;
            }
            .edit-profile-card {
                padding: 20px;
            }
            .edit-profile-card h3 {
                font-size: 1.7rem;
                margin-bottom: 20px;
            }
            .profile-image-upload-section img {
                width: 100px;
                height: 100px;
            }
            .btn-update-profile {
                font-size: 1rem;
                padding: 10px 30px;
            }
            .main-footer h5 {
                font-size: 1.2rem;
            }
            .main-footer p, .main-footer li {
                font-size: 0.85rem;
            }
            .main-footer .social-icons a {
                font-size: 1.4rem;
                margin: 0 8px;
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
                        <a class="nav-link" href="my_bookings.php"><i class="fas fa-ticket-alt me-1"></i> My Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user-circle me-1"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <div class="row justify-content-center w-100">
            <div class="col-md-8 col-lg-6">
                <div class="edit-profile-card">
                    <h3>Edit Your Profile</h3>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="edit_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="profile-image-upload-section">
                            <img src="<?= $current_profile_image_path ?>" alt="Current Profile Photo" class="img-fluid">
                            <p class="text-muted mt-2">Current Profile Picture</p>
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Change Profile Picture</label>
                                <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif">
                                <div class="form-text">Max 5MB. JPG, PNG, GIF formats only.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" id="mobile" name="mobile" value="<?= htmlspecialchars($user['mobile'] ?? '') ?>" pattern="[0-9]{10}" title="Mobile number must be 10 digits" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-update-profile">
                                <i class="fas fa-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
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