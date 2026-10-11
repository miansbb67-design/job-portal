<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
$cats = get_all_categories(true);
foreach ($cats as $c) {
    echo "Cat: {$c['name']} (slug: {$c['slug']}) -> Articles: {$c['total_articles']}\n";
}
