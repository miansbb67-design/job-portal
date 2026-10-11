<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

$stmt = $db->query("
    SELECT t.*, 
        (
            SELECT COUNT(*) 
            FROM articles a 
            WHERE (a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL))
              AND (
                a.tags = t.name 
                OR a.tags LIKE CONCAT(t.name, ',%') 
                OR a.tags LIKE CONCAT('%, ', t.name, '%') 
                OR a.tags LIKE CONCAT('%,', t.name, '%')
              )
        ) AS post_count
    FROM tags t
    ORDER BY post_count DESC, t.name ASC
");
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach (array_slice($tags, 0, 10) as $t) {
    echo "{$t['name']} | /tag/{$t['slug']} | Posts: {$t['post_count']}\n";
}
