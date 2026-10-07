<?php
/**
 * Synchronize all files from job-portal to job-portal-old
 */

$src = 'c:/xampp/htdocs/job-portal';
$dst = 'c:/xampp/htdocs/job-portal-old';

if (!is_dir($dst)) {
    die("Destination directory does not exist: {$dst}\n");
}

function recursiveCopy($src, $dst, $skipDirs = ['.git', 'scratch', '.tempmediaStorage']) {
    $dir = opendir($src);
    @mkdir($dst, 0777, true);
    $copied = 0;

    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') continue;
        if (in_array($file, $skipDirs)) continue;

        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;

        if (is_dir($srcPath)) {
            $copied += recursiveCopy($srcPath, $dstPath, $skipDirs);
        } else {
            // Check if different or missing
            if (!file_exists($dstPath) || filemtime($srcPath) > filemtime($dstPath) || filesize($srcPath) !== filesize($dstPath)) {
                copy($srcPath, $dstPath);
                $copied++;
            }
        }
    }
    closedir($dir);
    return $copied;
}

echo "Starting synchronization...\n";
$count = recursiveCopy($src, $dst);
echo "Synchronization complete! Copied/Updated {$count} files to {$dst}.\n";
