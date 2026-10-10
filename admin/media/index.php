<?php
/**
 * Media Library Manager
 * Browse uploaded media assets, upload new graphics, copy links, and delete files with media.* permissions.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization Check: Requires media.view
require_permission('media.view');

$adminPageTitle = "Media Library";
require_once __DIR__ . '/../includes/admin-header.php';

$uploadDir = __DIR__ . '/../../uploads/articles';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$errors = [];
$successMsg = '';

// Handle Image Upload
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['media_file'])) {
    if (!is_super_admin() && !has_permission('media.upload')) {
        require_permission('media.upload');
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security session expired. Please refresh and try again.";
    } else {
        $file = $_FILES['media_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload failed with error code: " . $file['error'];
        } else {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts)) {
                $errors[] = "Only JPG, PNG, WEBP, GIF, and SVG files are allowed.";
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = "Image size cannot exceed 5MB.";
            } else {
                $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '-', pathinfo($file['name'], PATHINFO_FILENAME));
                $newFilename = time() . '-' . strtolower($baseName) . '.' . $ext;
                $targetPath = $uploadDir . '/' . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    log_activity('upload_media', 'media', "Uploaded media file '{$newFilename}'", null);
                    $successMsg = "Media file '{$newFilename}' uploaded successfully!";
                } else {
                    $errors[] = "Failed to save the uploaded file to server.";
                }
            }
        }
    }
}

// Read all image files in uploads/articles
$mediaFiles = [];
$validExts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || $f === '.htaccess' || $f === 'index.html') continue;
        $filePath = $uploadDir . '/' . $f;
        if (is_file($filePath)) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $validExts)) {
                $mediaFiles[] = [
                    'name' => $f,
                    'size' => filesize($filePath),
                    'modified' => filemtime($filePath),
                    'url' => (defined('UPLOAD_URL') ? UPLOAD_URL . $f : BASE_URL . '/uploads/articles/' . $f),
                    'path' => $filePath
                ];
            }
        }
    }
    // Sort newest first
    usort($mediaFiles, fn($a, $b) => $b['modified'] <=> $a['modified']);
}
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Media Library
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Store and manage banner images, logos, and article artwork.
        </p>
    </div>

    <div style="font-size:0.875rem; color:#64748b; font-weight:600;">
        Total Files: <strong><?= count($mediaFiles) ?></strong>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:1rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-size:0.9rem;">
    <ul style="margin:0; padding-left:1.5rem;">
        <?php foreach ($errors as $err): ?>
            <li><?= h($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($successMsg)): ?>
<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; padding:1rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-size:0.9rem; font-weight:600; display:flex; align-items:center; gap:0.5rem;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
    <?= h($successMsg) ?>
</div>
<?php endif; ?>

<!-- Upload Box with Dropify (if permitted) -->
<?php if (is_super_admin() || has_permission('media.upload')): ?>
<div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:12px; padding:1.75rem; margin-bottom:2rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <form action="" method="POST" enctype="multipart/form-data" id="mediaUploadForm">
        <?= csrf_field() ?>
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem;">
            <div>
                <div style="font-weight:800; color:#0f172a; font-size:1.1rem; display:flex; align-items:center; gap:0.5rem;">
                    <div style="width:34px; height:34px; border-radius:8px; background:#eff6ff; color:#0284c7; display:flex; align-items:center; justify-content:center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    </div>
                    <span>Upload New Media Asset</span>
                </div>
                <p style="font-size:0.825rem; color:#64748b; margin:0.35rem 0 0 0;">
                    Drag &amp; drop banner images, logos, or article artwork. Supported formats: JPG, PNG, WEBP, GIF, SVG (Max 5MB)
                </p>
            </div>
            <button type="submit" id="btnUploadMedia" class="admin-btn admin-btn-primary" style="padding:0.65rem 1.5rem; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:0.5rem; font-size:0.9rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Upload File
            </button>
        </div>

        <div class="dropify-wrapper-box" style="margin-bottom:0.25rem;">
            <input 
                type="file" 
                name="media_file" 
                id="mediaFileInput" 
                class="dropify" 
                data-height="180" 
                data-max-file-size="5M" 
                data-allowed-file-extensions="jpg jpeg png webp gif svg"
                data-default-file=""
                required
            >
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Media Grid -->
<?php if (empty($mediaFiles)): ?>
<div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:12px; padding:3rem; text-align:center; color:#94a3b8;">
    No media files found in uploads directory.
</div>
<?php else: ?>
<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:1.25rem;">
    <?php foreach ($mediaFiles as $media): ?>
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="height:150px; background:#f8fafc; display:flex; align-items:center; justify-content:center; overflow:hidden; border-bottom:1px solid #f1f5f9; position:relative;">
            <img src="<?= h($media['url']) ?>" alt="<?= h($media['name']) ?>" style="max-height:100%; max-width:100%; object-fit:contain;">
        </div>

        <div style="padding:0.9rem; flex:1; display:flex; flex-direction:column; justify-content:space-between;">
            <div>
                <div style="font-weight:700; font-size:0.85rem; color:#0f172a; word-break:break-all; margin-bottom:0.25rem;">
                    <?= h($media['name']) ?>
                </div>
                <div style="font-size:0.75rem; color:#64748b;">
                    <?= round($media['size'] / 1024, 1) ?> KB &bull; <?= date('M j, Y', $media['modified']) ?>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.75rem; padding-top:0.5rem; border-top:1px solid #f8fafc;">
                <button type="button" onclick="navigator.clipboard.writeText('<?= addslashes($media['url']) ?>'); alert('Direct image URL copied to clipboard!');" class="admin-btn-xs" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-size:0.75rem; font-weight:700; cursor:pointer;" title="Copy Image URL">
                    Copy URL
                </button>

                <a href="<?= h($media['url']) ?>" target="_blank" class="admin-btn-xs" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; text-decoration:none;" title="View Full Image">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </a>

                <?php if (is_super_admin() || has_permission('media.delete')): ?>
                <form action="<?= ADMIN_URL ?>/media/delete.php" method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this media file?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="filename" value="<?= h($media['name']) ?>">
                    <button type="submit" class="admin-btn-xs btn-delete" title="Delete Image">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>

<script>
$(document).ready(function() {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.dropify !== 'undefined') {
        $('#mediaFileInput').dropify({
            messages: {
                'default': 'Drag and drop an image here or click to browse',
                'replace': 'Drag and drop or click to replace image',
                'remove':  'Remove File',
                'error':   'Oops, something went wrong with the file.'
            },
            error: {
                'fileSize': 'The file size is too big ({{ value }} max).',
                'fileExtension': 'The image format is not allowed ({{ value }} only).'
            }
        });
    }
});
</script>
