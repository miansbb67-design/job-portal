<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$adms = $db->query("SELECT id, username, email, reset_token, reset_token_expires FROM admins")->fetchAll(PDO::FETCH_ASSOC);
echo "Admins reset token status:\n";
print_r($adms);

$logs = $db->query("SELECT * FROM activity_logs WHERE module = 'Authentication' OR description LIKE '%reset%' ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "Recent authentication / reset logs:\n";
print_r($logs);
