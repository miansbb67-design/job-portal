<?php
/**
 * AJAX Notifications Endpoint
 * Handles asynchronous notification actions like mark-as-read and live fetch.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

header('Content-Type: application/json; charset=utf-8');

$action = trim($_GET['action'] ?? ($_POST['action'] ?? ''));

if ($action === 'mark_all_read') {
    $_SESSION['admin_last_read_notif_time'] = time();
    echo json_encode([
        'status' => 'success',
        'message' => 'All notifications marked as read',
        'read_time' => $_SESSION['admin_last_read_notif_time']
    ]);
    exit;
}

// Default: return recent notifications
try {
    $db = getDBConnection();
    $lastRead = (int)($_SESSION['admin_last_read_notif_time'] ?? 0);
    $stmt = $db->query("
        SELECT id, admin_id, admin_name, action, module, description, created_at 
        FROM activity_logs 
        ORDER BY id DESC 
        LIMIT 10
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unreadCount = 0;
    foreach ($items as &$item) {
        $ts = strtotime($item['created_at']);
        $item['timestamp'] = $ts;
        $item['is_unread'] = ($ts > $lastRead);
        if ($item['is_unread']) $unreadCount++;
    }

    echo json_encode([
        'status' => 'success',
        'unread_count' => $unreadCount,
        'notifications' => $items
    ]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
