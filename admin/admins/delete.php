<?php
/**
 * Administrator Deletion Handler
 * Permanently removes an administrator and their custom permission bindings.
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

// Authorization check: Super Admin or has users.delete permission
if (!is_super_admin() && !has_permission('users.delete')) {
    require_permission('users.delete');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    set_flash('error', 'Invalid administrator ID.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Protection 1: Cannot delete self
if ($id == $_SESSION['admin_id']) {
    set_flash('error', 'You cannot delete your own administrator account.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Protection 2: Root Super Admin (ID 1) cannot be deleted
if ($id == 1) {
    set_flash('error', 'The primary Super Administrator account cannot be deleted.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT id, username, role FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    set_flash('error', 'Administrator not found.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Protection 3: Subordinate admin cannot delete a Super Admin
if ($admin['role'] === 'superadmin' && !is_super_admin()) {
    require_permission('super_admin_required');
}

try {
    $db->beginTransaction();

    // Remove permissions pivot entries
    $delPerms = $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
    $delPerms->execute([$id]);

    // Delete administrator record
    $delAdmin = $db->prepare("DELETE FROM admins WHERE id = ?");
    $delAdmin->execute([$id]);

    $db->commit();

    log_activity('delete_admin', 'users', "Permanently deleted administrator '{$admin['username']}' (ID: {$id})", null);
    set_flash('success', "Administrator '{$admin['username']}' has been permanently deleted.");
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    set_flash('error', 'Database error while attempting to delete administrator.');
}

header("Location: " . ADMIN_URL . "/admins/index.php");
exit;
