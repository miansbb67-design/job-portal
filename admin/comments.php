<?php
/**
 * Admin Panel - Comments Moderation & Management
 * View, approve, reject, mark spam, and delete reader comments.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires posts.view or super admin
if (!is_super_admin() && !has_permission('posts.view')) {
    require_permission('posts.view');
}

$db = getDBConnection();

// Handle Comment Actions (Status change, Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Invalid security token. Please refresh and try again.');
        header('Location: ' . ADMIN_URL . '/comments.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if ($commentId > 0) {
        $cStmt = $db->prepare("SELECT id, user_name, comment_text, article_id FROM comments WHERE id = ? LIMIT 1");
        $cStmt->execute([$commentId]);
        $comm = $cStmt->fetch(PDO::FETCH_ASSOC);

        if ($comm) {
            if ($action === 'set_status') {
                $newStatus = trim($_POST['status'] ?? '');
                if (in_array($newStatus, ['approved', 'pending', 'spam'], true)) {
                    $db->prepare("UPDATE comments SET status = ? WHERE id = ?")->execute([$newStatus, $commentId]);
                    log_activity('moderate', 'comments', "Changed comment #{$commentId} status to {$newStatus}", $commentId);
                    set_flash('success', "Comment status updated to " . ucfirst($newStatus) . ".");
                }
            } elseif ($action === 'delete') {
                $db->prepare("DELETE FROM comments WHERE id = ?")->execute([$commentId]);
                log_activity('delete', 'comments', "Deleted comment #{$commentId} by '{$comm['user_name']}'", $commentId);
                set_flash('success', "Comment has been deleted permanently.");
            }
        }
    }

    $redirectUrl = ADMIN_URL . '/comments.php' . (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '');
    header('Location: ' . $redirectUrl);
    exit;
}

// Filters & Pagination
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$filterSearch = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterArticle = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Base counts
$countAll = (int)$db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$countApproved = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'approved'")->fetchColumn();
$countPending = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
$countSpam = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'spam'")->fetchColumn();

// Build WHERE clause
$where = [];
$params = [];

if ($filterStatus !== 'all' && in_array($filterStatus, ['approved', 'pending', 'spam'], true)) {
    $where[] = "c.status = ?";
    $params[] = $filterStatus;
}
if (!empty($filterSearch)) {
    $where[] = "(c.user_name LIKE ? OR c.user_email LIKE ? OR c.comment_text LIKE ?)";
    $term = "%{$filterSearch}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}
if ($filterArticle > 0) {
    $where[] = "c.article_id = ?";
    $params[] = $filterArticle;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Count filtered
$countStmt = $db->prepare("SELECT COUNT(*) FROM comments c {$whereSql}");
$countStmt->execute($params);
$totalFiltered = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalFiltered / $perPage);

// Fetch comments with article info
$sql = "
    SELECT c.*, a.title as article_title, a.slug as article_slug
    FROM comments c
    LEFT JOIN articles a ON c.article_id = a.id
    {$whereSql}
    ORDER BY c.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Comments Moderation";
include __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-content-inner">
    <!-- Header Page Title -->
    <div class="admin-page-header" style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
            <h1 style="font-size:1.6rem; font-weight:800; color:#0f172a; margin:0 0 0.25rem;">Comments Moderation</h1>
            <p style="font-size:0.875rem; color:#64748b; margin:0;">Review, approve, and manage community feedback across all articles.</p>
        </div>
    </div>

    <!-- Filter Status Tabs -->
    <div style="display:flex; gap:0.5rem; margin-bottom:1.5rem; flex-wrap:wrap; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
        <a href="<?= ADMIN_URL ?>/comments.php?status=all" class="admin-btn-xs" style="text-decoration:none; padding:0.45rem 0.95rem; border-radius:6px; font-weight:700; <?= $filterStatus === 'all' ? 'background:#059669; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            All (<?= $countAll ?>)
        </a>
        <a href="<?= ADMIN_URL ?>/comments.php?status=approved" class="admin-btn-xs" style="text-decoration:none; padding:0.45rem 0.95rem; border-radius:6px; font-weight:700; <?= $filterStatus === 'approved' ? 'background:#059669; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            Approved (<?= $countApproved ?>)
        </a>
        <a href="<?= ADMIN_URL ?>/comments.php?status=pending" class="admin-btn-xs" style="text-decoration:none; padding:0.45rem 0.95rem; border-radius:6px; font-weight:700; <?= $filterStatus === 'pending' ? 'background:#059669; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            Pending (<?= $countPending ?>)
        </a>
        <a href="<?= ADMIN_URL ?>/comments.php?status=spam" class="admin-btn-xs" style="text-decoration:none; padding:0.45rem 0.95rem; border-radius:6px; font-weight:700; <?= $filterStatus === 'spam' ? 'background:#059669; color:#fff;' : 'background:#f1f5f9; color:#475569;' ?>">
            Spam (<?= $countSpam ?>)
        </a>
    </div>

    <!-- Search Bar -->
    <div style="background:#ffffff; padding:1.25rem; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:1.5rem;">
        <form method="GET" action="<?= ADMIN_URL ?>/comments.php" style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            <input type="hidden" name="status" value="<?= h($filterStatus) ?>">
            <input type="text" name="search" value="<?= h($filterSearch) ?>" placeholder="Search by author, email, or content..." class="form-input" style="flex:1; min-width:240px; padding:0.55rem 0.85rem; border:1px solid #cbd5e1; border-radius:6px;">
            <button type="submit" class="admin-btn-xs" style="background:#0f172a; color:#fff; padding:0.55rem 1.1rem; border-radius:6px; font-weight:700; border:none; cursor:pointer;">
                Search
            </button>
            <?php if (!empty($filterSearch)): ?>
                <a href="<?= ADMIN_URL ?>/comments.php?status=<?= h($filterStatus) ?>" class="admin-btn-xs" style="background:#f1f5f9; color:#475569; padding:0.55rem 1rem; border-radius:6px; text-decoration:none; font-weight:600;">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Comments Table -->
    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <?php if (empty($comments)): ?>
            <div style="text-align:center; padding:3.5rem 1.5rem; color:#64748b;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:0.75rem;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <h3 style="font-size:1.1rem; color:#1e293b; margin:0 0 0.25rem;">No comments found</h3>
                <p style="font-size:0.875rem; margin:0;">There are no reader comments matching your current filter criteria.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:0.875rem; text-align:left;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-weight:700;">
                            <th style="padding:0.9rem 1.25rem; width:220px;">Author</th>
                            <th style="padding:0.9rem 1.25rem;">Comment</th>
                            <th style="padding:0.9rem 1.25rem; width:220px;">In Response To</th>
                            <th style="padding:0.9rem 1.25rem; width:110px;">Status</th>
                            <th style="padding:0.9rem 1.25rem; width:130px;">Submitted</th>
                            <th style="padding:0.9rem 1.25rem; width:160px; text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $row): ?>
                            <tr style="border-bottom:1px solid #f1f5f9; vertical-align:top;">
                                <!-- Author -->
                                <td style="padding:1rem 1.25rem;">
                                    <strong style="color:#0f172a; display:block;"><?= h($row['user_name']) ?></strong>
                                    <a href="mailto:<?= h($row['user_email']) ?>" style="color:#0284c7; font-size:0.8rem; text-decoration:none; display:block; margin-top:0.15rem;"><?= h($row['user_email']) ?></a>
                                    <span style="font-size:0.75rem; color:#94a3b8; display:block; margin-top:0.2rem;">IP: <?= h($row['user_ip']) ?></span>
                                </td>

                                <!-- Comment Body -->
                                <td style="padding:1rem 1.25rem;">
                                    <div style="color:#334155; line-height:1.55; max-height:120px; overflow-y:auto; word-break:break-word;">
                                        <?= nl2br(h($row['comment_text'])) ?>
                                    </div>
                                </td>

                                <!-- Article Link -->
                                <td style="padding:1rem 1.25rem;">
                                    <?php if (!empty($row['article_title'])): ?>
                                        <a href="<?= get_article_url($row['article_slug']) ?>" target="_blank" style="color:#0f172a; font-weight:600; text-decoration:none; font-size:0.85rem; line-height:1.4; display:block;">
                                            <?= h($row['article_title']) ?> &nearr;
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-style:italic;">(Article deleted)</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status Badge -->
                                <td style="padding:1rem 1.25rem;">
                                    <?php if ($row['status'] === 'approved'): ?>
                                        <span style="display:inline-block; padding:0.2rem 0.55rem; background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; border-radius:9999px; font-size:0.75rem; font-weight:700;">Approved</span>
                                    <?php elseif ($row['status'] === 'pending'): ?>
                                        <span style="display:inline-block; padding:0.2rem 0.55rem; background:#fffbeb; color:#d97706; border:1px solid #fde68a; border-radius:9999px; font-size:0.75rem; font-weight:700;">Pending</span>
                                    <?php else: ?>
                                        <span style="display:inline-block; padding:0.2rem 0.55rem; background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; border-radius:9999px; font-size:0.75rem; font-weight:700;">Spam</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Date -->
                                <td style="padding:1rem 1.25rem; font-size:0.8rem; color:#64748b;">
                                    <?= date('M j, Y', strtotime($row['created_at'])) ?><br>
                                    <span style="color:#94a3b8;"><?= date('g:i A', strtotime($row['created_at'])) ?></span>
                                </td>

                                <!-- Actions -->
                                <td style="padding:1rem 1.25rem; text-align:right;">
                                    <div style="display:inline-flex; gap:0.35rem; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                                        <?php if ($row['status'] !== 'approved'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="status" value="approved">
                                                <input type="hidden" name="comment_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" title="Approve Comment" style="background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; padding:0.3rem 0.55rem; border-radius:6px; cursor:pointer; font-size:0.75rem; font-weight:700;">
                                                    &check; Approve
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="status" value="pending">
                                                <input type="hidden" name="comment_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" title="Mark as Pending" style="background:#fffbeb; color:#d97706; border:1px solid #fde68a; padding:0.3rem 0.55rem; border-radius:6px; cursor:pointer; font-size:0.75rem; font-weight:700;">
                                                    Unapprove
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($row['status'] !== 'spam'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="status" value="spam">
                                                <input type="hidden" name="comment_id" value="<?= (int)$row['id'] ?>">
                                                <button type="submit" title="Mark as Spam" style="background:#f8fafc; color:#64748b; border:1px solid #cbd5e1; padding:0.3rem 0.55rem; border-radius:6px; cursor:pointer; font-size:0.75rem; font-weight:700;">
                                                    Spam
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to permanently delete this comment?');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="comment_id" value="<?= (int)$row['id'] ?>">
                                            <button type="submit" title="Delete Comment" style="background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; padding:0.3rem 0.55rem; border-radius:6px; cursor:pointer; font-size:0.75rem; font-weight:700;">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="padding:1rem 1.25rem; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:0.8rem; color:#64748b;">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $perPage, $totalFiltered) ?> of <?= $totalFiltered ?> comments
                    </span>
                    <div style="display:flex; gap:0.35rem;">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="<?= ADMIN_URL ?>/comments.php?page=<?= $i ?>&status=<?= h($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>" 
                               style="padding:0.35rem 0.75rem; border-radius:6px; font-size:0.8rem; text-decoration:none; font-weight:700; <?= $page === $i ? 'background:#059669; color:#fff;' : 'background:#ffffff; color:#475569; border:1px solid #cbd5e1;' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/admin-footer.php'; ?>
