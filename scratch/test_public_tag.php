<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/tag/remote-work';
$_GET['slug'] = 'remote-work';

ob_start();
require __DIR__ . '/../tag.php';
$html = ob_get_clean();
echo 'Length: ' . strlen($html) . " bytes.\n";
echo 'Contains "#Remote Work": ' . (strpos($html, 'Remote Work') !== false ? 'YES' : 'NO') . "\n";
echo 'Contains article title: ' . (strpos($html, 'Top 10 High-Paying') !== false ? 'YES' : 'NO') . "\n";
