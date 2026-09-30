<?php
/**
 * PawPal - Authentication Middleware
 * Include this at the top of protected pages
 */

require_once __DIR__ . '/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlash('warning', 'Please log in to continue.');
    redirect(BASE_URL . '/login.php');
}
