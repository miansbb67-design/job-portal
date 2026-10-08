<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$logs = $db->query("SELECT id, admin_id, admin_name, action, module, description, created_at FROM activity_logs ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($logs as $l) {
    echo "[{$l['created_at']}] [{$l['module']}] {$l['action']}: {$l['description']}\n";
}
