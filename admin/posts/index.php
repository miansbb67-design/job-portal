<?php
/**
 * Blog Posts Management Console
 * Lists, filters, and manages all blog articles with granular posts.* permissions.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Backend Authorization Check: Requires posts.view permission
$db = getDBConnection();

// Status quick toggle handler (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        require_permission('posts.publish');
        $id = (int)($_POST['id'] ?? 0);
        $checkStmt = $db->prepare("SELECT id, title, status FROM articles WHERE id = ? AND (is_job_posting = 0 OR is_job_posting IS NULL) LIMIT 1");
        $checkStmt->execute([$id]);
        $artRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($artRow) {
            $newStatus = ($artRow['status'] === 'published') ? 'draft' : 'published';
            $publishedAtSql = ($newStatus === 'published') ? ", published_at = IFNULL(published_at, NOW())" : "";

            $toggleStmt = $db->prepare("UPDATE articles SET status = ? {$publishedAtSql} WHERE id = ?");
            $toggleStmt->execute([$newStatus, $id]);

            $actionName = ($newStatus === 'published') ? 'publish' : 'unpublish';
            log_activity($actionName, 'posts', "Changed status of article '{$artRow['title']}' to {$newStatus}", $id);
            set_flash('success', "Post status updated to " . ucfirst($newStatus) . ".");
        }
    }
    header('Location: ' . ADMIN_URL . '/posts/index.php');
    exit;
}

$adminPageTitle = "Manage Blog Posts";
require_once __DIR__ . '/../includes/admin-header.php';

// Fetch all standard blog posts
$sql = "
    SELECT 
        a.id, 
        a.title, 
        a.slug, 
        a.status, 
        a.created_at, 
        c.name as category_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.is_job_posting = 0 OR a.is_job_posting IS NULL
    ORDER BY a.created_at DESC
";
$posts = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$totalPosts = count($posts);
$publishedPosts = count(array_filter($posts, fn($p) => $p['status'] === 'published'));
$draftPosts = count(array_filter($posts, fn($p) => $p['status'] === 'draft'));
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Blog Articles &amp; Insights
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Publish career advice, industry insights, and informative articles.
        </p>
    </div>

    <?php if (is_super_admin() || has_permission('posts.create')): ?>
    <a href="<?= ADMIN_URL ?>/add-article.php?type=post" class="admin-btn admin-btn-primary" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.25rem; font-weight:700; border-radius:8px; text-decoration:none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Write New Article
    </a>
    <?php endif; ?>
</div>

<!-- Metrics Row -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Total Articles</div>
        <div style="font-size:1.75rem; font-weight:800; color:#0f172a;"><?= $totalPosts ?></div>
    </div>
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Published Articles</div>
        <div style="font-size:1.75rem; font-weight:800; color:#059669;"><?= $publishedPosts ?></div>
    </div>
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Drafts</div>
        <div style="font-size:1.75rem; font-weight:800; color:#d97706;"><?= $draftPosts ?></div>
    </div>
</div>

<!-- Posts Table -->
<div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid var(--admin-border); color:#475569; font-weight:700; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.04em;">
                    <th style="padding:0.9rem 1.25rem;">ID</th>
                    <th style="padding:0.9rem 1.25rem;">Article Title</th>
                    <th style="padding:0.9rem 1.25rem;">Category</th>
                    <th style="padding:0.9rem 1.25rem; white-space:nowrap;">Status</th>
                    <th style="padding:0.9rem 1.25rem;">Published Date</th>
                    <th style="padding:0.9rem 1.25rem; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                <tr>
                    <td colspan="6" style="padding:2.5rem; text-align:center; color:#94a3b8;">
                        No blog posts found. Click "Write New Article" above to create one.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:1rem 1.25rem; color:#64748b; font-weight:600;">
                            #<?= (int)$post['id'] ?>
                        </td>
                        <td style="padding:1rem 1.25rem;">
                            <div>
                                <a href="<?= BASE_URL ?>/article/<?= urlencode($post['slug']) ?>" target="_blank" class="admin-table-title-link" title="Open live article in new tab">
                                    <?= h($post['title']) ?>
                                </a>
                            </div>
                            <div style="margin-top:0.35rem;">
                                <a href="<?= BASE_URL ?>/article/<?= urlencode($post['slug']) ?>" target="_blank" class="admin-table-slug-link" title="View live article">
                                    <span>/article/<?= h($post['slug']) ?></span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                            </div>
                        </td>
                        <td style="padding:1rem 1.25rem; color:#475569;">
                            <span style="background:#f1f5f9; padding:0.2rem 0.55rem; border-radius:4px; font-size:0.8rem; font-weight:600;">
                                <?= h($post['category_name'] ?? 'Uncategorized') ?>
                            </span>
                        </td>
                        <td style="padding:1rem 1.25rem; white-space:nowrap;">
                            <?php if (is_super_admin() || has_permission('posts.publish')): ?>
                            <form action="<?= ADMIN_URL ?>/posts/index.php" method="POST" style="display:inline; margin:0;">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                <button type="submit" class="admin-status-pill is-<?= $post['status'] ?>" title="Click to toggle status (Change to <?= ($post['status'] === 'published') ? 'Draft' : 'Published' ?>)">
                                    <?php if ($post['status'] === 'published'): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <span>Published</span>
                                    <?php else: ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <span>Draft</span>
                                    <?php endif; ?>
                                    <svg class="pill-switch-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M7 16V4m0 0L3 8m4-4l4 4m6 4v12m0 0l4-4m-4 4l-4-4"/></svg>
                                </button>
                            </form>
                            <?php else: ?>
                                <span class="admin-status-pill is-<?= $post['status'] ?> is-static">
                                    <?php if ($post['status'] === 'published'): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <span>Published</span>
                                    <?php else: ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <span>Draft</span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:1rem 1.25rem; font-size:0.8rem; color:#64748b;">
                            <?= date('M j, Y', strtotime($post['created_at'])) ?>
                        </td>
                        <td style="padding:1rem 1.25rem; text-align:right;">
                            <div class="admin-action-btn-group">
                                <a href="<?= BASE_URL ?>/article/<?= urlencode($post['slug']) ?>" target="_blank" class="admin-btn-xs" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; text-decoration:none;" title="View Live Article">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>

                                <?php if (is_super_admin() || has_permission('posts.edit')): ?>
                                <a href="<?= ADMIN_URL ?>/edit-article.php?id=<?= $post['id'] ?>&ref=posts" class="admin-btn-xs btn-edit" title="Edit Article Content">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    Edit
                                </a>
                                <?php endif; ?>

                                <?php if (is_super_admin() || has_permission('posts.delete')): ?>
                                <form action="<?= ADMIN_URL ?>/delete-article.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this article?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $post['id'] ?>">
                                    <input type="hidden" name="ref" value="posts">
                                    <button type="submit" class="admin-btn-xs btn-delete" title="Delete Article">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
