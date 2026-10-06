<?php
/**
 * Static Page Editor
 * Allows authorized admins to modify content and SEO descriptions of platform pages.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization Check: Requires pages.edit
require_permission('pages.edit');

$db = getDBConnection();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: " . ADMIN_URL . "/pages/index.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM pages WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    set_flash('error', 'Page not found.');
    header("Location: " . ADMIN_URL . "/pages/index.php");
    exit;
}

$errors = [];
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security session expired. Please refresh and try again.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $metaDescription = trim($_POST['meta_description'] ?? '');
        $status = ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft';
        $content = $_POST['content'] ?? '';

        if (empty($title)) {
            $errors[] = "Page title is required.";
        }

        if (empty($errors)) {
            $upd = $db->prepare("
                UPDATE pages 
                SET title = ?, meta_description = ?, content = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $upd->execute([$title, $metaDescription, $content, $status, $id]);

            log_activity('edit_page', 'pages', "Updated static page '{$title}' (ID: {$id})", $id);
            $successMsg = "Page updated successfully!";

            // Refresh page data
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

$adminPageTitle = "Edit Page";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <a href="<?= ADMIN_URL ?>/pages/index.php" style="color:#64748b; text-decoration:none; font-size:0.875rem; font-weight:600; display:inline-block; margin-bottom:0.25rem;">
            &larr; Back to Pages
        </a>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin:0;">
            Edit Page: <?= h($page['title']) ?>
        </h2>
    </div>

    <div style="display:flex; gap:0.5rem; align-items:center;">
        <a href="<?= BASE_URL ?>/<?= h($page['slug']) ?>.php" target="_blank" class="admin-btn" style="background:#ffffff; border:1px solid #cbd5e1; color:#334155; padding:0.55rem 1rem; border-radius:8px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            Preview Public Page
        </a>
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

<div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:12px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <form action="" method="POST">
        <?= csrf_field() ?>

        <div class="admin-editor-grid" style="margin-bottom:1.5rem;">
            <div>
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    Page Title <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="title" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.95rem; font-weight:600;" value="<?= h($page['title']) ?>" required>
            </div>

            <div>
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    Status
                </label>
                <select name="status" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
                    <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>Published (Live)</option>
                    <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom:1.5rem;">
            <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                Meta SEO Description
            </label>
            <textarea name="meta_description" rows="2" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; resize:vertical;"><?= h($page['meta_description'] ?? '') ?></textarea>
            <span style="font-size:0.75rem; color:#64748b;">Short description for search engine snippet previews.</span>
        </div>

        <div style="margin-bottom:1.75rem;">
            <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                Page HTML Content
            </label>
            <textarea name="content" rows="14" class="admin-form-control" style="width:100%; padding:0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; font-family:var(--font-mono, monospace); line-height:1.6; resize:vertical;"><?= h($page['content'] ?? '') ?></textarea>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.75rem; align-items:center;">
            <a href="<?= ADMIN_URL ?>/pages/index.php" class="admin-btn" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:0.65rem 1.25rem; font-weight:700; border-radius:8px; text-decoration:none;">
                Cancel
            </a>
            <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.65rem 1.5rem; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Save Page
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
