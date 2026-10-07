<?php
/**
 * API Endpoint: Article Reaction Handler (Like / Dislike)
 * Returns JSON response with updated reaction status and counts.
 */
// Enable CORS for LAN IP / same domain access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Read JSON or POST form data
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$articleId = (int)($data['article_id'] ?? 0);
$type = trim((string)($data['type'] ?? ''));
$token = $data['csrf_token'] ?? '';

// Verify CSRF Token
if (!verify_csrf_token($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired session token. Please refresh the page.']);
    exit;
}

if ($articleId <= 0 || !in_array($type, ['like', 'dislike'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit;
}

$result = toggle_article_reaction($articleId, $type);

if (!$result['success']) {
    http_response_code(400);
}

echo json_encode($result);
exit;
