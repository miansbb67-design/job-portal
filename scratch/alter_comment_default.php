<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$db->exec("ALTER TABLE comments MODIFY COLUMN status ENUM('approved','pending','spam') NOT NULL DEFAULT 'pending'");
echo "Updated comments table status default to 'pending' successfully!\n";

$cols = $db->query("DESCRIBE comments")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    if ($col['Field'] === 'status') {
        echo "Column: {$col['Field']} | Type: {$col['Type']} | Default: {$col['Default']}\n";
    }
}
