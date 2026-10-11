<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'super_admin';
$_SESSION['admin_username'] = 'Faizan';
$_SESSION['admin_permissions'] = ['*'];

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/job-portal/admin/tags.php';
$_SERVER['PHP_SELF'] = '/job-portal/admin/tags.php';

ob_start();
require __DIR__ . '/../admin/tags.php';
$output = ob_get_clean();

echo "Rendered tags.php length: " . strlen($output) . " bytes\n";
if (strpos($output, '#059669') !== false) {
    echo "[OK] Emerald Green brand color applied\n";
} else {
    echo "[FAIL] Brand color missing\n";
}
