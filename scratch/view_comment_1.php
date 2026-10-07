<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$comm = $db->query("SELECT * FROM comments WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
echo "Comment 1:\n";
print_r($comm);
