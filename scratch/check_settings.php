<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$res = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
print_r($res);
