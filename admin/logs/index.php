<?php
/**
 * System Activity Logs & Audit Trail Console
 * View chronological audit events across all administrative activities.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization Check: Requires logs.view
require_permission('logs.view');

$db = getDBConnection();

// Clear Logs Handler (Super Admin Only)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    if (!is_super_admin()) {
        require_permission('super_admin_required');
    }

    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        $db->query("TRUNCATE TABLE activity_logs");
        log_activity('clear_logs', 'logs', "Cleared entire activity audit trail", null);
        set_flash('success', "Activity audit trail truncated successfully.");
        header("Location: " . ADMIN_URL . "/logs/index.php");
        exit;
    }
}

$adminPageTitle = "System Activity Logs";
require_once __DIR__ . '/../includes/admin-header.php';

// Filters
$filterModule = trim($_GET['module'] ?? '');
$filterAdmin = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;

$whereClauses = ["1=1"];
$params = [];

if (!empty($filterModule)) {
    $whereClauses[] = "l.module = ?";
    $params[] = $filterModule;
}

if ($filterAdmin > 0) {
    $whereClauses[] = "l.admin_id = ?";
    $params[] = $filterAdmin;
}

$whereSql = implode(" AND ", $whereClauses);

// Pagination
$perPage = 25;
$page = max(1, (int)($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM activity_logs l WHERE {$whereSql}");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalLogs / $perPage));

$sql = "
    SELECT 
        l.*,
        a.name as admin_name,
        a.username as admin_username,
        a.role as admin_role
    FROM activity_logs l
    LEFT JOIN admins a ON l.admin_id = a.id
    WHERE {$whereSql}
    ORDER BY l.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$logStmt = $db->prepare($sql);
$logStmt->execute($params);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct modules for filter dropdown
$distinctModules = $db->query("SELECT DISTINCT module FROM activity_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);

// All admins for filter dropdown
$allAdmins = $db->query("SELECT id, name, username FROM admins ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Activity &amp; Audit Logs
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Comprehensive audit log tracking all logins, content edits, role changes, and security operations.
        </p>
    </div>

    <?php if (is_super_admin()): ?>
    <form action="" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to permanently clear all activity logs?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="clear_logs">
        <button type="submit" class="admin-btn" style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:0.55rem 1rem; border-radius:8px; font-weight:700; display:inline-flex; align-items:center; gap:0.4rem; cursor:pointer;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            Clear All Logs
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Filters Form -->
<div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1rem 1.25rem; margin-bottom:1.5rem;">
    <form action="" method="GET" style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <label style="font-size:0.85rem; font-weight:700; color:#475569;">Module:</label>
            <select name="module" class="admin-form-control" style="padding:0.45rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.85rem;">
                <option value="">All Modules</option>
                <?php foreach ($distinctModules as $m): ?>
                    <option value="<?= h($m) ?>" <?= $filterModule === $m ? 'selected' : '' ?>><?= ucfirst(h($m)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display:flex; align-items:center; gap:0.5rem;">
            <label style="font-size:0.85rem; font-weight:700; color:#475569;">Admin:</label>
            <select name="admin_id" class="admin-form-control" style="padding:0.45rem 0.75rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.85rem;">
                <option value="0">All Administrators</option>
                <?php foreach ($allAdmins as $adm): ?>
                    <option value="<?= $adm['id'] ?>" <?= $filterAdmin == $adm['id'] ? 'selected' : '' ?>>
                        <?= h($adm['name']) ?> (@<?= h($adm['username']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.45rem 1rem; border-radius:6px; font-size:0.85rem; font-weight:700;">
            Filter Logs
        </button>

        <?php if (!empty($filterModule) || $filterAdmin > 0): ?>
            <a href="<?= ADMIN_URL ?>/logs/index.php" style="font-size:0.85rem; color:#64748b; text-decoration:none;">Reset</a>
        <?php endif; ?>

        <div style="margin-left:auto; font-size:0.85rem; color:#64748b;">
            Showing <strong><?= count($logs) ?></strong> of <strong><?= $totalLogs ?></strong> entries
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid var(--admin-border); color:#475569; font-weight:700; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.04em;">
                    <th style="padding:0.85rem 1.25rem;">Timestamp</th>
                    <th style="padding:0.85rem 1.25rem;">Administrator</th>
                    <th style="padding:0.85rem 1.25rem;">Module</th>
                    <th style="padding:0.85rem 1.25rem;">Action</th>
                    <th style="padding:0.85rem 1.25rem;">Event Description</th>
                    <th style="padding:0.85rem 1.25rem;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" style="padding:3rem; text-align:center; color:#94a3b8;">
                        No activity records found matching your filters.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:0.9rem 1.25rem; font-size:0.8rem; color:#64748b; white-space:nowrap;">
                            <?= date('M j, Y H:i:s', strtotime($log['created_at'])) ?>
                        </td>
                        <td style="padding:0.9rem 1.25rem;">
                            <?php if (!empty($log['admin_username'])): ?>
                                <div style="font-weight:700; color:#0f172a; font-size:0.875rem;">
                                    <?= h($log['admin_name']) ?>
                                </div>
                                <div style="font-size:0.75rem; color:#64748b;">
                                    @<?= h($log['admin_username']) ?>
                                    <?php if ($log['admin_role'] === 'superadmin'): ?>
                                        <span style="color:#059669; font-weight:700;">(Super Admin)</span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-style:italic;">System / Anonymous</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:0.9rem 1.25rem;">
                            <span style="display:inline-block; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.75rem; font-weight:700; text-transform:uppercase; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0;">
                                <?= h($log['module']) ?>
                            </span>
                        </td>
                        <td style="padding:0.9rem 1.25rem; font-family:monospace; font-weight:700; color:#1e293b; font-size:0.8rem;">
                            <?= h($log['action']) ?>
                        </td>
                        <td style="padding:0.9rem 1.25rem; color:#334155;">
                            <?= h($log['description']) ?>
                        </td>
                        <td style="padding:0.9rem 1.25rem; font-family:monospace; font-size:0.75rem; color:#64748b;">
                            <?= h($log['ip_address'] ?? '127.0.0.1') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="padding:1rem 1.25rem; background:#f8fafc; border-top:1px solid var(--admin-border); display:flex; justify-content:space-between; align-items:center;">
        <div style="font-size:0.85rem; color:#64748b;">
            Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong>
        </div>
        <div style="display:flex; gap:0.35rem;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?p=<?= $i ?>&module=<?= urlencode($filterModule) ?>&admin_id=<?= $filterAdmin ?>" class="admin-btn-xs" style="padding:0.35rem 0.7rem; border-radius:4px; border:1px solid #cbd5e1; text-decoration:none; font-weight:700; <?= $i === $page ? 'background:var(--admin-primary); color:#fff; border-color:var(--admin-primary);' : 'background:#fff; color:#334155;' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
