<?php
include 'include/db.php'; //
session_start(); //

$success = ""; //
$error = ""; //

// Initialize variables for pre-filling form (only for fields present in the new design)
$name_value = '';
$email_value = '';
// Passwords are not pre-filled for security reasons

if ($_SERVER["REQUEST_METHOD"] == "POST") { //
    $name             = trim($_POST["name"] ?? '');
    $email            = trim($_POST["email"] ?? '');
    $password         = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm_password"] ?? '';

    // Store current values to re-populate the form if there's an error (excluding passwords)
    $name_value = $name;
    $email_value = $email;

    // Basic server-side validation for new fields
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "⚠️ All fields are required."; //
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "📧 Please enter a valid email address."; //
    } elseif (strlen($password) < 6) { // Minimum password length
        $error = "🔒 Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match. Please try again.";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "🚫 This email is already registered. Try logging in!";
        } else {
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Use prepared statement for security for new fields (name, email, password)
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "🎉 Sign up successful! You can now log in.";
                // Clear form fields after success
                $name_value = $email_value = ''; // Reset form
            } else {
                $error = "❌ Error: " . $stmt->error; //
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel - Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Variables - Based on image_32a9e1.jpg's color scheme */
        :root {
            --primary-blue: #233767; /* Dark blue for panels */
            --light-bg-color: #E6F0F6; /* Very light blue/grey for body background */
            --white-card-bg: #ffffff;
            --text-dark: #333333;
            --text-light: #ffffff;
            --input-border: #cccccc; /* Light border for inputs */
            --input-focus-border: #66afe9; /* Standard blue focus */
            --button-blue: #4a67b5; /* Primary button blue */
            --button-blue-hover: #3a5091;
            --google-btn-border: #dd4b39; /* Google red */
            --error-red: #dc3545;
            --success-green: #28a745;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--light-bg-color); /* Light background as in new image */
            color: var(--text-dark);
            line-height: 1.6;
        }

        .signup-container {
            display: flex;
            width: 950px; /* Wider to accommodate both panels cleanly */
            max-width: 95%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .left-panel {
            flex: 1.5; /* Slightly larger left panel */
            background-image: url('assets/images/signup.png'); /* Hot air balloon image */
            background-size: cover;
            background-position: center;
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            justify-content: center; /* Center content vertically */
            align-items: flex-start;
            padding: 40px;
            text-align: left;
            position: relative;
            min-height: 600px; /* Ensure sufficient height */
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.3) 0%, rgba(0, 0, 0, 0.05) 50%, transparent 100%); /* Subtle gradient */
            border-radius: 15px 0 0 15px;
        }

        .left-panel-content {
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .left-panel h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 3rem; /* Larger font size as in image */
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
        }

        .left-panel p {
            font-family: 'Open Sans', sans-serif;
            font-size: 1rem;
            line-height: 1.5;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .left-panel .learn-more-btn {
            background-color: var(--white-card-bg);
            color: var(--primary-blue);
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .left-panel .learn-more-btn:hover {
            background-color: #f0f0f0;
            color: #1a2a4e;
        }

        .right-panel {
            flex: 1;
            background-color: var(--white-card-bg); /* White background for the form side */
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--text-dark);
            border-radius: 0 15px 15px 0;
            position: relative;
        }

        .right-panel .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            color: var(--primary-blue);
        }

        .right-panel .logo i {
            font-size: 1.8rem;
            margin-right: 10px;
        }

        .right-panel .logo span {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .right-panel .section-title {
            text-align: center;
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 25px;
            border: 1px solid var(--input-border); /* Solid border for inputs */
            border-radius: 8px; /* Rounded input fields */
            overflow: hidden;
            background-color: var(--white-card-bg);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .input-group:focus-within {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 0.25rem rgba(102, 175, 233, 0.25); /* Standard blue focus ring */
        }

        .right-panel .input-group-text {
            background-color: var(--white-card-bg);
            border: none;
            color: var(--text-dark);
            padding: 12px 15px; /* Padding for icon */
            font-size: 1rem;
            border-right: 1px solid var(--input-border); /* Separator line for icon */
        }

        .right-panel .form-control {
            background-color: var(--white-card-bg);
            border: none;
            border-radius: 0;
            padding: 12px 15px; /* Padding for input text */
            color: var(--text-dark);
            font-size: 1rem;
        }

        .right-panel .form-control:focus {
            box-shadow: none;
            background-color: var(--white-card-bg);
            color: var(--text-dark);
        }

        .right-panel .form-control::placeholder {
            color: #888888;
        }

        .btn-continue {
            background-color: var(--button-blue);
            color: var(--text-light);
            padding: 12px 0;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            border: none;
            transition: background-color 0.3s ease;
            margin-top: 15px;
        }

        .btn-continue:hover {
            background-color: var(--button-blue-hover);
        }

        .separator {
            text-align: center;
            margin: 30px 0;
            position: relative;
            color: #999999;
            font-size: 0.9rem;
        }

        .separator::before,
        .separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background-color: #e0e0e0;
        }

        .separator::before { left: 0; }
        .separator::after { right: 0; }

        .separator span {
            background-color: var(--white-card-bg);
            padding: 0 10px;
        }

        .btn-google {
            background-color: var(--white-card-bg);
            color: var(--google-btn-border);
            padding: 12px 0;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            border: 2px solid var(--google-btn-border);
            transition: background-color 0.3s ease, color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-google i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .btn-google:hover {
            background-color: var(--google-btn-border);
            color: var(--text-light);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            font-size: 0.95rem;
            color: var(--text-dark);
        }

        .login-link a {
            color: var(--button-blue);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: var(--button-blue-hover);
            text-decoration: underline;
        }

        /* Alert message styling */
        .alert {
            margin-bottom: 20px;
            font-weight: 500;
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            position: absolute; /* Position to appear above content */
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 80px); /* Adjust width considering padding */
            z-index: 10;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.9);
            color: var(--white);
            border: 1px solid var(--success-green);
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.9);
            color: var(--white);
            border: 1px solid var(--error-red);
        }


        /* Responsive adjustments */
        @media (max-width: 992px) {
            .signup-container {
                flex-direction: column;
                width: 100%;
                border-radius: 10px;
            }
            .left-panel, .right-panel {
                flex: none;
                width: 100%;
                border-radius: 0;
            }
            .left-panel {
                min-height: 250px;
                height: auto;
                border-radius: 10px 10px 0 0;
                padding: 30px;
                justify-content: center;
                align-items: center;
                text-align: center;
            }
            .left-panel::before {
                border-radius: 10px 10px 0 0;
            }
            .left-panel-content {
                width: auto;
            }
            .left-panel h2 {
                font-size: 2.2rem; /* Adjusted for smaller screens */
                margin-bottom: 15px;
            }
            .left-panel p {
                font-size: 0.9rem;
            }
            .left-panel .learn-more-btn {
                padding: 8px 20px;
                font-size: 0.9rem;
            }
            .right-panel {
                padding: 40px 30px;
                border-radius: 0 0 10px 10px;
            }
            .right-panel .logo {
                margin-bottom: 20px;
            }
            .right-panel .section-title {
                margin-bottom: 25px;
            }
            .alert {
                position: static;
                transform: none;
                width: auto;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 576px) {
            .left-panel {
                min-height: 180px;
            }
            .left-panel h2 {
                font-size: 1.8rem;
            }
            .right-panel {
                padding: 30px 20px;
            }
            .right-panel .logo i, .right-panel .logo span {
                font-size: 1.5rem;
            }
            .btn-continue, .btn-google {
                font-size: 1rem;
                padding: 10px 0;
            }
        }
    </style>
</head>
<body>

    <div class="signup-container">
        <div class="left-panel">
            <div class="left-panel-content">
                <h2>ENJOY THE WORLD</h2>
                <p>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam ac, ipsum et, dolor sit amet.
                    Duis in, volutpat nunc, quam id, commodo dui.
                </p>
                <a href="#" class="learn-more-btn">Learn More</a>
            </div>
        </div>
        <div class="right-panel">
            <div class="logo">
                <i class="fas fa-plane"></i>
                <span>SAIR KARO</span>
                
            </div>

            <div class="section-title">Create Account</div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" name="name" id="name" class="form-control" placeholder="Name*" value="<?= htmlspecialchars($name_value) ?>" required>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Email Address*" value="<?= htmlspecialchars($email_value) ?>" required>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password*" required>
                </div>
                <div class="input-group mb-4">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password*" required>
                </div>

                <button type="submit" class="btn btn-continue">Continue</button>
            </form>

            <div class="separator">
                <span>or</span>
            </div>

            <button type="button" class="btn-google">
                <i class="fab fa-google"></i> Sign up with Google
            </button>

            <p class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </p>
            <p class="login-link mt-3">
                <a href="home.php"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>