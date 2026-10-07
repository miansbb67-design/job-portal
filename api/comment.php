<?php
/**
 * API Endpoint: Article Comment Submission
 * Processes comment, validates, performs anti-spam checks, and saves to database.
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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$articleId = (int)($data['article_id'] ?? 0);
$name = trim((string)($data['user_name'] ?? ''));
$email = trim((string)($data['user_email'] ?? ''));
$commentText = trim((string)($data['comment_text'] ?? ''));
$token = $data['csrf_token'] ?? '';
$honeypot = trim((string)($data['comments_bot_trap'] ?? ($data['website_url'] ?? '')));

// Anti-Spam Honeypot: Bots will fill this invisible field
if (!empty($honeypot)) {
    // Silently reject spam bots with successful looking message
    echo json_encode(['success' => true, 'message' => 'Thank you! Your comment has been received.']);
    exit;
}

// Verify CSRF Token
if (!verify_csrf_token($token)) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid or expired session token. Please refresh the page and try again.',
        'new_csrf_token' => generate_csrf_token()
    ]);
    exit;
}

$res = submit_article_comment($articleId, $name, $email, $commentText);

if (!$res['success']) {
    http_response_code(400);
}

// Attach fresh CSRF token so next post without refresh succeeds
$res['new_csrf_token'] = generate_csrf_token();

echo json_encode($res);
exit;
