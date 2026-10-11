<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/job-portal/index.php';

ob_start();
require __DIR__ . '/../index.php';
$html = ob_get_clean();

echo "Restored index.php length: " . strlen($html) . " bytes\n";
if (strpos($html, 'CareerNexaa') !== false) {
    echo "[OK] CareerNexaa found\n";
}
