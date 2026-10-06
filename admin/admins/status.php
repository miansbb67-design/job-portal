<?php
/**
 * Administrator Status Toggle Handler
 * Activates or deactivates an administrator account.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash('error', 'Security session expired. Please try again.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Authorization check: Super Admin or has users.edit permission
if (!is_super_admin() && !has_permission('users.edit')) {
    require_permission('users.edit');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    set_flash('error', 'Invalid administrator ID.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Protection 1: Cannot disable self
if ($id == $_SESSION['admin_id']) {
    set_flash('error', 'You cannot change your own account status.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Protection 2: Cannot disable primary Super Admin (ID 1)
if ($id == 1) {
    set_flash('error', 'The primary Super Administrator account cannot be deactivated.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT id, username, role, status FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    set_flash('error', 'Administrator not found.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Protection 3: Subordinate admins cannot toggle a Super Admin
if ($admin['role'] === 'superadmin' && !is_super_admin()) {
    require_permission('super_admin_required');
}

$newStatus = ($admin['status'] === 'active') ? 'inactive' : 'active';

$upd = $db->prepare("UPDATE admins SET status = ?, updated_at = NOW() WHERE id = ?");
$upd->execute([$newStatus, $id]);

$actionLabel = ($newStatus === 'active') ? 'activated' : 'deactivated';
log_activity('status_change', 'users', "Administrator '{$admin['username']}' (ID: {$id}) was {$actionLabel}.", $id);

set_flash('success', "Administrator '{$admin['username']}' has been {$actionLabel} successfully.");
header("Location: " . ADMIN_URL . "/admins/index.php");
exit;
