<?php
/**
 * Static Pages Management Console
 * Lists, views, and edits platform legal and informational pages with pages.* permissions.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Backend Authorization Check: Requires pages.view permission
require_permission('pages.view');

$adminPageTitle = "Manage Pages";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDBConnection();
$pages = $db->query("SELECT * FROM pages ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Static Pages
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Manage informational and legal site pages such as About, Privacy Policy, Terms, and Contact.
        </p>
    </div>
</div>

<div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%; border-collapse:collapse; font-size:0.875rem;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid var(--admin-border); color:#475569; font-weight:700; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.04em;">
                    <th style="padding:0.9rem 1.25rem;">Page Title</th>
                    <th style="padding:0.9rem 1.25rem;">Slug / URL</th>
                    <th style="padding:0.9rem 1.25rem;">Status</th>
                    <th style="padding:0.9rem 1.25rem;">Last Modified</th>
                    <th style="padding:0.9rem 1.25rem; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="5" style="padding:2.5rem; text-align:center; color:#94a3b8;">
                        No pages registered in the database.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($pages as $pg): ?>
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:1rem 1.25rem; font-weight:700; color:#0f172a;">
                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                <?= h($pg['title']) ?>
                            </div>
                        </td>
                        <td style="padding:1rem 1.25rem; font-family:monospace; color:#0284c7; font-size:0.8rem;">
                            <?= h(str_replace(BASE_URL, '', get_page_view_url($pg['slug']))) ?>
                        </td>
                        <td style="padding:1rem 1.25rem; white-space:nowrap;">
                            <span class="admin-status-pill is-<?= $pg['status'] ?> is-static">
                                <?php if ($pg['status'] === 'published'): ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span>Published</span>
                                <?php else: ?>
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    <span>Draft</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td style="padding:1rem 1.25rem; font-size:0.8rem; color:#64748b;">
                            <?= !empty($pg['updated_at']) ? date('M j, Y H:i', strtotime($pg['updated_at'])) : date('M j, Y H:i', strtotime($pg['created_at'])) ?>
                        </td>
                        <td style="padding:1rem 1.25rem; text-align:right;">
                            <div class="admin-action-btn-group">
                                <a href="<?= get_page_view_url($pg['slug']) ?>" target="_blank" class="admin-btn-xs" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; text-decoration:none;" title="View Live Page">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>

                                <?php if (is_super_admin() || has_permission('pages.edit')): ?>
                                <a href="<?= ADMIN_URL ?>/pages/edit.php?id=<?= $pg['id'] ?>" class="admin-btn-xs btn-edit" title="Edit Page Content">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    Edit
                                </a>
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
