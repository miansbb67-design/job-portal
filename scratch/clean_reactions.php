<?php
require 'config/database.php';
$db = getDBConnection();
$db->exec('TRUNCATE TABLE comments');
$db->exec('TRUNCATE TABLE article_reactions');
$db->exec('UPDATE articles SET likes = 0, dislikes = 0');
echo "Cleaned test reactions and comments successfully.\n";
