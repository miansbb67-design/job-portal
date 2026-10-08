<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "ADMINS TABLE COLUMNS:\n";
$stmt = $db->query("DESCRIBE admins");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "  - {$col['Field']} ({$col['Type']}) default: {$col['Default']}\n";
}

echo "\nADMINS ROWS:\n";
$stmt = $db->query("SELECT id, username, email, name, role, status, reset_token, reset_token_expires FROM admins");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "ID: {$r['id']} | Username: {$r['username']} | Email: {$r['email']} | Name: {$r['name']} | Role: {$r['role']} | Status: {$r['status']} | Token: {$r['reset_token']} | Exp: {$r['reset_token_expires']}\n";
}
