<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$attempts = $db->query("SELECT * FROM login_attempts ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "Recent login attempts:\n";
print_r($attempts);

$admins = $db->query("SELECT id, username, email, status, role FROM admins")->fetchAll(PDO::FETCH_ASSOC);
echo "Admins in DB:\n";
print_r($admins);
