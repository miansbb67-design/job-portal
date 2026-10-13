<?php
$files = glob(__DIR__ . '/../*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    preg_match_all('/<img[^>]+>/i', $content, $matches);
    if (!empty($matches[0])) {
        echo basename($file) . ":\n";
        foreach ($matches[0] as $img) {
            echo "  " . htmlspecialchars(substr($img, 0, 100)) . "\n";
        }
    }
}
