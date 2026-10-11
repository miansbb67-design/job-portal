<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "=== TABLES ===\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\n=== CATEGORIES COLUMNS ===\n";
$cols = $db->query("DESCRIBE categories")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col) {
    echo "{$col['Field']} - {$col['Type']}\n";
}
