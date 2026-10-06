<?php
/**
 * Permissions Explorer & Role Matrix Console
 * Displays all registered platform permissions, descriptions, and assigned administrators.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization: Super Admin or has settings.view / users.view
if (!is_super_admin() && !has_permission('settings.view') && !has_permission('users.view')) {
    require_permission('settings.view');
}

$adminPageTitle = "Permissions Matrix";
require_once __DIR__ . '/../includes/admin-header.php';

$db = getDBConnection();

// Fetch all permissions grouped by module with list of assigned non-super admins
$sql = "
    SELECT 
        p.*,
        GROUP_CONCAT(a.name ORDER BY a.name SEPARATOR '|||') as admin_names,
        COUNT(ap.admin_id) as assigned_count
    FROM permissions p
    LEFT JOIN admin_permissions ap ON p.id = ap.permission_id
    LEFT JOIN admins a ON ap.admin_id = a.id
    GROUP BY p.id
    ORDER BY p.module ASC, p.permission_key ASC
";
$rawPerms = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$superAdminCount = (int)$db->query("SELECT COUNT(*) FROM admins WHERE role = 'superadmin' AND status = 'active'")->fetchColumn();
$standardAdminCount = (int)$db->query("SELECT COUNT(*) FROM admins WHERE role = 'admin' AND status = 'active'")->fetchColumn();

// Group by module
$modules = [];
foreach ($rawPerms as $p) {
    $modules[$p['module']][] = $p;
}
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            System Permissions &amp; Access Matrix
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Overview of the granular access control keys enforced across the frontend and backend.
        </p>
    </div>

    <div style="display:flex; gap:0.75rem;">
        <?php if (is_super_admin() || has_permission('users.view')): ?>
        <a href="<?= ADMIN_URL ?>/admins/index.php" class="admin-btn" style="background:#ffffff; border:1px solid #cbd5e1; color:#334155; padding:0.55rem 1rem; border-radius:8px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            Manage Administrators
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Overview Stats Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.75rem;">
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.35rem;">Total Permissions</div>
        <div style="font-size:1.75rem; font-weight:800; color:#0f172a;"><?= count($rawPerms) ?></div>
        <div style="font-size:0.75rem; color:#059669; margin-top:0.25rem;">Across <?= count($modules) ?> functional modules</div>
    </div>
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.35rem;">Super Admins</div>
        <div style="font-size:1.75rem; font-weight:800; color:#059669;"><?= $superAdminCount ?></div>
        <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Hold implicit 100% permissions</div>
    </div>
    <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:10px; padding:1.25rem;">
        <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:0.35rem;">Custom Staff Admins</div>
        <div style="font-size:1.75rem; font-weight:800; color:#0284c7;"><?= $standardAdminCount ?></div>
        <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Regulated by assigned permission keys</div>
    </div>
</div>

<!-- Architecture & Enforcement Callout -->
<div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:1.25rem; margin-bottom:1.75rem; display:flex; gap:1rem; align-items:flex-start;">
    <div style="width:36px; height:36px; border-radius:8px; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
    </div>
    <div>
        <div style="font-weight:800; color:#1e40af; font-size:0.95rem; margin-bottom:0.25rem;">Two-Layer Authorization Architecture</div>
        <p style="font-size:0.85rem; color:#1e3a8a; margin:0; line-height:1.5;">
            <strong>Layer 1 (Navigation UI):</strong> Sidebar items and UI action buttons are conditionally rendered via <code>has_permission('key')</code>.<br>
            <strong>Layer 2 (Backend Enforcement):</strong> Protected controllers invoke <code>require_permission('key')</code> at the very top. Any unauthorized direct URL manipulation terminates with a <code>403 Access Denied</code> response and logs an audit incident.
        </p>
    </div>
</div>

<!-- Permission Modules Accordion / Cards -->
<div style="display:flex; flex-direction:column; gap:1.5rem;">
    <?php foreach ($modules as $moduleName => $perms): ?>
    <div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
        <div style="padding:1rem 1.25rem; background:#f8fafc; border-bottom:1px solid var(--admin-border); display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight:800; color:#0f172a; font-size:0.95rem; text-transform:uppercase; letter-spacing:0.04em; display:flex; align-items:center; gap:0.5rem;">
                <span style="width:8px; height:8px; border-radius:50%; background:#0284c7; display:inline-block;"></span>
                <?= h($moduleName) ?> Module (<?= count($perms) ?> permissions)
            </div>
            <span style="font-size:0.75rem; color:#64748b; font-weight:600;">Module Prefix: <code><?= strtolower(h($moduleName)) ?>.*</code></span>
        </div>

        <div style="overflow-x:auto;">
            <table class="admin-table" style="width:100%; border-collapse:collapse; font-size:0.875rem;">
                <thead>
                    <tr style="border-bottom:1px solid #f1f5f9; color:#64748b; font-size:0.75rem; text-transform:uppercase;">
                        <th style="padding:0.75rem 1.25rem; width:220px;">Permission Key</th>
                        <th style="padding:0.75rem 1.25rem; width:180px;">Action Name</th>
                        <th style="padding:0.75rem 1.25rem;">Description</th>
                        <th style="padding:0.75rem 1.25rem; text-align:right;">Staff Assigned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($perms as $p): ?>
                    <tr style="border-bottom:1px solid #f8fafc;">
                        <td style="padding:0.85rem 1.25rem; font-family:monospace; font-weight:700; color:#0f172a;">
                            <span style="background:#f1f5f9; padding:0.25rem 0.5rem; border-radius:4px; border:1px solid #e2e8f0; font-size:0.8rem;">
                                <?= h($p['permission_key']) ?>
                            </span>
                        </td>
                        <td style="padding:0.85rem 1.25rem; font-weight:700; color:#334155;">
                            <?= h($p['permission_name']) ?>
                        </td>
                        <td style="padding:0.85rem 1.25rem; color:#64748b; font-size:0.85rem;">
                            <?= h($p['description']) ?>
                        </td>
                        <td style="padding:0.85rem 1.25rem; text-align:right;">
                            <?php if ($p['assigned_count'] > 0): ?>
                                <span style="background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; font-size:0.75rem; font-weight:700; padding:0.2rem 0.5rem; border-radius:999px;">
                                    <?= (int)$p['assigned_count'] ?> admin(s)
                                </span>
                            <?php else: ?>
                                <span style="color:#94a3b8; font-size:0.75rem;">Only Super Admins</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
