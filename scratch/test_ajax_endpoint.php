<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'super_admin';
$_SESSION['admin_username'] = 'admin';

require_once __DIR__ . '/../includes/functions.php';
$token = generate_csrf_token();

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['csrf_token'] = $token;
$_POST['api_key'] = 'AIzaSyCCWXlnB6t_OwDg_yzTZ3UuT3i2-v75jBQ';
$_POST['property_id'] = '';

ob_start();
include __DIR__ . '/../admin/ajax-ga-test.php';
$resp = ob_get_clean();

echo "AJAX Endpoint Output: " . $resp . "\n";
$decoded = json_decode($resp, true);
if ($decoded) {
    echo "PASS: Valid JSON response\n";
    echo "Status Code: " . ($decoded['status_code'] ?? 'N/A') . "\n";
    echo "Message: " . ($decoded['message'] ?? 'N/A') . "\n";
} else {
    echo "FAIL: Invalid JSON\n";
}
