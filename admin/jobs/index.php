<?php
/**
 * Jobs Management Console
 * Lists, filters, and manages all job postings with granular jobs.* permissions.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Backend Authorization Check: Requires jobs.view permission
$db = getDBConnection();

// Status quick toggle handler (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($token)) {
        require_permission('jobs.publish');
        $id = (int)($_POST['id'] ?? 0);
        $checkStmt = $db->prepare("SELECT id, title, status FROM articles WHERE id = ? AND is_job_posting = 1 LIMIT 1");
        $checkStmt->execute([$id]);
        $jobRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($jobRow) {
            $newStatus = ($jobRow['status'] === 'published') ? 'draft' : 'published';
            $publishedAtSql = ($newStatus === 'published') ? ", published_at = IFNULL(published_at, NOW())" : "";

            $toggleStmt = $db->prepare("UPDATE articles SET status = ? {$publishedAtSql} WHERE id = ?");
            $toggleStmt->execute([$newStatus, $id]);

            $actionName = ($newStatus === 'published') ? 'publish' : 'unpublish';
            log_activity($actionName, 'jobs', "Changed status of job '{$jobRow['title']}' to {$newStatus}", $id);
            set_flash('success', "Job status updated to " . ucfirst($newStatus) . ".");
        }
    }
    header('Location: ' . ADMIN_URL . '/jobs/index.php');
    exit;
}

$adminPageTitle = "Manage Jobs";
require_once __DIR__ . '/../includes/admin-header.php';

// Fetch all job postings
$sql = "
    SELECT 
        a.id, 
        a.title, 
        a.slug, 
        a.status, 
        a.created_at, 
        a.job_company as company_name, 
        a.job_location, 
        a.job_type,
        c.name as category_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.is_job_posting = 1
    ORDER BY a.created_at DESC
";
$jobs = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$totalJobs = count($jobs);
$publishedJobs = count(array_filter($jobs, fn($j) => $j['status'] === 'published'));
$draftJobs = count(array_filter($jobs, fn($j) => $j['status'] === 'draft'));
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Job Postings
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Manage career opportunities, job listings, and hiring posts.
        </p>
    </div>

    <?php if (is_super_admin() || has_permission('jobs.create')): ?>
    <a href="<?= ADMIN_URL ?>/add-article.php?type=job" class="admin-btn admin-btn-primary" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.25rem; font-weight:700; border-radius:8px; text-decoration:none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Post New Job
    </a>
    <?php endif; ?>
</div>

<!-- Metrics Row -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Total Jobs</div>
        <div style="font-size:1.75rem; font-weight:800; color:#0f172a;"><?= $totalJobs ?></div>
    </div>
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Live / Published</div>
        <div style="font-size:1.75rem; font-weight:800; color:#059669;"><?= $publishedJobs ?></div>
    </div>
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Drafts / In Review</div>
        <div style="font-size:1.75rem; font-weight:800; color:#d97706;"><?= $draftJobs ?></div>
    </div>
</div>

<!-- Jobs Table -->
<div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid var(--admin-border); color:#475569; font-weight:700; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.04em;">
                    <th class="col-job-id" style="padding:0.9rem 1.25rem;">ID</th>
                    <th class="col-job-title" style="padding:0.9rem 1.25rem;">Job Title &amp; Company</th>
                    <th class="col-job-cat" style="padding:0.9rem 1.25rem;">Category</th>
                    <th class="col-job-loc" style="padding:0.9rem 1.25rem;">Location &amp; Type</th>
                    <th class="col-job-status" style="padding:0.9rem 1.25rem; white-space:nowrap;">Status</th>
                    <th class="col-job-date" style="padding:0.9rem 1.25rem;">Date Posted</th>
                    <th class="col-job-actions" style="padding:0.9rem 1.25rem; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($jobs)): ?>
                <tr>
                    <td colspan="7" style="padding:2.5rem; text-align:center; color:#94a3b8;">
                        No job postings found. Click "Post New Job" above to create one.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td class="cell-job-id" style="padding:1rem 1.25rem; color:#64748b; font-weight:600;">
                            #<?= (int)$job['id'] ?>
                        </td>
                        <td class="cell-job-title" style="padding:1rem 1.25rem;">
                            <div>
                                <a href="<?= BASE_URL ?>/article/<?= urlencode($job['slug']) ?>" target="_blank" class="admin-table-title-link" title="Open live job in new tab">
                                    <?= h($job['title']) ?>
                                </a>
                            </div>
                            <?php if (!empty($job['company_name'])): ?>
                                <div style="font-size:0.8rem; color:#0284c7; font-weight:600; margin-top:0.2rem;">
                                    <?= h($job['company_name']) ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-top:0.35rem;">
                                <a href="<?= BASE_URL ?>/article/<?= urlencode($job['slug']) ?>" target="_blank" class="admin-table-slug-link" title="View live job">
                                    <span>/article/<?= h($job['slug']) ?></span>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                                </a>
                            </div>
                            <div class="job-mobile-meta">
                                <span class="job-badge-meta"><?= h($job['category_name'] ?? 'General') ?></span>
                                <span>&bull;</span>
                                <span><?= h($job['job_location'] ?? 'Remote') ?> (<?= h($job['job_type'] ?? 'Full-time') ?>)</span>
                                <span>&bull;</span>
                                <span><?= date('M j, Y', strtotime($job['created_at'])) ?></span>
                            </div>
                        </td>
                        <td class="cell-job-cat" style="padding:1rem 1.25rem; color:#475569;">
                            <span style="background:#f1f5f9; padding:0.2rem 0.55rem; border-radius:4px; font-size:0.8rem; font-weight:600;">
                                <?= h($job['category_name'] ?? 'Uncategorized') ?>
                            </span>
                        </td>
                        <td class="cell-job-loc" style="padding:1rem 1.25rem; color:#64748b; font-size:0.825rem;">
                            <?= h($job['job_location'] ?? 'Remote') ?> &bull; <?= h($job['job_type'] ?? 'Full-time') ?>
                        </td>
                        <td class="cell-job-status" style="padding:1rem 1.25rem; white-space:nowrap;">
                            <?php if (is_super_admin() || has_permission('jobs.publish')): ?>
                            <form action="<?= ADMIN_URL ?>/jobs/index.php" method="POST" style="display:inline; margin:0;">
                                <?= csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $job['id'] ?>">
                                <button type="submit" class="admin-status-pill is-<?= $job['status'] ?>" title="Click to toggle status (Change to <?= ($job['status'] === 'published') ? 'Draft' : 'Published' ?>)">
                                    <?php if ($job['status'] === 'published'): ?>
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
                                <span class="admin-status-pill is-<?= $job['status'] ?> is-static">
                                    <?php if ($job['status'] === 'published'): ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                        <span>Published</span>
                                    <?php else: ?>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                        <span>Draft</span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="cell-job-date" style="padding:1rem 1.25rem; font-size:0.8rem; color:#64748b;">
                            <?= date('M j, Y', strtotime($job['created_at'])) ?>
                        </td>
                        <td class="cell-job-actions" style="padding:1rem 1.25rem; text-align:right;">
                            <div class="admin-action-btn-group">
                                <a href="<?= BASE_URL ?>/article/<?= urlencode($job['slug']) ?>" target="_blank" class="admin-btn-xs" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; text-decoration:none;" title="View Live Job">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>

                                <?php if (is_super_admin() || has_permission('jobs.edit')): ?>
                                <a href="<?= ADMIN_URL ?>/edit-article.php?id=<?= $job['id'] ?>&ref=jobs" class="admin-btn-xs btn-edit" title="Edit Job Details">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    Edit
                                </a>
                                <?php endif; ?>

                                <?php if (is_super_admin() || has_permission('jobs.delete')): ?>
                                <form action="<?= ADMIN_URL ?>/delete-article.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this job posting?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $job['id'] ?>">
                                    <input type="hidden" name="ref" value="jobs">
                                    <button type="submit" class="admin-btn-xs btn-delete" title="Delete Job">
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
