<?php
/**
 * Media File Deletion Handler
 * Removes an uploaded graphic asset from server disk with media.delete authorization.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . ADMIN_URL . "/media/index.php");
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash('error', 'Security session expired. Please try again.');
    header("Location: " . ADMIN_URL . "/media/index.php");
    exit;
}

// Authorization Check: Requires media.delete
if (!is_super_admin() && !has_permission('media.delete')) {
    require_permission('media.delete');
}

$rawFilename = $_POST['filename'] ?? '';
$filename = basename($rawFilename); // Path traversal prevention

$uploadDir = __DIR__ . '/../../uploads/articles';
$targetPath = $uploadDir . '/' . $filename;

if (empty($filename) || !is_file($targetPath)) {
    set_flash('error', 'Target media file does not exist.');
    header("Location: " . ADMIN_URL . "/media/index.php");
    exit;
}

// Prevent deletion of protected system files
if ($filename === '.htaccess' || $filename === 'index.html') {
    set_flash('error', 'Cannot delete protected system directory files.');
    header("Location: " . ADMIN_URL . "/media/index.php");
    exit;
}

if (unlink($targetPath)) {
    log_activity('delete_media', 'media', "Permanently deleted media file '{$filename}'", null);
    set_flash('success', "Media file '{$filename}' deleted successfully.");
} else {
    set_flash('error', "Could not delete media file. Check server file permissions.");
}

header("Location: " . ADMIN_URL . "/media/index.php");
exit;
