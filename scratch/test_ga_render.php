<?php
// Mock session as logged in admin
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'super_admin';
$_SESSION['admin_username'] = 'admin';

$_SERVER['REQUEST_URI'] = '/admin/analytics.php?tab=google';
$_SERVER['PHP_SELF'] = '/admin/analytics.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET['tab'] = 'google';

ob_start();
try {
    include __DIR__ . '/../admin/analytics.php';
    $output = ob_get_clean();
    echo "SUCCESS! Rendered Google Analytics Tab: " . strlen($output) . " bytes\n";
    
    if (strpos($output, 'Google Analytics 4 Intelligence Hub') !== false) {
        echo "PASS: Hub title found\n";
    } else {
        echo "FAIL: Hub title NOT found\n";
    }

    if (strpos($output, 'AIzaSyCCWXlnB6t_OwDg_yzTZ3UuT3i2-v75jBQ') !== false) {
        echo "PASS: API key found in form input\n";
    } else {
        echo "FAIL: API key NOT found in form input\n";
    }

    if (strpos($output, 'G-XH87VZP3DS') !== false) {
        echo "PASS: G-XH87VZP3DS Measurement ID found\n";
    } else {
        echo "FAIL: G-XH87VZP3DS NOT found\n";
    }

    if (strpos($output, 'btnTestGaApi') !== false) {
        echo "PASS: Test API button found\n";
    } else {
        echo "FAIL: Test API button NOT found\n";
    }

} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
