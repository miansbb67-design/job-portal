<?php
/**
 * AJAX Media Library Endpoint
 * Handles asynchronous image fetching and direct computer uploads.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . '/../uploads/articles';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

$action = trim($_GET['action'] ?? ($_POST['action'] ?? 'list'));

// 1. Direct File Upload from Computer (Screenshot 3)
if ($action === 'upload') {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No file received']);
        exit;
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Upload error code: ' . $file['error']]);
        exit;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, WEBP, GIF, and SVG images are allowed']);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Image size cannot exceed 10MB']);
        exit;
    }

    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
    $cleanName = trim(preg_replace('/-+/', '-', $cleanName), '-');
    if (empty($cleanName)) $cleanName = 'media';
    $newFilename = time() . '-' . strtolower($cleanName) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Auto-optimize image and generate companion WebP for ultra-fast loading
        optimize_and_save_image($targetPath, 1366, 82);

        $fileUrl = BASE_URL . '/uploads/articles/' . $newFilename;
        $relPath = 'uploads/articles/' . $newFilename;

        log_activity('upload_media', 'media', "Uploaded media image '{$newFilename}'", null);

        echo json_encode([
            'status' => 'success',
            'message' => 'Image uploaded successfully!',
            'file' => [
                'name' => $newFilename,
                'url' => $fileUrl,
                'rel_path' => $relPath,
                'size' => filesize($targetPath),
                'modified' => filemtime($targetPath)
            ]
        ]);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to save file to server uploads folder']);
        exit;
    }
}

// 2. List All Media Assets
$mediaFiles = [];
$validExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || $f === '.htaccess' || $f === 'index.html') continue;
        $path = $uploadDir . '/' . $f;
        if (is_file($path)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $validExts)) {
                // Skip companion WebP files if their JPG/PNG source exists
                if ($ext === 'webp') {
                    $baseName = pathinfo($f, PATHINFO_FILENAME);
                    if (file_exists($uploadDir . '/' . $baseName . '.jpg') ||
                        file_exists($uploadDir . '/' . $baseName . '.jpeg') ||
                        file_exists($uploadDir . '/' . $baseName . '.png')) {
                        continue;
                    }
                }

                $mediaFiles[] = [
                    'name' => $f,
                    'url' => BASE_URL . '/uploads/articles/' . $f,
                    'rel_path' => 'uploads/articles/' . $f,
                    'size' => filesize($path),
                    'modified' => filemtime($path)
                ];
            }
        }
    }
    // Newest first
    usort($mediaFiles, fn($a, $b) => $b['modified'] <=> $a['modified']);
}

echo json_encode([
    'status' => 'success',
    'total' => count($mediaFiles),
    'files' => $mediaFiles
]);
exit;
