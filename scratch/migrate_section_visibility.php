<?php
/**
 * Database Migration: Create and Seed section_visibility table
 */
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "Starting section_visibility migration...\n";

$db->exec("
    CREATE TABLE IF NOT EXISTS `section_visibility` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `section_key` VARCHAR(50) NOT NULL UNIQUE,
        `section_name` VARCHAR(100) NOT NULL,
        `section_location` VARCHAR(100) DEFAULT 'Homepage',
        `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
        `sort_order` INT NOT NULL DEFAULT 0,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Verified/Created 'section_visibility' table.\n";

$defaultSections = [
    [
        'key' => 'header',
        'name' => 'Header / Navigation',
        'location' => 'Global Header',
        'order' => 1
    ],
    [
        'key' => 'hero_featured',
        'name' => 'Hero / Featured Guide',
        'location' => 'Homepage Top Showcase',
        'order' => 2
    ],
    [
        'key' => 'live_job_radar',
        'name' => 'Live Job Radar',
        'location' => 'Homepage Top Showcase',
        'order' => 3
    ],
    [
        'key' => 'top_categories',
        'name' => 'Top Categories',
        'location' => 'Homepage Sector Tracks',
        'order' => 4
    ],
    [
        'key' => 'trending_guides',
        'name' => 'Trending Career Guides',
        'location' => 'Homepage (3-Column Grid)',
        'order' => 5
    ],
    [
        'key' => 'jobs_section',
        'name' => 'Jobs Section (Latest Vacancies)',
        'location' => 'Homepage Job Cards',
        'order' => 6
    ],
    [
        'key' => 'career_toolkit',
        'name' => 'Career Success Toolkit',
        'location' => 'Homepage Banner',
        'order' => 7
    ],
    [
        'key' => 'latest_articles',
        'name' => 'Latest Articles & News',
        'location' => 'Homepage (3-Column Grid)',
        'order' => 8
    ],
    [
        'key' => 'footer',
        'name' => 'Footer',
        'location' => 'Global Footer',
        'order' => 9
    ]
];

$stmt = $db->prepare("
    INSERT INTO `section_visibility` (`section_key`, `section_name`, `section_location`, `is_enabled`, `sort_order`)
    VALUES (:key, :name, :location, 1, :order)
    ON DUPLICATE KEY UPDATE 
        `section_name` = VALUES(`section_name`),
        `section_location` = VALUES(`section_location`),
        `sort_order` = VALUES(`sort_order`)
");

foreach ($defaultSections as $sec) {
    $stmt->execute([
        ':key' => $sec['key'],
        ':name' => $sec['name'],
        ':location' => $sec['location'],
        ':order' => $sec['order']
    ]);
    echo "Registered section: {$sec['name']} [{$sec['key']}]\n";
}

echo "Migration finished successfully!\n";
