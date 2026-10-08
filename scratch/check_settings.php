<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$rows = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "{$r['setting_key']} = {$r['setting_value']}\n";
}
