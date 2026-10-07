<?php
/**
 * Database Migration: Add Comments & Likes/Dislikes Reactions System
 */
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

echo "Starting migration...\n";

// 1. Check and add 'likes' column to articles
$cols = $db->query("SHOW COLUMNS FROM articles LIKE 'likes'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE articles ADD COLUMN likes INT NOT NULL DEFAULT 0 AFTER views");
    echo "Added 'likes' column to articles.\n";
} else {
    echo "'likes' column already exists.\n";
}

// 2. Check and add 'dislikes' column to articles
$cols = $db->query("SHOW COLUMNS FROM articles LIKE 'dislikes'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE articles ADD COLUMN dislikes INT NOT NULL DEFAULT 0 AFTER likes");
    echo "Added 'dislikes' column to articles.\n";
} else {
    echo "'dislikes' column already exists.\n";
}

// 3. Create article_reactions table
$db->exec("
    CREATE TABLE IF NOT EXISTS `article_reactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `article_id` INT NOT NULL,
        `reaction_type` ENUM('like', 'dislike') NOT NULL,
        `user_ip` VARCHAR(45) NOT NULL,
        `user_agent` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `idx_article_ip` (`article_id`, `user_ip`),
        KEY `idx_article_id` (`article_id`),
        CONSTRAINT `fk_reactions_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Verified/Created 'article_reactions' table.\n";

// 4. Create comments table
$db->exec("
    CREATE TABLE IF NOT EXISTS `comments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `article_id` INT NOT NULL,
        `user_name` VARCHAR(100) NOT NULL,
        `user_email` VARCHAR(150) NOT NULL,
        `comment_text` TEXT NOT NULL,
        `status` ENUM('approved', 'pending', 'spam') NOT NULL DEFAULT 'approved',
        `user_ip` VARCHAR(45) NOT NULL,
        `user_agent` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_article_id` (`article_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_at` (`created_at`),
        CONSTRAINT `fk_comments_article` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "Verified/Created 'comments' table.\n";

echo "Migration finished successfully!\n";
