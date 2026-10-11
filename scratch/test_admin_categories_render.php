<?php
// Mock super admin session
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'super_admin';
$_SESSION['admin_username'] = 'Faizan';
$_SESSION['admin_permissions'] = ['*'];

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/job-portal/admin/categories.php';
$_SERVER['PHP_SELF'] = '/job-portal/admin/categories.php';

ob_start();
require __DIR__ . '/../admin/categories.php';
$output = ob_get_clean();

echo "Rendered length: " . strlen($output) . " bytes\n";
if (strpos($output, 'pinecast-categories-table') !== false) {
    echo "[OK] pinecast-categories-table found in output\n";
} else {
    echo "[FAIL] pinecast-categories-table NOT found\n";
}

if (strpos($output, 'Government Jobs') !== false && strpos($output, 'Trading') !== false && strpos($output, 'Finance') !== false) {
    echo "[OK] All existing categories found in output\n";
} else {
    echo "[FAIL] Some categories missing\n";
}

if (strpos($output, '+ New Category') !== false) {
    echo "[OK] + New Category button found in output\n";
} else {
    echo "[FAIL] + New Category button missing\n";
}

if (strpos($output, 'CareerNexaa') !== false) {
    echo "[OK] CareerNexaa branding retained\n";
} else {
    echo "[FAIL] CareerNexaa branding missing\n";
}
