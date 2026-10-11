<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'super_admin';
$_SESSION['admin_username'] = 'Faizan';
$_SESSION['admin_permissions'] = ['*'];

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/job-portal/admin/section-visibility.php';
$_SERVER['PHP_SELF'] = '/job-portal/admin/section-visibility.php';

ob_start();
require __DIR__ . '/../admin/section-visibility.php';
$html = ob_get_clean();

if (strpos($html, 'NOTIFICATION') !== false) {
    echo "[OK] NOTIFICATION section found in admin/section-visibility.php\n";
} else {
    echo "[FAIL] NOTIFICATION not found\n";
}

if (strpos($html, 'sections[notification][title]') !== false) {
    echo "[OK] sections[notification] inputs found in form\n";
} else {
    echo "[FAIL] sections[notification] inputs not found\n";
}
