<?php
session_start(); // Start the session if it's not already started

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie (if it exists)
// This will delete the session file on the server.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Redirect to the login page
header("Location: login.php");
exit(); // Always exit after a header redirect
?>