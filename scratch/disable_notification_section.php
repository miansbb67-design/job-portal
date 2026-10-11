<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$db->exec("UPDATE section_visibility SET is_enabled = 0 WHERE section_key IN ('notification', 'newsletter')");
echo "Disabled notification & newsletter sections in database.\n";
