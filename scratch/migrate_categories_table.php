<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

// Check if status column exists
$columns = $db->query("SHOW COLUMNS FROM categories LIKE 'status'")->fetchAll();
if (empty($columns)) {
    $db->exec("ALTER TABLE categories ADD COLUMN status ENUM('active','inactive') DEFAULT 'active' AFTER description");
    echo "Added 'status' column to categories.\n";
}

// Check if image column exists
$columnsImg = $db->query("SHOW COLUMNS FROM categories LIKE 'image'")->fetchAll();
if (empty($columnsImg)) {
    $db->exec("ALTER TABLE categories ADD COLUMN image VARCHAR(255) NULL AFTER status");
    echo "Added 'image' column to categories.\n";
}

// Set default images for existing categories
$imageMapping = [
    'government-jobs' => 'uploads/articles/corporate-boardroom-meeting.jpg',
    'private-tech-jobs' => 'uploads/articles/business-partnership-deal.jpg',
    'remote-work-from-home' => 'uploads/articles/career-advancement-ladder.jpg',
    'career-advice-tips' => 'uploads/articles/customer-service-agent.jpg',
    'resume-interview-prep' => 'uploads/articles/interview-presentation-desk.jpg',
    'internships-entry-level' => 'uploads/articles/office-desk-support.jpg',
    'trading' => 'uploads/articles/crypto-trading-market.jpg',
    'travel' => 'uploads/articles/autumn-thanksgiving-travel.jpg',
    'finance' => 'uploads/articles/amd-stock-growth-chart.jpg',
];

$upd = $db->prepare("UPDATE categories SET image = ?, status = 'active' WHERE slug = ? AND (image IS NULL OR image = '')");
foreach ($imageMapping as $slug => $imgPath) {
    $upd->execute([$imgPath, $slug]);
}

echo "Categories table schema updated and images seeded.\n";
