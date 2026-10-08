<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES IN DATABASE:\n";
print_r($tables);

foreach ($tables as $t) {
    $cols = $db->query("DESCRIBE `$t`")->fetchAll(PDO::FETCH_COLUMN);
    $hasEmail = false;
    foreach ($cols as $col) {
        if (stripos($col, 'email') !== false) {
            $hasEmail = true;
            break;
        }
    }
    if ($hasEmail) {
        echo "\nTable '$t' has email column. Rows:\n";
        $rows = $db->query("SELECT * FROM `$t` LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        print_r($rows);
    }
}
