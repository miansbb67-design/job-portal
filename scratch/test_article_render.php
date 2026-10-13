<?php
$_SERVER['REQUEST_URI'] = '/article.php?slug=top-10-high-paying-remote-tech-careers-2026';
$_SERVER['PHP_SELF'] = '/article.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_GET['slug'] = 'top-10-high-paying-remote-tech-careers-2026';

ob_start();
try {
    include __DIR__ . '/../article.php';
    $output = ob_get_clean();
    echo "SUCCESS! Rendered single article: " . strlen($output) . " bytes\n";
    if (strpos($output, 'single-featured-image') !== false) {
        echo "PASS: single-featured-image found\n";
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "ERROR: " . $e->getMessage() . "\n";
}
