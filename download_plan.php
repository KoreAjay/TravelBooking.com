
<?php
session_start();
include 'include/db.php'; // Ensure your database connection is included

// Helper function to display an error page and terminate.
function displayError($message, $redirect_link = 'generate_trip.php') {
    // It's good practice to log these errors for debugging and security monitoring
    error_log("Download Error: " . $message . " | User ID: " . ($_SESSION['user_id'] ?? 'Guest') . " | IP: " . $_SERVER['REMOTE_ADDR']);

    http_response_code(404); // Use 404 Not Found as a general default for non-existent resources
    // If it's an authorization issue, 403 Forbidden is more appropriate:
    // http_response_code(403);

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Download Error</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap' rel='stylesheet'>
        <style>
            body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
            .error-container { text-align: center; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            h1 { color: #dc3545; }
            p { color: #6c757d; }
            .btn { margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='error-container'>
            <h1>Oops!</h1>
            <p>" . htmlspecialchars($message) . "</p>
            <a href='" . htmlspecialchars($redirect_link) . "' class='btn btn-primary'>Go back</a>
        </div>
    </body>
    </html>";
    exit;
}

// --- 1. Input Validation and Sanitization for plan_id ---
$plan_id = $_GET['plan_id'] ?? '';

// Validate plan_id: it must be a positive integer as it's an AUTO_INCREMENT PK
if (empty($plan_id) || !filter_var($plan_id, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)))) {
    displayError("Invalid trip plan identifier provided.");
}

// Ensure database connection is available
if (!isset($conn) || $conn->connect_error) {
    displayError("Database connection failed. Please try again later.");
}

// --- 2. Authentication/Authorization (CRITICAL SECURITY STEP) ---
// This is where you verify if the logged-in user is authorized to download THIS plan.
$user_id = $_SESSION['user_id'] ?? null; // Get user ID from session

// Prepare query to fetch plan content and its associated user_id
// We select user_id to perform the authorization check
$sql = "SELECT destination, start_date, end_date, interests, generated_content, user_id FROM trip_plans WHERE plan_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    displayError("Failed to prepare database statement: " . $conn->error);
}

// 'i' for integer binding since plan_id is assumed to be an integer
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    displayError("The trip plan you requested was not found.");
}

$plan_data = $result->fetch_assoc();
$stmt->close();

// --- Authorization Check ---
// If the plan is associated with a user, ensure it's the logged-in user.
// If user_id is NULL in the DB (for guest plans), no specific user needs to be logged in.
if ($plan_data['user_id'] !== null && $plan_data['user_id'] !== $user_id) {
    // Log unauthorized access attempt
    error_log("Unauthorized download attempt for plan_id: " . $plan_id . " by user_id: " . $user_id . " (Owner: " . $plan_data['user_id'] . ")");
    displayError("You are not authorized to download this trip plan. Please log in with the correct account.", "login.php");
}

// --- Prepare Content for Download ---
$plan_content = $plan_data['generated_content'];

// Fallback to basic generation if 'generated_content' is somehow empty in DB
if (empty($plan_content)) {
    $destination = htmlspecialchars($plan_data['destination']);
    $start_date = htmlspecialchars(date('d M, Y', strtotime($plan_data['start_date'])));
    $end_date = htmlspecialchars(date('d M, Y', strtotime($plan_data['end_date'])));
    $interests = htmlspecialchars($plan_data['interests']);

    $plan_content = "Proposed Itinerary for " . $destination . "\n\n";
    $plan_content .= "Dates: " . $start_date . " to " . $end_date . "\n";
    $plan_content .= "Interests: " . $interests . "\n\n";
    $plan_content .= "Day 1: Arrival & Exploration\n";
    $plan_content .= "Arrive in " . $destination . ". Check into your accommodation. Explore a local market or a famous landmark.\n\n";
    $plan_content .= "Day 2: Adventure & Nature\n";
    $plan_content .= "Enjoy a nature hike or visit a wildlife sanctuary based on your interests.\n\n";
    $plan_content .= "Day 3: Culture & Cuisine\n";
    $plan_content .= "Immerse yourself in local culture and taste authentic cuisine.\n\n";
    $plan_content .= "Enjoy your trip with Sair Karo!";
}


$final_filename_for_download = "TripPlan_" . preg_replace("/[^a-zA-Z0-9]/", "_", $plan_data['destination']) . "_" . $plan_id . ".txt";
$mime_type = 'text/plain'; // We are providing plain text content for download

// --- Serve the content ---
$filesize = strlen($plan_content); // Get size of the generated content

// Set headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
// Force download with a user-friendly filename
header('Content-Disposition: attachment; filename="' . $final_filename_for_download . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); // More robust cache control
header('Pragma: public');
header('Content-Length: ' . $filesize);

// Clear output buffer and flush system output buffer
ob_clean();
flush();

echo $plan_content; // Output the fetched/generated content
exit; // Terminate script after file is sent

?>