<?php
/**
 * Admin Panel - Logout Handler
 * Securely destroys session data and authentication tokens.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Log activity before destroying session
if (is_admin_logged_in()) {
    log_activity('LOGOUT', 'Authentication', 'Admin logged out gracefully.');
}

// Unset all session variables
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a fresh session to pass a clean flash message
session_start();
set_flash('success', 'You have been successfully logged out.');

header('Location: ' . ADMIN_URL . '/login.php');
exit;
