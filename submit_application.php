<?php
// submit_application.php

// Allow cross-origin requests for local development (adjust for production)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Database configuration (Adjust these based on your XAMPP setup)
$servername = "localhost"; // Usually 'localhost' for XAMPP
$username = "root";        // Default XAMPP MySQL username
$password = "";            // Default XAMPP MySQL password (empty by default for XAMPP)
$dbname = "travel_db";     // IMPORTANT: This must match your database name from phpMyAdmin

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// --- DEBUG START ---
// Output connection status for debugging
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
} else {
    // This will appear in the Network tab's Response
    // Do not use this in production
    // echo "DEBUG: Database connected successfully.\n";
}
// --- DEBUG END ---


// Ensure the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit();
}

// Get form data from the POST request
$job_title = filter_input(INPUT_POST, 'jobTitle', FILTER_SANITIZE_STRING);
$full_name = filter_input(INPUT_POST, 'fullName', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// --- DEBUG START ---
// Output received POST data for debugging
// Do not use this in production
// echo "DEBUG: Received POST data:\n";
// var_dump($_POST);
// var_dump($_FILES);
// --- DEBUG END ---

// Validate required fields (Full Name and Email are set as required in careers.html)
if (empty($job_title) || empty($full_name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Required fields (Job Title, Full Name, Email) are missing or invalid."]);
    exit();
}

$resume_path = null; // Initialize resume path to null
$upload_dir = 'resumes/'; // Directory to save resumes (relative to this PHP script)

// Handle resume file upload (now optional in careers.html)
if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
    // Create the upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) { // Attempt to create directory with full permissions
            error_log("Failed to create upload directory: " . $upload_dir);
            // Even if directory creation fails, we'll try to continue without resume for this optional field
        }
    }

    $file_tmp_name = $_FILES['resume']['tmp_name'];
    $file_name = basename($_FILES['resume']['name']);
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    // Generate a unique name for the uploaded file to prevent conflicts
    $unique_file_name = uniqid('resume_') . '.' . $file_extension;
    $destination = $upload_dir . $unique_file_name;

    // Move the uploaded file from its temporary location to the final destination
    if (move_uploaded_file($file_tmp_name, $destination)) {
        $resume_path = $destination; // Store the path in the database
    } else {
        error_log("Failed to move uploaded resume file to " . $destination . " for application by " . $email);
        // Do not exit, as resume is now optional. Just log the failure.
    }
}
// If no file is uploaded, $resume_path remains null, which is handled by the database schema.

// Prepare SQL statement to prevent SQL injection vulnerabilities
$stmt = $conn->prepare("INSERT INTO applications (job_title, full_name, email, phone, resume_path, message) VALUES (?, ?, ?, ?, ?, ?)");

// 'ssssss' defines the types of the parameters: s for string
// Bind the parameters to the prepared statement
$stmt->bind_param("ssssss", $job_title, $full_name, $email, $phone, $resume_path, $message);

// Execute the statement
if ($stmt->execute()) {
    http_response_code(200); // OK
    echo json_encode(["success" => true, "message" => "Application submitted successfully!"]);
} else {
    // Log the SQL execution error for debugging
    $error_message_for_log = "Failed to execute SQL statement: " . $stmt->error . " for application by " . $email . " on job " . $job_title;
    error_log($error_message_for_log);
    
    // Also include error message in the response for direct debugging
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Failed to submit application to database. SQL Error: " . $stmt->error]);
}

// Close the statement and database connection
$stmt->close();
$conn->close();

?>
