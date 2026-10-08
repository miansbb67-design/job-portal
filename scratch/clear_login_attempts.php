<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();
$db->exec("DELETE FROM login_attempts");
echo "login_attempts table completely cleared.\n";
