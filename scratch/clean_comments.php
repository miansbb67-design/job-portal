<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$db->exec("DELETE FROM comments WHERE id > 1");
$count = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
echo "Remaining comments in database: $count\n";
$rows = $db->query("SELECT id, user_name, user_email, comment_text, status FROM comments")->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
