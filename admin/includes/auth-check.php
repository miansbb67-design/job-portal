<?php
/**
 * Admin Authentication Guard Middleware
 * Restricts access to authenticated administrators only.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!is_admin_logged_in()) {
    set_flash('error', 'Please log in to access the administration portal.');
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// Live DB verification: ensure account exists and status is active
$authDb = getDBConnection();
$authStmt = $authDb->prepare("SELECT id, name, username, email, role, status FROM admins WHERE id = ? LIMIT 1");
$authStmt->execute([(int)$_SESSION['admin_id']]);
$liveAdmin = $authStmt->fetch();

if (!$liveAdmin || $liveAdmin['status'] !== 'active') {
    // Force logout if deleted or deactivated
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    session_start();
    set_flash('error', 'Your administrator account is inactive or has been disabled. Please contact the Super Administrator.');
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

// Sync live DB state into session
$_SESSION['admin_name'] = $liveAdmin['name'];
$_SESSION['admin_username'] = $liveAdmin['username'];
$_SESSION['admin_email'] = $liveAdmin['email'];
$_SESSION['admin_role'] = $liveAdmin['role'];
$_SESSION['admin_status'] = $liveAdmin['status'];

// Live sync permissions so role & access updates apply instantly
get_admin_permission_keys($liveAdmin['id'], true);

$currentUser = get_logged_in_admin();
