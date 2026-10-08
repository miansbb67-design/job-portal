<?php
/**
 * Create Clean Production Deployment ZIP Archive
 */
$sourceDir = realpath(__DIR__ . '/..');
$zipFileName = 'careernexaa-production.zip';
$targetZip = $sourceDir . DIRECTORY_SEPARATOR . $zipFileName;
$altTargetZip = dirname($sourceDir) . DIRECTORY_SEPARATOR . $zipFileName;

if (file_exists($targetZip)) {
    @unlink($targetZip);
}
if (file_exists($altTargetZip)) {
    @unlink($altTargetZip);
}

echo "Building production zip archive...\n";
echo "Source: {$sourceDir}\n";

$zip = new ZipArchive();
if ($zip->open($targetZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Error: Cannot create zip file at {$targetZip}\n");
}

// Exclude list
$excludeDirs = ['.git', 'scratch'];
$excludeFiles = ['careernexaa-production.zip', 'cookie.txt', 'test_cookie.txt'];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$fileCount = 0;
$totalBytes = 0;

foreach ($files as $file) {
    $filePath = $file->getRealPath();
    $relativePath = substr($filePath, strlen($sourceDir) + 1);
    $normalizedRel = str_replace('\\', '/', $relativePath);

    // Check exclusion
    $skip = false;
    foreach ($excludeDirs as $exDir) {
        if ($normalizedRel === $exDir || strpos($normalizedRel, $exDir . '/') === 0) {
            $skip = true;
            break;
        }
    }
    foreach ($excludeFiles as $exFile) {
        if (basename($normalizedRel) === $exFile) {
            $skip = true;
            break;
        }
    }

    if ($skip) continue;

    if ($file->isDir()) {
        $zip->addEmptyDir($normalizedRel);
    } else if ($file->isFile()) {
        $zip->addFile($filePath, $normalizedRel);
        $fileCount++;
        $totalBytes += $file->getSize();
    }
}

// Ensure .htaccess files are explicitly included
$rootHtaccess = $sourceDir . DIRECTORY_SEPARATOR . '.htaccess';
if (file_exists($rootHtaccess)) {
    $zip->addFile($rootHtaccess, '.htaccess');
}
$uploadsHtaccess = $sourceDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . '.htaccess';
if (file_exists($uploadsHtaccess)) {
    $zip->addFile($uploadsHtaccess, 'uploads/.htaccess');
}

$zip->close();

// Also copy to parent htdocs directory for easy access
copy($targetZip, $altTargetZip);

$zipSizeMb = round(filesize($targetZip) / (1024 * 1024), 2);
$zipSizeKb = round(filesize($targetZip) / 1024, 2);

echo "\n======================================================\n";
echo "✓ ZIP ARCHIVE CREATED SUCCESSFULLY!\n";
echo "======================================================\n";
echo "Files included : {$fileCount} files\n";
echo "Uncompressed   : " . round($totalBytes / (1024 * 1024), 2) . " MB\n";
echo "Compressed Size: {$zipSizeMb} MB ({$zipSizeKb} KB)\n\n";
echo "Saved Locations:\n";
echo "1. {$targetZip}\n";
echo "2. {$altTargetZip}\n";
echo "======================================================\n";
