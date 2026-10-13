<?php
$lines = file(__DIR__ . '/../assets/css/style.css');
foreach ($lines as $num => $line) {
    if (preg_match('/(thumb|image|img|media-wrap|spotlight)/i', $line)) {
        if (strpos($line, '{') !== false || strpos($line, '.') !== false) {
            echo ($num + 1) . ": " . trim($line) . "\n";
        }
    }
}
