<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

// Create tags table
$db->exec("
    CREATE TABLE IF NOT EXISTS tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(120) NOT NULL UNIQUE,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Populate tags from existing articles
$stmt = $db->query("SELECT tags FROM articles WHERE tags IS NOT NULL AND tags != ''");
$allTags = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $items = explode(',', $row['tags']);
    foreach ($items as $item) {
        $t = trim($item);
        if (!empty($t)) {
            $allTags[$t] = true;
        }
    }
}

$ins = $db->prepare("INSERT IGNORE INTO tags (name, slug, description) VALUES (?, ?, ?)");
foreach (array_keys($allTags) as $tagName) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tagName), '-'));
    $ins->execute([$tagName, $slug, 'Articles and career guides tagged with ' . $tagName]);
}

$count = $db->query("SELECT COUNT(*) FROM tags")->fetchColumn();
echo "Tags table ready with {$count} tags.\n";
