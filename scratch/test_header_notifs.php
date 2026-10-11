<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$res = $db->query("
    SELECT a.id, a.title, a.slug, a.is_job_posting, a.job_company, a.published_at, c.name as category_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL) 
    ORDER BY a.published_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($res) . "\n";
foreach ($res as $row) {
    echo "- [" . ($row['is_job_posting'] ? 'JOB' : 'GUIDE') . "] " . $row['title'] . " (" . $row['category_name'] . ")\n";
}
