<?php
/**
 * Super Admin - Administrator Management Console
 * Lists all registered administrators with roles, permissions count, activity, and status.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization check: Super Admin or has users.view permission
if (!is_super_admin() && !has_permission('users.view')) {
    require_permission('users.view');
}

$adminPageTitle = "Admin Management";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDBConnection();

// Fetch all administrators with their assigned permission count
$sql = "
    SELECT 
        a.*,
        COUNT(ap.permission_id) as permissions_count
    FROM admins a
    LEFT JOIN admin_permissions ap ON a.id = ap.admin_id
    GROUP BY a.id
    ORDER BY (a.role = 'superadmin') DESC, a.created_at ASC
";
$admins = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$totalPermsCount = (int)$db->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Administrators &amp; Roles
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Manage platform administrators, grant granular permissions, and control account statuses.
        </p>
    </div>

    <?php if (is_super_admin() || has_permission('users.create')): ?>
    <a href="<?= ADMIN_URL ?>/admins/add.php" class="admin-btn admin-btn-primary" style="display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.25rem; font-weight:700; border-radius:8px; text-decoration:none;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add New Admin
    </a>
    <?php endif; ?>
</div>

<!-- Admin Table Card -->
<div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
    <div style="padding:1.25rem 1.5rem; border-bottom:1px solid var(--admin-border); display:flex; justify-content:space-between; align-items:center; background:#fafcff;">
        <div style="font-weight:700; color:#0f172a; font-size:0.95rem; display:flex; align-items:center; gap:0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            System Administrators (<?= count($admins) ?>)
        </div>
        <div style="font-size:0.8rem; color:#64748b;">
            Total System Permissions: <strong><?= $totalPermsCount ?></strong>
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="admin-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:0.875rem;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid var(--admin-border); color:#475569; font-weight:700; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.04em;">
                    <th style="padding:0.9rem 1.25rem;">ID</th>
                    <th style="padding:0.9rem 1.25rem;">Administrator</th>
                    <th style="padding:0.9rem 1.25rem;">Email</th>
                    <th style="padding:0.9rem 1.25rem;">Role</th>
                    <th style="padding:0.9rem 1.25rem;">Permissions</th>
                    <th style="padding:0.9rem 1.25rem;">Status</th>
                    <th style="padding:0.9rem 1.25rem;">Last Login</th>
                    <th style="padding:0.9rem 1.25rem; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $adm): ?>
                <?php 
                    $isSelf = ($adm['id'] == $_SESSION['admin_id']);
                    $isSuper = ($adm['role'] === 'superadmin');
                ?>
                <tr style="border-bottom:1px solid #f1f5f9; transition:background 0.15s ease;">
                    <td style="padding:1rem 1.25rem; color:#64748b; font-weight:600;">
                        #<?= (int)$adm['id'] ?>
                    </td>
                    <td style="padding:1rem 1.25rem;">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <div style="width:36px; height:36px; border-radius:50%; background:<?= $isSuper ? '#ecfdf5' : '#eff6ff' ?>; color:<?= $isSuper ? '#059669' : '#3b82f6' ?>; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem;">
                                <?= strtoupper(substr($adm['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:700; color:#0f172a; font-size:0.925rem;">
                                    <?= h($adm['name']) ?>
                                    <?php if ($isSelf): ?>
                                        <span style="font-size:0.7rem; background:#e0e7ff; color:#3730a3; padding:0.15rem 0.45rem; border-radius:4px; margin-left:4px; font-weight:700;">YOU</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.775rem; color:#64748b; font-family:monospace;">
                                    @<?= h($adm['username']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:1rem 1.25rem; color:#334155;">
                        <a href="mailto:<?= h($adm['email']) ?>" style="color:#0284c7; text-decoration:none;">
                            <?= h($adm['email']) ?>
                        </a>
                    </td>
                    <td style="padding:1rem 1.25rem;">
                        <span class="admin-role-badge <?= $isSuper ? 'badge-superadmin' : 'badge-admin' ?>">
                            <?= $isSuper ? '★ Super Admin' : 'Admin' ?>
                        </span>
                    </td>
                    <td style="padding:1rem 1.25rem;">
                        <?php if ($isSuper): ?>
                            <span style="display:inline-flex; align-items:center; gap:0.35rem; color:#059669; font-weight:700; font-size:0.8rem; background:#ecfdf5; padding:0.25rem 0.65rem; border-radius:6px; border:1px solid #a7f3d0;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                All (<?= $totalPermsCount ?> / <?= $totalPermsCount ?>)
                            </span>
                        <?php else: ?>
                            <a href="<?= ADMIN_URL ?>/admins/edit.php?id=<?= $adm['id'] ?>#permissions" style="text-decoration:none;" title="Click to edit permissions">
                                <span style="display:inline-flex; align-items:center; gap:0.35rem; color:#1e40af; font-weight:700; font-size:0.8rem; background:#eff6ff; padding:0.25rem 0.65rem; border-radius:6px; border:1px solid #bfdbfe;">
                                    <?= (int)$adm['permissions_count'] ?> of <?= $totalPermsCount ?>
                                </span>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td style="padding:1rem 1.25rem; white-space:nowrap;">
                        <?php if ($adm['status'] === 'active'): ?>
                            <span class="admin-status-pill is-published is-static">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                <span>Active</span>
                            </span>
                        <?php else: ?>
                            <span class="admin-status-pill is-draft is-static" style="background:#fef2f2 !important; color:#991b1b !important; border-color:#fecaca !important;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                <span>Inactive</span>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:1rem 1.25rem; font-size:0.8rem; color:#64748b;">
                        <?= !empty($adm['last_login']) ? date('M j, Y H:i', strtotime($adm['last_login'])) : '<span style="color:#94a3b8;">Never</span>' ?>
                    </td>
                    <td style="padding:1rem 1.25rem; text-align:right;">
                        <div class="admin-action-btn-group">
                            <?php if (is_super_admin() || has_permission('users.edit')): ?>
                                <a href="<?= ADMIN_URL ?>/admins/edit.php?id=<?= $adm['id'] ?>" class="admin-btn-xs btn-edit" title="Edit Admin & Permissions">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    Edit
                                </a>

                                <?php if (!$isSelf): ?>
                                    <form action="<?= ADMIN_URL ?>/admins/status.php" method="POST" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $adm['id'] ?>">
                                        <button type="submit" class="admin-btn-xs btn-toggle-active" title="<?= $adm['status'] === 'active' ? 'Deactivate Admin' : 'Activate Admin' ?>" onclick="return confirm('Toggle status for this administrator?');">
                                            <?= $adm['status'] === 'active' ? 'Disable' : 'Enable' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if ((is_super_admin() || has_permission('users.delete')) && !$isSelf && $adm['id'] != 1): ?>
                                <form action="<?= ADMIN_URL ?>/admins/delete.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete administrator \'<?= h($adm['username']) ?>\'? This action cannot be undone.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $adm['id'] ?>">
                                    <button type="submit" class="admin-btn-xs btn-delete" title="Delete Administrator">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
