<?php
$files = glob(__DIR__ . '/../uploads/articles/*.*');
foreach ($files as $f) {
    if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'])) {
        $size = filesize($f);
        $info = @getimagesize($f);
        $w = $info ? $info[0] : 'N/A';
        $h = $info ? $info[1] : 'N/A';
        echo basename($f) . " -> " . $w . "x" . $h . " (" . round($size/1024) . " KB)\n";
    }
}
