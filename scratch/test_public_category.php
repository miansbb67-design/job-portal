<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['SCRIPT_NAME'] = '/job-portal/categories.php';
$_GET['slug'] = 'government-jobs';

ob_start();
require __DIR__ . '/../categories.php';
$html = ob_get_clean();

echo "Public category page length: " . strlen($html) . " bytes\n";
if (strpos($html, 'Government Jobs') !== false) {
    echo "[OK] Government Jobs category title found\n";
} else {
    echo "[FAIL] Government Jobs not found\n";
}
if (strpos($html, 'CareerNexaa') !== false) {
    echo "[OK] CareerNexaa brand found\n";
} else {
    echo "[FAIL] Brand not found\n";
}
