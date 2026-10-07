<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

$art = $db->query("SELECT id, title, slug FROM articles WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
echo "Testing on Article 1: {$art['title']}\n";

$res = submit_article_comment(1, "Ali Khan", "ali@example.com", "Great article on remote jobs!");
echo "Result:\n";
print_r($res);
