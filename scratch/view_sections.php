<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$secs = $db->query("SELECT * FROM section_visibility ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($secs as $s) {
    echo "{$s['id']} | Key: {$s['section_key']} | Name: {$s['section_name']} | Title: {$s['title']} | Sub: {$s['subtitle']} | Enabled: {$s['is_enabled']}\n";
}
