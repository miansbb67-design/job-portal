<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$adms = $db->query("SELECT id, username, email, password_hash FROM admins")->fetchAll(PDO::FETCH_ASSOC);
foreach ($adms as $a) {
    echo "ID: {$a['id']}, Username: {$a['username']}, Email: {$a['email']}\n";
    echo "Matches Admin@12345: " . (password_verify('Admin@12345', $a['password_hash']) ? 'YES' : 'NO') . "\n";
    echo "Matches Admin@123: " . (password_verify('Admin@123', $a['password_hash']) ? 'YES' : 'NO') . "\n";
    echo "Matches password: " . (password_verify('password', $a['password_hash']) ? 'YES' : 'NO') . "\n";
}
