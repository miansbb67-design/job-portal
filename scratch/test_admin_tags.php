<?php
session_start();
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = 1;
$_SESSION['admin_role'] = 'superadmin';
$_SESSION['admin_username'] = 'Faizan';
$_SESSION['admin_name'] = 'Faizan';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/admin/tags.php';

ob_start();
require __DIR__ . '/../admin/tags.php';
$html = ob_get_clean();
echo 'Length: ' . strlen($html) . " bytes.\n";
echo 'Contains "Tags": ' . (strpos($html, 'Tags') !== false ? 'YES' : 'NO') . "\n";
echo 'Contains "Add tag": ' . (strpos($html, 'Add tag') !== false ? 'YES' : 'NO') . "\n";
echo 'Contains "Remote Work": ' . (strpos($html, 'Remote Work') !== false ? 'YES' : 'NO') . "\n";
