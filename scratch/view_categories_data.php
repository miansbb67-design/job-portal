<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$stmt = $db->query("SELECT id, name, slug, status, image, description FROM categories ORDER BY id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Name: {$r['name']} | Slug: {$r['slug']} | Status: {$r['status']} | Img: {$r['image']}\n";
}
