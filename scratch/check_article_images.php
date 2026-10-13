<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$rows = $db->query("SELECT id, title, featured_image, thumbnail_image FROM articles WHERE is_trash = 0 ORDER BY id DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID: " . $r['id'] . " | " . $r['title'] . "\n";
    echo "  Featured:  " . $r['featured_image'] . "\n";
    echo "  Thumbnail: " . $r['thumbnail_image'] . "\n";
}
