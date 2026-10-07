<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$exists = $db->query("SELECT COUNT(*) FROM section_visibility WHERE section_key = 'article_comments'")->fetchColumn();
if (!$exists) {
    $db->prepare("
        INSERT INTO section_visibility (section_key, section_name, section_location, is_enabled, sort_order)
        VALUES ('article_comments', 'Article Comments & Discussion', 'Article Detail Page', 1, 10)
    ")->execute();
    echo "Added 'article_comments' to section_visibility table.\n";
} else {
    echo "'article_comments' already exists in section_visibility table.\n";
}
