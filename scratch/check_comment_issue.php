<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

echo "=== COMMENTS TABLE CHECK ===\n";
try {
    $cols = $db->query("DESCRIBE comments")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "{$col['Field']} ({$col['Type']}) Null:{$col['Null']} Key:{$col['Key']} Default:{$col['Default']}\n";
    }
} catch (Exception $e) {
    echo "Error describing comments: " . $e->getMessage() . "\n";
}

echo "\n=== EXISTING COMMENTS COUNT ===\n";
$cnt = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
echo "Total comments in DB: $cnt\n";

$rows = $db->query("SELECT id, article_id, user_name, user_email, status, created_at FROM comments ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

echo "\n=== ARTICELS CHECK ===\n";
$articles = $db->query("SELECT id, title, slug, status, is_trash FROM articles WHERE status = 'published' AND is_trash = 0 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($articles);
