<?php
// Enable error reporting for development (disable/adjust for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'include/db.php'; // Ensure this path is correct relative to check_schedule.php

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$mode = $_GET['mode'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$schedules = []; // Array to store fetched schedules
$error_message = ''; // To store any database-related errors or general messages

// Check if database connection is successful
if (!isset($conn) || !$conn) {
    $error_message = "Database connection failed. Please try again later.";
} else {
    // Base SQL query
    $sql = "SELECT * FROM schedule WHERE 1";
    $params = [];
    $types = "";

    // Add conditions based on GET parameters
    if (!empty($mode)) {
        $sql .= " AND mode = ?";
        $params[] = $mode;
        $types .= "s";
    }
    if (!empty($from)) {
        $sql .= " AND from_city LIKE ?";
        $params[] = '%' . $from . '%';
        $types .= "s";
    }
    if (!empty($to)) {
        $sql .= " AND to_city LIKE ?";
        $params[] = '%' . $to . '%';
        $types .= "s";
    }

    $sql .= " ORDER BY date, time";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind parameters if any
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                // Fetch all matching schedules
                while ($row = mysqli_fetch_assoc($result)) {
                    $schedules[] = $row;
                }
            } else {
                $error_message = "Error getting results from query: " . mysqli_error($conn);
                error_log("check_schedule.php: Error getting results: " . mysqli_error($conn));
            }
        } else {
            $error_message = "Error executing search query: " . mysqli_stmt_error($stmt);
            error_log("check_schedule.php: Error executing statement: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = "Error preparing search query: " . mysqli_error($conn);
        error_log("check_schedule.php: Error preparing statement: " . mysqli_error($conn));
    }

    mysqli_close($conn); // Close connection after query execution
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Available Schedules - Sair Karo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: rgb(48, 56, 67);
            --secondary-color: #6c757d;
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --info-color: #17a2b8;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-top: 70px; /* Adjust for fixed navbar */
        }

        .navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .navbar-brand {
            font-weight: 600;
            color: var(--primary-color);
        }
        .nav-link {
            font-weight: 500;
        }

        .hero-banner {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('images/hero-bg.jpg') no-repeat center center/cover;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        .hero-banner h1 {
            font-size: 3.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .hero-banner p {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 30px;
        }

        .section-title {
            color: var(--primary-color);
            margin-bottom: 40px;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        .section-title::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            height: 3px;
            width: 80px;
            background-color: var(--primary-color);
            border-radius: 5px;
        }

        .schedule-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }
        .schedule-card .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            font-size: 1.1em;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .schedule-card .card-body {
            padding: 20px;
        }
        .schedule-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-item strong {
            display: block;
            margin-bottom: 5px;
            color: var(--secondary-color);
            font-size: 0.85em;
        }
        .btn-book {
            background-color: var(--success-color);
            border-color: var(--success-color);
            transition: background-color 0.2s ease;
        }
        .btn-book:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .footer {
            background-color: var(--primary-color);
            color: white;
            padding: 40px 0;
            text-align: center;
            margin-top: 50px;
            font-size: 0.9em;
        }
        .footer .social-icons a {
            color: white;
            font-size: 1.5em;
            margin: 0 10px;
            transition: color 0.2s ease;
        }
        .footer .social-icons a:hover {
            color: #ccc;
        }
        .footer .list-unstyled li {
            margin-bottom: 8px;
        }
        .footer .list-unstyled li i {
            width: 20px;
        }
        .copyright {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'include/navbar.php'; ?>

    <div class="container mt-5">
        <h2 class="section-title text-center">Available Schedules</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
            </div>
        <?php elseif (empty($schedules)): ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle me-2"></i>No schedules found matching your criteria. Please try different search parameters.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($schedules as $schedule):
                    // Ensure all values are treated as strings to avoid htmlspecialchars deprecation
                    $schedule_id = htmlspecialchars($schedule['id'] ?? '');
                    $mode_val = htmlspecialchars($schedule['mode'] ?? '');
                    $from_city = htmlspecialchars($schedule['from_city'] ?? '');
                    $to_city = htmlspecialchars($schedule['to_city'] ?? '');
                    $date_val = htmlspecialchars($schedule['date'] ?? '');
                    $time_val = htmlspecialchars($schedule['time'] ?? '');
                    $available_seats = htmlspecialchars($schedule['available_seats'] ?? 0);
                    $total_seats = htmlspecialchars($schedule['total_seats'] ?? 0);
                    $coach_class = htmlspecialchars($schedule['coach_class'] ?? 'N/A');
                    $fare_amount = htmlspecialchars($schedule['fare_amount'] ?? 0);

                    // Robust date and time formatting
                    $formatted_date = '';
                    if (!empty($date_val) && strtotime($date_val) !== false) {
                        $formatted_date = date('d M Y', strtotime($date_val));
                    } else {
                        $formatted_date = 'N/A'; // Or a default placeholder
                    }

                    $formatted_time = '';
                    if (!empty($time_val) && strtotime($time_val) !== false) {
                        $formatted_time = date('h:i A', strtotime($time_val));
                    } else {
                        $formatted_time = 'N/A'; // Or a default placeholder
                    }
                ?>
                    <div class="col-lg-6 col-md-12">
                        <div class="card schedule-card">
                            <div class="card-header">
                                <div>
                                    <i class="fas fa-<?= ($mode_val == 'train' ? 'train' : ($mode_val == 'bus' ? 'bus' : 'plane')) ?> me-2"></i>
                                    <?= ucwords($mode_val) ?> from <?= $from_city ?> to <?= $to_city ?>
                                </div>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-calendar-alt me-1"></i> <?= $formatted_date ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="schedule-details">
                                    <div class="detail-item">
                                        <strong>Departure Time</strong>
                                        <p><?= $formatted_time ?></p>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Available Seats</strong>
                                        <p><?= $available_seats ?> / <?= $total_seats ?></p>
                                    </div>
                                    <?php if ($mode_val == 'plane'): ?>
                                    <div class="detail-item">
                                        <strong>Class</strong>
                                        <p><?= $coach_class ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <strong>Fare</strong>
                                        <p>₹<?= number_format($fare_amount, 2) ?></p>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <?php if ($available_seats > 0): ?>
                                        <a href="book_<?= $mode_val ?>.php?id=<?= $schedule_id ?>&mode=<?= $mode_val ?>" class="btn btn-book">
                                            <i class="fas fa-ticket-alt me-2"></i>Book Now
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-warning" disabled>
                                            <i class="fas fa-exclamation-circle me-2"></i>Sold Out
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'include/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 