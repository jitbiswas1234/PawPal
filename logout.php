<?php
/**
 * PawPal - Logout Script
 */
require_once __DIR__ . '/includes/functions.php';

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Start a new session briefly to transmit the logout flash message
session_start();
setFlash('success', 'You have successfully logged out. Hope to see you and your pets again soon!');

// Redirect to login page
redirect(BASE_URL . '/login.php');
?>
