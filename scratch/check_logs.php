<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$logs = $db->query("SELECT * FROM activity_logs WHERE module = 'comments' ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($logs);
