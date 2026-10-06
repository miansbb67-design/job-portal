<?php
/**
 * Admin Panel - All Articles Management
 * Pixel-perfect match for Screenshot 1: PineCastTV Articles Directory
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires posts.view or jobs.view
if (!is_super_admin() && !has_permission('posts.view') && !has_permission('jobs.view')) {
    require_permission('posts.view');
}

$db = getDBConnection();
$filterVideo = isset($_GET['video']) ? (int)$_GET['video'] : 0;

// Action Handlers (Move to Trash, Restore, Toggle Status, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $db->prepare("SELECT id, title, is_trash, status FROM articles WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $art = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($art) {
                if ($action === 'trash') {
                    $db->prepare("UPDATE articles SET is_trash = 1 WHERE id = ?")->execute([$id]);
                    log_activity('trash', 'posts', "Moved article '{$art['title']}' to trash", $id);
                    set_flash('success', "Article moved to trash.");
                } elseif ($action === 'restore') {
                    $db->prepare("UPDATE articles SET is_trash = 0 WHERE id = ?")->execute([$id]);
                    log_activity('restore', 'posts', "Restored article '{$art['title']}' from trash", $id);
                    set_flash('success', "Article restored from trash.");
                } elseif ($action === 'delete_permanently') {
                    require_permission('posts.delete');
                    $db->prepare("DELETE FROM articles WHERE id = ?")->execute([$id]);
                    log_activity('delete', 'posts', "Permanently deleted article '{$art['title']}'", $id);
                    set_flash('success', "Article permanently deleted.");
                } elseif ($action === 'toggle_status') {
                    $newStatus = ($art['status'] === 'published') ? 'draft' : 'published';
                    $pubSql = ($newStatus === 'published') ? ", published_at = IFNULL(published_at, NOW())" : "";
                    $db->prepare("UPDATE articles SET status = ? {$pubSql} WHERE id = ?")->execute([$newStatus, $id]);
                    log_activity('publish', 'posts', "Changed status of '{$art['title']}' to {$newStatus}", $id);
                    set_flash('success', "Article status updated to " . ucfirst($newStatus) . ".");
                }
            }
        }
    }
    $redirectUrl = ADMIN_URL . '/articles.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '');
    header('Location: ' . $redirectUrl);
    exit;
}

// Filter parameters
$currentFilterStatus = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$filterSearch = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filterAuthor = isset($_GET['author']) ? (int)$_GET['author'] : 0;

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$perPage = 15;
$offset = ($currentPage - 1) * $perPage;

// Base counts for filter pills (Matches active video filter if present)
$videoSql = $filterVideo ? " AND (video_type IS NOT NULL AND video_type != 'none' AND video_type != '')" : "";
$countAll = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_trash = 0 {$videoSql}")->fetchColumn();
$countPublished = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_trash = 0 AND status = 'published' {$videoSql}")->fetchColumn();
$countDrafts = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_trash = 0 AND status = 'draft' {$videoSql}")->fetchColumn();
$countScheduled = 0;
$countUnpublished = $countDrafts;
$countTrash = (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_trash = 1 {$videoSql}")->fetchColumn();

// Build query
$where = [];
$params = [];

if ($currentFilterStatus === 'trash') {
    $where[] = "a.is_trash = 1";
} else {
    $where[] = "a.is_trash = 0";
    if ($currentFilterStatus === 'published') {
        $where[] = "a.status = 'published'";
    } elseif ($currentFilterStatus === 'draft') {
        $where[] = "a.status = 'draft'";
    } elseif ($currentFilterStatus === 'unpublished') {
        $where[] = "a.status != 'published'";
    }
}

if ($filterVideo === 1) {
    $where[] = "(a.video_type IS NOT NULL AND a.video_type != 'none' AND a.video_type != '')";
}

if (!empty($filterSearch)) {
    $where[] = "(a.title LIKE ? OR a.slug LIKE ?)";
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}

if ($filterCategory > 0) {
    $where[] = "a.category_id = ?";
    $params[] = $filterCategory;
}

if ($filterAuthor > 0) {
    $where[] = "a.author_id = ?";
    $params[] = $filterAuthor;
}

$whereSql = implode(' AND ', $where);

// Total items
$countStmt = $db->prepare("SELECT COUNT(*) FROM articles a WHERE {$whereSql}");
$countStmt->execute($params);
$totalFound = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalFound / $perPage);

// Fetch items
$sql = "
    SELECT a.*, c.name as category_name, c.slug as category_slug, 
           adm.username as author_username, adm.name as author_full_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admins adm ON a.author_id = adm.id
    WHERE {$whereSql}
    ORDER BY a.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$articlesStmt = $db->prepare($sql);
$articlesStmt->execute($params);
$articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories for filter dropdown
$categoriesList = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all authors for filter dropdown
$authorsList = $db->query("SELECT id, name, username FROM admins ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = $filterVideo ? "Vlogs & Videos" : "Articles";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="articles-management-view">
    <!-- Top Filter Bar & Search Controls (Screenshot 1) -->
    <div class="articles-controls-bar">
        <!-- Status Filter Pills on Left -->
        <div class="status-pills-row">
            <?php $videoQuery = $filterVideo ? '&video=1' : ''; ?>
            <a href="<?= ADMIN_URL ?>/articles.php?status=all<?= $videoQuery ?>" class="status-pill <?= ($currentFilterStatus === 'all' || $currentFilterStatus === '') ? 'active' : '' ?>">
                All <span class="pill-count"><?= $countAll ?></span>
            </a>
            <a href="<?= ADMIN_URL ?>/articles.php?status=published<?= $videoQuery ?>" class="status-pill <?= ($currentFilterStatus === 'published') ? 'active' : '' ?>">
                Published <span class="pill-count"><?= $countPublished ?></span>
            </a>
            <a href="<?= ADMIN_URL ?>/articles.php?status=draft<?= $videoQuery ?>" class="status-pill <?= ($currentFilterStatus === 'draft') ? 'active' : '' ?>">
                Drafts <span class="pill-count"><?= $countDrafts ?></span>
            </a>
            <a href="<?= ADMIN_URL ?>/articles.php?status=scheduled<?= $videoQuery ?>" class="status-pill <?= ($currentFilterStatus === 'scheduled') ? 'active' : '' ?>">
                Scheduled <span class="pill-count"><?= $countScheduled ?></span>
            </a>
            <a href="<?= ADMIN_URL ?>/articles.php?status=unpublished<?= $videoQuery ?>" class="status-pill <?= ($currentFilterStatus === 'unpublished') ? 'active' : '' ?>">
                Unpublished <span class="pill-count"><?= $countUnpublished ?></span>
            </a>
            <?php if ($filterVideo): ?>
                <a href="<?= ADMIN_URL ?>/articles.php" class="status-pill" style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1;">
                    ✕ Clear Video Filter (View All <?= (int)$db->query("SELECT COUNT(*) FROM articles WHERE is_trash = 0")->fetchColumn() ?> Articles)
                </a>
            <?php endif; ?>
            <?php if ($currentFilterStatus === 'trash'): ?>
            <a href="<?= ADMIN_URL ?>/articles.php?status=trash" class="status-pill active trash-pill">
                Trash <span class="pill-count"><?= $countTrash ?></span>
            </a>
            <?php endif; ?>
        </div>

        <!-- Search & Dropdown Filters on Right (Screenshot 1) -->
        <form action="<?= ADMIN_URL ?>/articles.php" method="GET" class="search-filters-form" id="articlesFilterForm">
            <?php if ($currentFilterStatus !== 'all'): ?>
                <input type="hidden" name="status" value="<?= h($currentFilterStatus) ?>">
            <?php endif; ?>
            <?php if ($filterVideo): ?>
                <input type="hidden" name="video" value="1">
            <?php endif; ?>

            <!-- Title Search Input -->
            <div class="search-input-wrap">
                <input type="text" name="search" class="filter-input-search" placeholder="Search title..." value="<?= h($filterSearch) ?>" onkeydown="if(event.key==='Enter'){this.form.submit();}">
            </div>

            <!-- Category Dropdown -->
            <div class="select-wrap">
                <select name="category" class="filter-select" onchange="this.form.submit()">
                    <option value="0">All categories</option>
                    <?php foreach ($categoriesList as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($filterCategory === (int)$cat['id']) ? 'selected' : '' ?>>
                            <?= h($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Author Dropdown -->
            <div class="select-wrap">
                <select name="author" class="filter-select" onchange="this.form.submit()">
                    <option value="0">All authors</option>
                    <?php foreach ($authorsList as $aut): ?>
                        <?php 
                            $authName = !empty($aut['name']) && $aut['name'] !== 'Administrator' ? $aut['name'] : $aut['username'];
                            // Extract first name for concise display like 'Emma', 'Faizan'
                            $nameParts = explode(' ', trim($authName));
                            $displayShort = $nameParts[0];
                        ?>
                        <option value="<?= $aut['id'] ?>" <?= ($filterAuthor === (int)$aut['id']) ? 'selected' : '' ?>>
                            <?= h($displayShort) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Articles Data Table Container (Screenshot 1) -->
    <div class="admin-table-container">
        <table class="articles-table">
            <thead>
                <tr>
                    <th class="col-title">TITLE</th>
                    <th class="col-cat">CATEGORY</th>
                    <th class="col-author">AUTHOR</th>
                    <th class="col-status">STATUS</th>
                    <th class="col-views">VIEWS</th>
                    <th class="col-plays">PLAYS</th>
                    <th class="col-date">DATE</th>
                    <th class="col-actions">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="8" class="empty-table-cell">
                            <div class="empty-state-wrap">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                                <h3><?= $filterVideo ? 'No Vlogs / Video Articles Found' : 'No articles found' ?></h3>
                                <p><?= $filterVideo ? 'None of your articles have a video attached yet. Click below to view all articles or write a new video article.' : 'Try adjusting your search query, filter criteria, or compose a new article.' ?></p>
                                <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:0.75rem;">
                                    <?php if ($filterVideo): ?>
                                        <a href="<?= ADMIN_URL ?>/articles.php" class="btn-topbar-white">View All Articles</a>
                                    <?php endif; ?>
                                    <a href="<?= ADMIN_URL ?>/add-article.php" class="btn-topbar-primary">+ New Article</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($articles as $art): ?>
                        <?php
                            // Clean author display
                            if (!empty($art['author_full_name']) && stripos($art['author_full_name'], 'Super') !== false) {
                                $authorDisplay = 'Admin';
                            } elseif (!empty($art['author_full_name'])) {
                                $authorDisplay = explode(' ', trim($art['author_full_name']))[0];
                            } else {
                                $authorDisplay = $art['author_username'] ?? 'Admin';
                            }

                            // Thumbnail image path resolution
                            $thumbSrc = get_article_thumbnail_url($art);

                            // Formatted date (Screenshot 1: Oct 3, 2026 12:02)
                            $dateObj = new DateTime($art['created_at']);
                            $dateFormatted = $dateObj->format('M j, Y H:i');

                            // Status color
                            $isPublished = ($art['status'] === 'published');
                            $viewsCount = (int)($art['views'] ?? 0);
                            $playsCount = (int)($art['plays'] ?? 0);
                        ?>
                        <tr class="article-row">
                            <!-- Title + Thumbnail + URL Slug -->
                            <td class="cell-title">
                                <div class="article-title-composite">
                                    <div class="article-thumb-wrap">
                                        <img src="<?= $thumbSrc ?>" alt="Cover" class="article-thumb-img" loading="lazy" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'120\' height=\'80\' viewBox=\'0 0 120 80\'%3E%3Crect width=\'120\' height=\'80\' fill=\'%23064e3b\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-family=\'sans-serif\' font-size=\'11\' font-weight=\'bold\' fill=\'%23a7f3d0\' text-anchor=\'middle\' dy=\'.3em\'%3EARTICLE%3C/text%3E%3C/svg%3E';">
                                    </div>
                                    <div class="article-title-text-group">
                                        <a href="<?= ADMIN_URL ?>/edit-article.php?id=<?= $art['id'] ?>" class="article-headline-link" title="<?= h($art['title']) ?>">
                                            <?= h($art['title']) ?>
                                        </a>
                                        <span class="article-slug-sub">/article/<?= h($art['slug']) ?></span>
                                    </div>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="cell-category">
                                <?php if (!empty($art['category_name'])): ?>
                                    <span class="category-plain-text"><?= h($art['category_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-dash">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Author -->
                            <td class="cell-author">
                                <span class="author-plain-text"><?= h($authorDisplay) ?></span>
                            </td>

                            <!-- Status Badge (Screenshot 1: Green Published, Gray Draft) -->
                            <td class="cell-status">
                                <?php if ($art['is_trash']): ?>
                                    <span class="badge-status-trash">Trashed</span>
                                <?php elseif ($isPublished): ?>
                                    <span class="badge-status-published">Published</span>
                                <?php else: ?>
                                    <span class="badge-status-draft">Draft</span>
                                <?php endif; ?>
                            </td>

                            <!-- Views -->
                            <td class="cell-views">
                                <span class="metric-num"><?= $viewsCount ?></span>
                            </td>

                            <!-- Plays -->
                            <td class="cell-plays">
                                <span class="metric-num"><?= $playsCount ?></span>
                            </td>

                            <!-- Date -->
                            <td class="cell-date">
                                <span class="date-plain-text"><?= $dateFormatted ?></span>
                            </td>

                            <!-- Actions: 📊 Stats, Preview, Edit, ... -->
                            <td class="cell-actions">
                                <div class="action-buttons-group">
                                    <!-- Stats Button -->
                                    <button type="button" class="btn-action-icon" title="Views: <?= $viewsCount ?> | Plays: <?= $playsCount ?>" onclick="showArticleStats(<?= $art['id'] ?>, '<?= addslashes(h($art['title'])) ?>', <?= $viewsCount ?>, <?= $playsCount ?>)">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                                    </button>

                                    <!-- Preview Button (Opens public site in new tab) -->
                                    <a href="<?= BASE_URL ?>/article/<?= h($art['slug']) ?>" target="_blank" class="btn-action-outline" title="View live article">
                                        Preview
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="<?= ADMIN_URL ?>/edit-article.php?id=<?= $art['id'] ?>" class="btn-action-outline" title="Edit content">
                                        Edit
                                    </a>

                                    <!-- More Actions Dropdown (...) -->
                                    <div class="more-dropdown-wrap">
                                        <button type="button" class="btn-action-more" onclick="toggleRowDropdown(this)">
                                            •••
                                        </button>
                                        <div class="row-dropdown-menu">
                                            <!-- Quick Status Toggle -->
                                            <form action="<?= ADMIN_URL ?>/articles.php" method="POST" style="margin:0;">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                                <button type="submit" class="dropdown-menu-item">
                                                    <?= $isPublished ? 'Convert to Draft' : 'Publish Immediately' ?>
                                                </button>
                                            </form>

                                            <!-- Copy URL -->
                                            <button type="button" class="dropdown-menu-item" onclick="copyArticleUrl('<?= BASE_URL ?>/article/<?= h($art['slug']) ?>')">
                                                Copy Article Link
                                            </button>

                                            <!-- Trash / Restore / Delete -->
                                            <?php if ($art['is_trash']): ?>
                                                <form action="<?= ADMIN_URL ?>/articles.php" method="POST" style="margin:0;">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="action" value="restore">
                                                    <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                                    <button type="submit" class="dropdown-menu-item text-success">
                                                        Restore Article
                                                    </button>
                                                </form>
                                                <form action="<?= ADMIN_URL ?>/articles.php" method="POST" style="margin:0;" onsubmit="return confirm('Permanently delete this article? This cannot be recovered.');">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="action" value="delete_permanently">
                                                    <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                                    <button type="submit" class="dropdown-menu-item text-danger">
                                                        Delete Permanently
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form action="<?= ADMIN_URL ?>/articles.php" method="POST" style="margin:0;">
                                                    <?= csrf_field(); ?>
                                                    <input type="hidden" name="action" value="trash">
                                                    <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                                    <button type="submit" class="dropdown-menu-item text-danger">
                                                        Move to Trash
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination controls (if more than 1 page) -->
    <?php if ($totalPages > 1): ?>
        <div class="articles-pagination-bar">
            <span class="pagination-info">
                Showing <?= min($offset + 1, $totalFound) ?>–<?= min($offset + $perPage, $totalFound) ?> of <?= $totalFound ?> articles
            </span>
            <div class="pagination-buttons">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&status=<?= urlencode($currentFilterStatus) ?>&search=<?= urlencode($filterSearch) ?>&category=<?= $filterCategory ?>&author=<?= $filterAuthor ?>" class="page-btn">
                        &laquo; Prev
                    </a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="?page=<?= $p ?>&status=<?= urlencode($currentFilterStatus) ?>&search=<?= urlencode($filterSearch) ?>&category=<?= $filterCategory ?>&author=<?= $filterAuthor ?>" class="page-btn <?= ($p === $currentPage) ? 'active' : '' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&status=<?= urlencode($currentFilterStatus) ?>&search=<?= urlencode($filterSearch) ?>&category=<?= $filterCategory ?>&author=<?= $filterAuthor ?>" class="page-btn">
                        Next &raquo;
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Simple Toast Notification Container for Link Copy -->
<div id="adminToast" class="admin-toast"></div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
