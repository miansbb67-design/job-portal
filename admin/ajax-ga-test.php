<?php
/**
 * AJAX Google Analytics Data API Test Endpoint
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/../includes/GoogleAnalyticsService.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin_logged_in() || (!is_super_admin() && !has_permission('dashboard.view'))) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired CSRF security token.']);
    exit;
}

$apiKey = trim($_POST['api_key'] ?? '');
$propertyId = trim($_POST['property_id'] ?? '');

$gaService = GoogleAnalyticsService::getInstance();
$result = $gaService->testConnection($apiKey, $propertyId);

echo json_encode($result);
exit;
