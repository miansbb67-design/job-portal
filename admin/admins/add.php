<?php
/**
 * Super Admin - Create New Administrator
 * Form to register a new admin with specific role and custom permission assignment.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization check: Super Admin or has users.create permission
if (!is_super_admin() && !has_permission('users.create')) {
    require_permission('users.create');
}

$db = getDBConnection();
$groupedPermissions = get_grouped_permissions();

$errors = [];
$name = '';
$username = '';
$email = '';
$role = 'admin';
$status = 'active';
$selectedPerms = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security session expired. Please refresh the page and submit again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $requestedRole = trim($_POST['role'] ?? 'admin');
        $status = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $selectedPerms = isset($_POST['permissions']) && is_array($_POST['permissions']) ? array_map('intval', $_POST['permissions']) : [];

        // Role restriction: Only Super Admin can assign superadmin role
        if ($requestedRole === 'superadmin' && !is_super_admin()) {
            $role = 'admin';
        } else {
            $role = in_array($requestedRole, ['superadmin', 'admin']) ? $requestedRole : 'admin';
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
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        if ($password !== $confirmPassword) {
            $errors[] = "Password confirmation does not match.";
        }

        // Check unique username
        $checkStmt = $db->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            $errors[] = "The username '{$username}' is already taken.";
        }

        // Check unique email
        $checkEmailStmt = $db->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
        $checkEmailStmt->execute([$email]);
        if ($checkEmailStmt->fetch()) {
            $errors[] = "The email '{$email}' is already in use by another administrator.";
        }

        // Proceed if no errors
        if (empty($errors)) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $insStmt = $db->prepare("
                INSERT INTO admins (name, username, email, password_hash, role, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insStmt->execute([$name, $username, $email, $passwordHash, $role, $status]);
            $newAdminId = (int)$db->lastInsertId();

            // Sync permissions if role is normal admin
            if ($role === 'admin' && !empty($selectedPerms)) {
                sync_admin_permissions($newAdminId, $selectedPerms);
            }

            log_activity('CREATE_ADMIN', 'Users / Admins', "Created new administrator: {$username} ({$role}) with ID #{$newAdminId}");

            set_flash('success', "Administrator '{$name}' (@{$username}) created successfully.");
            header('Location: ' . ADMIN_URL . '/admins/index.php');
            exit;
        }
    }
}

$adminPageTitle = "Add New Administrator";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.75rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Add New Administrator
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Configure profile credentials, select the role, and assign exact modular permissions.
        </p>
    </div>

    <a href="<?= ADMIN_URL ?>/admins/index.php" class="admin-btn admin-btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.6rem 1.25rem; font-weight:600; border-radius:8px; text-decoration:none; background:#ffffff; border:1px solid #cbd5e1; color:#334155;">
        &larr; Back to Admins
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="margin-bottom:1.5rem; padding:1rem 1.25rem; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; color:#991b1b;">
    <strong>Please correct the following errors:</strong>
    <ul style="margin:0.5rem 0 0 1.25rem; padding:0;">
        <?php foreach ($errors as $err): ?>
            <li><?= h($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form action="<?= ADMIN_URL ?>/admins/add.php" method="POST" id="addAdminForm">
    <?= csrf_field() ?>

    <div class="admin-split-2col-grid">
        
        <!-- Left: Account Details -->
        <div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h3 style="font-family:var(--admin-heading); font-size:1.1rem; font-weight:700; color:#0f172a; margin-bottom:1.25rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem;">
                Account Credentials
            </h3>

            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-weight:700; font-size:0.875rem; margin-bottom:0.4rem; color:#0f172a;">
                    Full Name <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="name" class="form-control" value="<?= h($name) ?>" placeholder="e.g. John Doe" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-weight:700; font-size:0.875rem; margin-bottom:0.4rem; color:#0f172a;">
                    Username <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="username" class="form-control" value="<?= h($username) ?>" placeholder="e.g. jdoe_admin" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
                <span style="font-size:0.75rem; color:#64748b;">Used for portal authentication.</span>
            </div>

            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-weight:700; font-size:0.875rem; margin-bottom:0.4rem; color:#0f172a;">
                    Email Address <span style="color:#ef4444;">*</span>
                </label>
                <input type="email" name="email" class="form-control" value="<?= h($email) ?>" placeholder="e.g. jdoe@company.com" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-weight:700; font-size:0.875rem; margin-bottom:0.4rem; color:#0f172a;">
                    Password <span style="color:#ef4444;">*</span>
                </label>
                <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-weight:700; font-size:0.875rem; margin-bottom:0.4rem; color:#0f172a;">
                    Confirm Password <span style="color:#ef4444;">*</span>
                </label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Re-type password" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
            </div>

            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-weight:700; font-size:0.875rem; margin-bottom:0.4rem; color:#0f172a;">
                    Administrator Role <span style="color:#ef4444;">*</span>
                </label>
                <select name="role" id="adminRoleSelect" class="form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;" onchange="handleRoleChange(this.value)">
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Staff Admin (Custom Permissions)</option>
                    <?php if (is_super_admin()): ?>
                        <option value="superadmin" <?= $role === 'superadmin' ? 'selected' : '' ?>>Super Admin (Full Unrestricted Access)</option>
                    <?php endif; ?>
                </select>
                <span style="font-size:0.75rem; color:#64748b; margin-top:4px; display:block;">
                    Super Admins bypass permission checks and always have full authority.
                </span>
            </div>

            <div style="margin-bottom:1.5rem;">
                <label style="display:block; font-weight:700; font-size:0.875rem; margin-bottom:0.4rem; color:#0f172a;">
                    Account Status
                </label>
                <select name="status" class="form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Can login)</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Blocked)</option>
                </select>
            </div>

            <button type="submit" class="admin-btn admin-btn-primary" style="width:100%; padding:0.75rem; font-weight:700; border-radius:8px; cursor:pointer;">
                Create Administrator
            </button>
        </div>

        <!-- Right: Modular Permissions Matrix -->
        <div class="admin-card" style="background:#ffffff; border:1px solid var(--admin-border); border-radius:12px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:1.25rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem;">
                <div>
                    <h3 style="font-family:var(--admin-heading); font-size:1.1rem; font-weight:700; color:#0f172a; margin:0;">
                        Assign Custom Permissions
                    </h3>
                    <span style="font-size:0.8rem; color:#64748b;">Specify exact modules and capabilities allowed for this account.</span>
                </div>

                <div id="permActionBtns" style="display:flex; gap:0.5rem;">
                    <button type="button" class="admin-btn-xs btn-perms" onclick="toggleAllPerms(true)">Select All</button>
                    <button type="button" class="admin-btn-xs btn-edit" onclick="toggleAllPerms(false)">Deselect All</button>
                </div>
            </div>

            <div id="superadminNotice" style="display:<?= $role === 'superadmin' ? 'block' : 'none' ?>; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:8px; padding:1rem 1.25rem; color:#065f46; margin-bottom:1.5rem;">
                <strong>★ Super Administrator Selected:</strong>
                <p style="margin:0.25rem 0 0; font-size:0.875rem;">
                    Super Admins automatically receive full system-wide permissions across all current and future modules. Explicit checkboxes are not required.
                </p>
            </div>

            <div id="permissionsMatrixContainer" style="opacity:<?= $role === 'superadmin' ? '0.5' : '1' ?>; pointer-events:<?= $role === 'superadmin' ? 'none' : 'auto' ?>;">
                <div class="permission-matrix-grid">
                    <?php foreach ($groupedPermissions as $module => $perms): ?>
                    <div class="permission-module-box">
                        <div class="permission-module-header">
                            <span class="permission-module-title">
                                <?= h($module) ?>
                            </span>
                            <button type="button" class="admin-btn-xs btn-edit" style="font-size:0.7rem; padding:0.15rem 0.45rem;" onclick="toggleModulePerms(this)">
                                Toggle
                            </button>
                        </div>
                        <div class="permission-module-body">
                            <?php foreach ($perms as $p): ?>
                            <label class="perm-check-row">
                                <input type="checkbox" name="permissions[]" value="<?= (int)$p['id'] ?>" class="perm-checkbox" <?= in_array($p['id'], $selectedPerms) ? 'checked' : '' ?>>
                                <div class="perm-meta">
                                    <span class="perm-label-text"><?= h($p['permission_name']) ?></span>
                                    <span class="perm-desc-text"><?= h($p['description']) ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
function handleRoleChange(role) {
    const notice = document.getElementById('superadminNotice');
    const matrix = document.getElementById('permissionsMatrixContainer');
    const btns = document.getElementById('permActionBtns');
    if (role === 'superadmin') {
        notice.style.display = 'block';
        matrix.style.opacity = '0.5';
        matrix.style.pointerEvents = 'none';
        btns.style.display = 'none';
    } else {
        notice.style.display = 'none';
        matrix.style.opacity = '1';
        matrix.style.pointerEvents = 'auto';
        btns.style.display = 'flex';
    }
}

function toggleAllPerms(check) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = check);
}

function toggleModulePerms(btn) {
    const box = btn.closest('.permission-module-box');
    const cbs = box.querySelectorAll('.perm-checkbox');
    const anyUnchecked = Array.from(cbs).some(cb => !cb.checked);
    cbs.forEach(cb => cb.checked = anyUnchecked);
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
