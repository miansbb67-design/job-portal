<?php
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';

ob_start();
include __DIR__ . '/../index.php';
$html = ob_get_clean();

$lines = explode("\n", $html);
for ($i = 0; $i < 20 && $i < count($lines); $i++) {
    echo $lines[$i] . "\n";
}
