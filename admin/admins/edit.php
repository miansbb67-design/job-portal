<?php
/**
 * Super Admin - Edit Administrator & Manage Permissions
 * Edit admin account details, reset password, change role, and toggle custom permissions.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization check: Super Admin or has users.edit permission
if (!is_super_admin() && !has_permission('users.edit')) {
    require_permission('users.edit');
}

$db = getDBConnection();
$adminId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($adminId <= 0) {
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

// Fetch target admin record
$stmt = $db->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$targetAdmin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$targetAdmin) {
    set_flash('error', 'Administrator not found.');
    header("Location: " . ADMIN_URL . "/admins/index.php");
    exit;
}

$isTargetSuper = ($targetAdmin['role'] === 'superadmin');
$isSelf = ($adminId == $_SESSION['admin_id']);

// Protection: Only Super Admin can edit another Super Admin account
if ($isTargetSuper && !is_super_admin()) {
    require_permission('super_admin_required'); // Will trigger 403 Access Denied
}

$groupedPermissions = get_grouped_permissions();
$currentPermIds = get_admin_permission_ids($adminId);

$errors = [];
$successMsg = '';

// Pre-fill fields
$name = $targetAdmin['name'];
$username = $targetAdmin['username'];
$email = $targetAdmin['email'];
$role = $targetAdmin['role'];
$status = $targetAdmin['status'];
$selectedPerms = $currentPermIds;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security session expired. Please refresh the page and submit again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $requestedRole = trim($_POST['role'] ?? $targetAdmin['role']);
        $requestedStatus = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $selectedPerms = isset($_POST['permissions']) && is_array($_POST['permissions']) ? array_map('intval', $_POST['permissions']) : [];

        // Protection: Root Administrator (ID 1) cannot be demoted or deactivated
        if ($adminId == 1) {
            $role = 'superadmin';
            $status = 'active';
        } else {
            // Only Super Admins can assign or revoke superadmin role
            if (is_super_admin()) {
                $role = in_array($requestedRole, ['superadmin', 'admin']) ? $requestedRole : 'admin';
            } else {
                $role = $targetAdmin['role']; // preserve current role if edited by subordinate admin
            }
            $status = $requestedStatus;
        }

        // Validation
        if (empty($name)) {
            $errors[] = "Full Name is required.";
        }
        if (empty($username) || !preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $username)) {
            $errors[] = "Username must be 3-30 characters (letters, numbers, underscores, dashes, dots).";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid email address is required.";
        }

        // Optional Password change validation
        $passwordChanged = false;
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $errors[] = "New password must be at least 6 characters long.";
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = "Password confirmation does not match.";
            } else {
                $passwordChanged = true;
            }
        }

        // Check unique username (excluding self)
        $checkStmt = $db->prepare("SELECT id FROM admins WHERE username = ? AND id != ? LIMIT 1");
        $checkStmt->execute([$username, $adminId]);
        if ($checkStmt->fetch()) {
            $errors[] = "The username '{$username}' is already taken by another administrator.";
        }

        // Check unique email (excluding self)
        $checkEmailStmt = $db->prepare("SELECT id FROM admins WHERE email = ? AND id != ? LIMIT 1");
        $checkEmailStmt->execute([$email, $adminId]);
        if ($checkEmailStmt->fetch()) {
            $errors[] = "The email '{$email}' is already in use by another administrator.";
        }

        if (empty($errors)) {
            if ($passwordChanged) {
                $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updStmt = $db->prepare("
                    UPDATE admins 
                    SET name = ?, username = ?, email = ?, password_hash = ?, role = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $updStmt->execute([$name, $username, $email, $passwordHash, $role, $status, $adminId]);
                log_activity('reset_password', 'users', "Reset password for administrator {$username} (ID: {$adminId})", $adminId);
            } else {
                $updStmt = $db->prepare("
                    UPDATE admins 
                    SET name = ?, username = ?, email = ?, role = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $updStmt->execute([$name, $username, $email, $role, $status, $adminId]);
            }

            // Sync permissions for normal admin role
            if ($role === 'admin') {
                sync_admin_permissions($adminId, $selectedPerms);
                log_activity('permission_change', 'permissions', "Updated permissions for administrator {$username} (Count: " . count($selectedPerms) . ")", $adminId);
            } else {
                // Super Admin has all implicit permissions; clear pivot
                $delPerms = $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
                $delPerms->execute([$adminId]);
            }

            log_activity('edit_admin', 'users', "Modified details for administrator {$username} (Role: {$role}, Status: {$status})", $adminId);

            // If updating currently logged in user, refresh session info
            if ($isSelf) {
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_role'] = $role;
                $_SESSION['admin_name'] = $name;
                $_SESSION['admin_permissions'] = get_admin_permission_keys($adminId);
            }

            $successMsg = "Administrator '{$username}' updated successfully!";
            
            // Refresh target data
            $stmt = $db->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $targetAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
            $currentPermIds = get_admin_permission_ids($adminId);
            $selectedPerms = $currentPermIds;
        }
    }
}

$adminPageTitle = "Edit Administrator";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.25rem;">
            <a href="<?= ADMIN_URL ?>/admins/index.php" style="color:#64748b; text-decoration:none; font-size:0.875rem; font-weight:600;">&larr; Back to Admins</a>
        </div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin:0;">
            Edit Administrator: <?= h($targetAdmin['name']) ?>
        </h2>
    </div>
    <div style="display:flex; gap:0.5rem; align-items:center;">
        <span class="admin-role-badge <?= $targetAdmin['role'] === 'superadmin' ? 'badge-superadmin' : 'badge-admin' ?>">
            <?= $targetAdmin['role'] === 'superadmin' ? '★ Super Admin' : 'Admin' ?>
        </span>
        <span class="<?= $targetAdmin['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
            <?= ucfirst($targetAdmin['status']) ?>
        </span>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:1rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-size:0.9rem;">
    <div style="font-weight:700; margin-bottom:0.35rem; display:flex; align-items:center; gap:0.5rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        Please correct the following errors:
    </div>
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

<form action="" method="POST" id="editAdminForm">
    <?= csrf_field() ?>

    <div class="admin-split-2col-grid" style="margin-bottom:1.5rem;">
        <!-- Account Details Card -->
        <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                Account Information
            </h3>

            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    Full Name <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="name" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" value="<?= h($name) ?>" required>
            </div>

            <div class="admin-form-row-2col" style="margin-bottom:1rem;">
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Username <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" name="username" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" value="<?= h($username) ?>" required>
                </div>
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Email Address <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="email" name="email" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" value="<?= h($email) ?>" required>
                </div>
            </div>

            <div class="admin-form-row-2col">
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Administrative Role
                    </label>
                    <?php if ($adminId == 1): ?>
                        <input type="hidden" name="role" value="superadmin">
                        <input type="text" class="admin-form-control" value="★ Super Admin (Root - Locked)" disabled style="width:100%; padding:0.65rem 0.85rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem; font-weight:700; color:#059669;">
                    <?php elseif (is_super_admin()): ?>
                        <select name="role" id="roleSelect" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" onchange="handleRoleChange(this.value)">
                            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin (Custom Permissions)</option>
                            <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>★ Super Admin (Full Control)</option>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="role" value="<?= h($role) ?>">
                        <input type="text" class="admin-form-control" value="<?= $role === 'superadmin' ? 'Super Admin' : 'Admin' ?>" disabled style="width:100%; padding:0.65rem 0.85rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem;">
                    <?php endif; ?>
                </div>
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        Account Status
                    </label>
                    <?php if ($adminId == 1): ?>
                        <input type="hidden" name="status" value="active">
                        <input type="text" class="admin-form-control" value="Active (Root - Always Active)" disabled style="width:100%; padding:0.65rem 0.85rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem; font-weight:700; color:#059669;">
                    <?php else: ?>
                        <select name="status" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Can Login)</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Access Disabled)</option>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top:1.25rem; padding-top:1rem; border-top:1px solid #f1f5f9; font-size:0.8rem; color:#64748b; display:flex; justify-content:space-between;">
                <div>Created: <strong><?= date('M j, Y H:i', strtotime($targetAdmin['created_at'])) ?></strong></div>
                <div>Last Login: <strong><?= !empty($targetAdmin['last_login']) ? date('M j, Y H:i', strtotime($targetAdmin['last_login'])) : 'Never' ?></strong></div>
            </div>
        </div>

        <!-- Password Reset Card -->
        <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin-bottom:1.25rem; display:flex; align-items:center; gap:0.5rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Password Reset
            </h3>
            
            <p style="font-size:0.85rem; color:#64748b; margin-bottom:1.25rem;">
                Leave password fields blank to retain the administrator's current password. Enter a new password only if you wish to reset it.
            </p>

            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    New Password
                </label>
                <input type="password" name="password" class="admin-form-control" placeholder="Leave empty to keep current password" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" autocomplete="new-password">
                <span style="font-size:0.75rem; color:#94a3b8;">Minimum 6 characters, if changing.</span>
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    Confirm New Password
                </label>
                <input type="password" name="confirm_password" class="admin-form-control" placeholder="Re-type new password" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" autocomplete="new-password">
            </div>

            <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:0.85rem; font-size:0.8rem; color:#0369a1;">
                <strong>Security Notice:</strong> Passwords are always encrypted using Bcrypt (<code>password_hash</code>). Neither plain text nor decrypted passwords are ever visible to anyone.
            </div>
        </div>
    </div>

    <!-- Permission Assignment Matrix -->
    <div id="permissionsSection" class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; border-bottom:1px solid #f1f5f9; padding-bottom:1rem; margin-bottom:1.25rem;">
            <div>
                <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:0 0 0.25rem 0; display:flex; align-items:center; gap:0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    Custom Permission Assignment
                </h3>
                <p style="font-size:0.85rem; color:#64748b; margin:0;">
                    Select the specific backend and frontend capabilities granted to this administrator.
                </p>
            </div>

            <div id="permActionButtons" style="display:flex; gap:0.5rem;">
                <button type="button" class="admin-btn admin-btn-sm" onclick="selectAllPerms(true)" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                    Select All
                </button>
                <button type="button" class="admin-btn admin-btn-sm" onclick="selectAllPerms(false)" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; padding:0.4rem 0.8rem; border-radius:6px; font-size:0.8rem; font-weight:700; cursor:pointer;">
                    Deselect All
                </button>
            </div>
        </div>

        <div id="superadminBanner" style="display:<?= $role === 'superadmin' ? 'block' : 'none' ?>; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:1.25rem; text-align:center; color:#065f46; margin-bottom:1rem;">
            <div style="font-weight:800; font-size:1.05rem; margin-bottom:0.25rem;">★ Super Admin Role Assigned</div>
            <div style="font-size:0.875rem;">
                Super Administrators automatically possess full, unrestricted access to every module, action, and setting across the entire platform. Custom permission toggles are bypassed.
            </div>
        </div>

        <div id="permMatrixContainer" style="display:<?= $role === 'superadmin' ? 'none' : 'block' ?>;">
            <div class="permission-matrix-grid">
                <?php foreach ($groupedPermissions as $module => $perms): ?>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1rem 1.25rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.5rem;">
                        <h4 style="font-size:0.9rem; font-weight:800; color:#1e293b; margin:0; text-transform:uppercase; letter-spacing:0.04em;">
                            <?= h($module) ?>
                        </h4>
                        <button type="button" onclick="toggleGroup('grp_<?= h(strtolower($module)) ?>')" style="background:none; border:none; color:#0284c7; font-size:0.75rem; font-weight:700; cursor:pointer; padding:0;">
                            Toggle All
                        </button>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:0.6rem;">
                        <?php foreach ($perms as $p): ?>
                        <label style="display:flex; align-items:flex-start; gap:0.6rem; cursor:pointer; font-size:0.85rem; color:#334155;">
                            <input 
                                type="checkbox" 
                                name="permissions[]" 
                                value="<?= (int)$p['id'] ?>" 
                                class="perm-checkbox grp_<?= h(strtolower($module)) ?>"
                                <?= in_array((int)$p['id'], $selectedPerms) ? 'checked' : '' ?>
                                style="margin-top:0.2rem; cursor:pointer; width:16px; height:16px; accent-color:#0284c7;"
                            >
                            <div>
                                <div style="font-weight:700; color:#0f172a; font-size:0.85rem;">
                                    <?= h($p['permission_name']) ?>
                                </div>
                                <div style="font-size:0.75rem; color:#64748b;">
                                    <code><?= h($p['permission_key']) ?></code> &bull; <?= h($p['description']) ?>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display:flex; justify-content:flex-end; gap:0.75rem; align-items:center;">
        <a href="<?= ADMIN_URL ?>/admins/index.php" class="admin-btn" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:0.65rem 1.25rem; font-weight:700; border-radius:8px; text-decoration:none;">
            Cancel
        </a>
        <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.65rem 1.5rem; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Save Changes
        </button>
    </div>
</form>

<script>
function handleRoleChange(role) {
    const banner = document.getElementById('superadminBanner');
    const container = document.getElementById('permMatrixContainer');
    const btnGroup = document.getElementById('permActionButtons');
    if (role === 'superadmin') {
        banner.style.display = 'block';
        container.style.display = 'none';
        btnGroup.style.display = 'none';
    } else {
        banner.style.display = 'none';
        container.style.display = 'block';
        btnGroup.style.display = 'flex';
    }
}

function selectAllPerms(checked) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = checked;
    });
}

function toggleGroup(groupClass) {
    const cbs = document.querySelectorAll('.' + groupClass);
    const someUnchecked = Array.from(cbs).some(cb => !cb.checked);
    cbs.forEach(cb => cb.checked = someUnchecked);
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
