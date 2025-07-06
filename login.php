<?php
// Ensure db.php is included.
require_once 'include/db.php';
session_start(); // Start session at the very beginning

$message = ""; // Variable to store success or error messages
$email_value = ''; // To pre-fill email field on error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get raw input from the form
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Get password

    // Store email value to pre-fill form in case of error
    $email_value = $email;

    // Input validation
    if (empty($email) || empty($password)) {
        $message = "Please fill in both Email and Password fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {
        // Use prepared statement for security
        // Select user based on email and fetch their hashed password
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email); // 's' indicates a string parameter for email
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password_from_db = $user['password'];

            // Verify the submitted password against the hashed password from the database
            if (password_verify($password, $hashed_password_from_db)) {
                // Login successful!
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];

                // Redirect to a dashboard or home page
                header("Location: home.php"); // Make sure 'home.php' exists
                exit();
            } else {
                // Password does not match
                $message = "Invalid Email or Password. Please check your credentials.";
            }
        } else {
            // Email not found
            $message = "Invalid Email or Password. Please check your credentials.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Blogger - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Variables - More refined palette */
        :root {
            --primary-blue: #233767; /* Original dark blue */
            --secondary-blue: #3E5A9D; /* A slightly lighter blue */
            --light-bg: #f5f8fa; /* Very light, almost white background */
            --white: #ffffff;
            --text-dark: #333333;
            --text-light: #f0f0f0;
            --border-color: #ccd3e4; /* Light blue-grey for borders */
            --input-focus-border: #7b9edb; /* Darker blue on input focus */
            --button-hover: #1e2c53; /* Darker blue for button hover */
            --error-red: #dc3545;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--light-bg);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .login-container {
            display: flex;
            width: 950px; /* Slightly wider for better layout */
            max-width: 95%;
            border-radius: 18px; /* Softer rounded corners */
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15); /* Stronger, softer shadow */
            background-color: var(--white);
        }

        .left-panel {
            flex: 1.2; /* Give more space to the image side */
            background-image: url('assets/images/login.png'); /* Ensure this path is correct for your image */
            background-size: cover;
            background-position: center;
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px; /* More padding */
            text-align: center;
            position: relative;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.45); /* Darker overlay for text contrast */
            border-radius: 18px 0 0 18px; /* Match container border-radius */
        }

        .left-panel-content {
            position: relative;
            z-index: 1; /* Ensure content is above the overlay */
        }

        .left-panel h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.8rem; /* Larger, more impactful heading */
            font-weight: 700;
            margin-bottom: 35px;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3); /* Subtle text shadow */
        }

        .left-panel .social-icons {
            margin-top: 40px;
            display: flex;
            gap: 25px; /* Spacing between icons */
        }

        .left-panel .social-icons a {
            color: var(--text-light);
            font-size: 1.8rem; /* Larger icons */
            transition: color 0.3s ease, transform 0.2s ease;
        }

        .left-panel .social-icons a:hover {
            color: var(--white); /* Brighter on hover */
            transform: translateY(-3px); /* Lift effect */
        }

        .right-panel {
            flex: 1;
            background-color: var(--primary-blue);
            padding: 60px 45px; /* More generous padding */
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--text-light);
            border-radius: 0 18px 18px 0; /* Match container border-radius */
            position: relative; /* For error message positioning */
        }

        .right-panel .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.1rem; /* Slightly larger */
            font-weight: 700;
            border: 3px solid var(--text-light); /* Thicker border */
            padding: 8px 25px; /* More padding */
            display: inline-block;
            margin-bottom: 50px; /* More space below */
            letter-spacing: 2px; /* Wider letter spacing */
            text-transform: uppercase;
            line-height: 1; /* Prevent extra space */
            color: var(--white); /* Ensure logo text is white */
        }

        .right-panel .social-login-icons {
            display: flex;
            justify-content: center;
            gap: 25px; /* More space between social login icons */
            margin-bottom: 40px;
        }

        .right-panel .social-login-icons a {
            width: 50px; /* Larger circles */
            height: 50px;
            background-color: var(--white); /* White background for social icons */
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--primary-blue); /* Primary blue for icons */
            font-size: 1.5rem; /* Larger icons */
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .right-panel .social-login-icons a:hover {
            transform: translateY(-3px);
            opacity: 0.9;
        }
        .right-panel .social-login-icons a.facebook:hover { background-color: #3b5998; color: var(--white); }
        .right-panel .social-login-icons a.google:hover { background-color: #dd4b39; color: var(--white); } /* Using google+ icon for general google */
        .right-panel .social-login-icons a.linkedin:hover { background-color: #007bb5; color: var(--white); }

        .right-panel .separator {
            text-align: center;
            margin-bottom: 40px; /* More space */
            position: relative;
            color: rgba(255, 255, 255, 0.7); /* Lighter text for separator */
            font-size: 0.95rem;
        }

        .right-panel .separator::before,
        .right-panel .separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 35%; /* Shorter lines */
            height: 1px;
            background-color: var(--border-color); /* Lighter border color */
        }

        .right-panel .separator::before { left: 0; }
        .right-panel .separator::after { right: 0; }

        .right-panel .separator span {
            background-color: var(--primary-blue);
            padding: 0 15px; /* More padding around text */
        }

        .input-group {
            margin-bottom: 25px; /* Consistent margin */
            border-bottom: 1px solid var(--border-color); /* Defined bottom border */
            transition: border-color 0.3s ease;
        }

        .input-group:focus-within {
            border-color: var(--input-focus-border); /* Highlight on focus */
        }

        .right-panel .input-group-text {
            background-color: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.8); /* Slightly less opaque icons */
            padding-right: 15px; /* More space between icon and input */
            font-size: 1.1rem; /* Slightly larger icons in inputs */
        }

        .right-panel .form-control {
            background-color: transparent;
            border: none;
            border-radius: 0;
            padding: 10px 0;
            color: var(--text-light);
            font-size: 1.05rem; /* Slightly larger font */
            padding-left: 0; /* Remove default left padding */
        }

        .right-panel .form-control:focus {
            box-shadow: none;
            background-color: transparent;
            color: var(--text-light);
        }

        .right-panel .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6); /* More subtle placeholder */
        }

        .right-panel .forgot-password {
            text-align: right;
            margin-top: -15px; /* Adjust to bring closer to input */
            margin-bottom: 45px;
        }

        .right-panel .forgot-password a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .right-panel .forgot-password a:hover {
            color: var(--white);
            text-decoration: underline;
        }

        .right-panel .btn-enter {
            background-color: var(--secondary-blue); /* Use secondary blue for the button */
            color: var(--white);
            padding: 14px 0; /* More vertical padding */
            border-radius: 50px;
            font-size: 1.2rem; /* Larger button text */
            font-weight: 600;
            width: 100%;
            border: none;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); /* Button shadow */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
        }

        .right-panel .btn-enter:hover {
            background-color: var(--button-hover); /* Darker blue on hover */
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3);
        }

        /* Error message styling */
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.9); /* Slightly transparent red */
            color: var(--white);
            border: 1px solid var(--error-red);
            padding: 12px;
            margin-bottom: 30px; /* Space it out */
            border-radius: 8px;
            text-align: center;
            font-size: 0.9rem;
            position: absolute; /* Position it absolutely within the right panel */
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 90px); /* Adjust width considering padding */
            z-index: 10;
        }


        /* Responsive adjustments */
        @media (max-width: 992px) {
            .login-container {
                width: 90%;
                flex-direction: column; /* Stack panels vertically */
                border-radius: 10px; /* Adjust border radius for smaller screens */
            }

            .left-panel, .right-panel {
                flex: none; /* Remove flex sizing */
                width: 100%; /* Take full width */
                border-radius: 0;
            }

            .left-panel {
                height: 280px; /* Fixed height for image on smaller screens */
                border-radius: 10px 10px 0 0; /* Rounded top corners */
            }

            .left-panel::before {
                border-radius: 10px 10px 0 0;
            }

            .right-panel {
                padding: 40px 30px; /* Adjust padding for smaller screens */
                border-radius: 0 0 10px 10px; /* Rounded bottom corners */
            }

            .right-panel .logo-text {
                margin-bottom: 30px;
                font-size: 1.8rem;
                padding: 5px 20px;
            }

            .right-panel .social-login-icons {
                margin-bottom: 30px;
                gap: 15px;
            }

            .right-panel .separator {
                margin-bottom: 30px;
            }

            .right-panel .forgot-password {
                margin-bottom: 30px;
            }

            .alert-danger {
                position: static; /* Let it flow naturally */
                transform: none;
                width: auto;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 576px) {
            .left-panel h2 {
                font-size: 2rem;
            }
            .left-panel .social-icons a {
                font-size: 1.3rem;
            }
            .right-panel .logo-text {
                font-size: 1.5rem;
            }
            .right-panel .btn-enter {
                font-size: 1rem;
            }
            .right-panel {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="left-panel">
            <div class="left-panel-content">
                <!-- <h2>TRAVEL IS THE ONLY THING YOU BUY THAT MAKES YOU RICHER</h2> -->
                 <br><br><br><br><br><br><br><br><br><br><br><br><br>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="right-panel">
            <div class="text-center">
                <span class="logo-text">SAIR KARO</span>
            </div>

            <div class="social-login-icons">
                <a href="#" class="facebook" aria-label="Login with Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="google" aria-label="Login with Google"><i class="fab fa-google-plus-g"></i></a>
                <a href="#" class="linkedin" aria-label="Login with LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>

            <div class="separator">
                <span>or use your email account</span>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" novalidate>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" name="email" placeholder="Email Address" required value="<?php echo htmlspecialchars($email_value); ?>">
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <div class="forgot-password">
                    <a href="#">Forgot Your Password?</a>
                </div>
                <button type="submit" class="btn btn-enter">ENTER</button>
            </form>

            <p class="signup-link mt-4">
                Not registered yet? <a href="signup.php">Create an Account</a>
            </p>
            <p class="home-link mt-2">
                <a href="home.php"><i class="fas fa-arrow-left me-2"></i>Back to Home</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>