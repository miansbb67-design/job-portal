<?php
/**
 * Super Admin & Role / Permission Authorization System
 * CareerNexaa Jobs & Career Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Get client IP address reliably (accounting for proxies/localhost)
 *
 * @return string
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    return trim($ip);
}

/**
 * Check if the current (or specified) admin is a Super Admin
 *
 * @param int|null $adminId
 * @return bool
 */
function is_super_admin($adminId = null) {
    if ($adminId === null) {
        if (!empty($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'superadmin') {
            return true;
        }
        $adminId = $_SESSION['admin_id'] ?? 0;
    }

    if (!$adminId) {
        return false;
    }

    $db = getDBConnection();
    $stmt = $db->prepare("SELECT role, status FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$adminId]);
    $admin = $stmt->fetch();

    if ($admin && $admin['status'] === 'active' && $admin['role'] === 'superadmin') {
        if ($adminId == ($_SESSION['admin_id'] ?? 0)) {
            $_SESSION['admin_role'] = 'superadmin';
        }
        return true;
    }

    return false;
}

/**
 * Fetch all permission keys assigned to an admin
 *
 * @param int $adminId
 * @param bool $forceRefresh
 * @return array Array of permission_key strings
 */
function get_admin_permission_keys($adminId, $forceRefresh = false) {
    if (!$adminId) {
        return [];
    }

    // Check if cached in session for current user
    if (!$forceRefresh && !empty($_SESSION['admin_id']) && $_SESSION['admin_id'] == $adminId && isset($_SESSION['cached_permissions'])) {
        return $_SESSION['cached_permissions'];
    }

    $db = getDBConnection();

    // Check if admin is superadmin
    $stmt = $db->prepare("SELECT role, status FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$adminId]);
    $admin = $stmt->fetch();

    if (!$admin || $admin['status'] !== 'active') {
        return [];
    }

    if ($admin['role'] === 'superadmin') {
        // Superadmin gets all permission keys in the system
        $allKeysStmt = $db->query("SELECT permission_key FROM permissions");
        $allKeys = $allKeysStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($_SESSION['admin_id']) && $_SESSION['admin_id'] == $adminId) {
            $_SESSION['cached_permissions'] = $allKeys;
        }
        return $allKeys;
    }

    // Normal admin: fetch explicitly assigned permissions
    $sql = "
        SELECT p.permission_key 
        FROM admin_permissions ap
        JOIN permissions p ON ap.permission_id = p.id
        WHERE ap.admin_id = ?
    ";
    $permStmt = $db->prepare($sql);
    $permStmt->execute([(int)$adminId]);
    $keys = $permStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($_SESSION['admin_id']) && $_SESSION['admin_id'] == $adminId) {
        $_SESSION['cached_permissions'] = $keys;
    }

    return $keys;
}

/**
 * Fetch all permission IDs assigned to an admin
 *
 * @param int $adminId
 * @return array Array of integer permission IDs
 */
function get_admin_permission_ids($adminId) {
    if (!$adminId) {
        return [];
    }

    $db = getDBConnection();

    // Check admin role
    $stmt = $db->prepare("SELECT role, status FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$adminId]);
    $admin = $stmt->fetch();

    if (!$admin || $admin['status'] !== 'active') {
        return [];
    }

    if ($admin['role'] === 'superadmin') {
        // Superadmin has all permission IDs
        $allIdsStmt = $db->query("SELECT id FROM permissions");
        return array_map('intval', $allIdsStmt->fetchAll(PDO::FETCH_COLUMN));
    }

    // Normal admin: fetch explicitly assigned permission IDs
    $permStmt = $db->prepare("SELECT permission_id FROM admin_permissions WHERE admin_id = ?");
    $permStmt->execute([(int)$adminId]);
    return array_map('intval', $permStmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Check if the logged-in admin has a specific permission
 * Super Admin always has full access (returns true)
 *
 * @param string $permissionKey Example: 'jobs.delete', 'posts.create'
 * @param int|null $adminId
 * @return bool
 */
function has_permission($permissionKey, $adminId = null) {
    if ($adminId === null) {
        $adminId = $_SESSION['admin_id'] ?? 0;
    }

    if (!$adminId) {
        return false;
    }

    // Super Admin check - always returns true
    if (is_super_admin($adminId)) {
        return true;
    }

    $permissions = get_admin_permission_keys($adminId);
    return in_array($permissionKey, $permissions, true);
}

/**
 * Enforce permission authorization guard
 * Halts execution and displays Access Denied page if unauthorized
 *
 * @param string $permissionKey
 */
function require_permission($permissionKey) {
    if (!is_admin_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        if (!headers_sent()) {
            header('Location: ' . ADMIN_URL . '/login.php');
            exit;
        }
    }

    if (!has_permission($permissionKey)) {
        log_activity('UNAUTHORIZED_ACCESS', 'Security', "Attempted unauthorized access to action [{$permissionKey}]");
        http_response_code(403);
        if (!headers_sent()) {
            header('Location: ' . ADMIN_URL . '/403.php?required=' . urlencode($permissionKey));
            exit;
        } else {
            echo '<script>window.location.href = "' . ADMIN_URL . '/403.php?required=' . urlencode($permissionKey) . '";</script>';
            echo '<div style="margin:2rem auto; max-width:600px; padding:2rem; background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; border-radius:10px; font-family:sans-serif; text-align:center;">';
            echo '<h2 style="margin:0 0 0.5rem 0;">403 Access Denied</h2>';
            echo '<p style="margin:0;">You lack the <code>' . htmlspecialchars($permissionKey) . '</code> permission required to access this resource.</p>';
            echo '<p style="margin-top:1rem;"><a href="' . ADMIN_URL . '/index.php" style="color:#1d4ed8; font-weight:700;">&larr; Return to Dashboard</a></p>';
            echo '</div>';
            exit;
        }
    }
}

/**
 * Enforce Super Admin only access
 *
 * @param string $actionLabel
 */
function require_super_admin($actionLabel = 'Super Admin Restricted Area') {
    if (!is_admin_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        if (!headers_sent()) {
            header('Location: ' . ADMIN_URL . '/login.php');
            exit;
        }
    }

    if (!is_super_admin()) {
        log_activity('UNAUTHORIZED_SUPERADMIN_ACCESS', 'Security', "Attempted unauthorized access to [{$actionLabel}]");
        http_response_code(403);
        if (!headers_sent()) {
            header('Location: ' . ADMIN_URL . '/403.php?required=superadmin');
            exit;
        } else {
            echo '<script>window.location.href = "' . ADMIN_URL . '/403.php?required=superadmin";</script>';
            echo '<div style="margin:2rem auto; max-width:600px; padding:2rem; background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; border-radius:10px; font-family:sans-serif; text-align:center;">';
            echo '<h2 style="margin:0 0 0.5rem 0;">403 Access Denied</h2>';
            echo '<p style="margin:0;">Super Administrator role is required to access ' . htmlspecialchars($actionLabel) . '.</p>';
            echo '<p style="margin-top:1rem;"><a href="' . ADMIN_URL . '/index.php" style="color:#1d4ed8; font-weight:700;">&larr; Return to Dashboard</a></p>';
            echo '</div>';
            exit;
        }
    }
}

/**
 * Record an audit activity log entry
 *
 * @param string $action Example: 'CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'
 * @param string $module Example: 'Jobs', 'Blog Posts', 'Admins', 'Settings'
 * @param string $description Detailed human-readable action description
 * @param int|null $adminId
 * @return bool
 */
function log_activity($action, $module, $description, $adminId = null) {
    try {
        $db = getDBConnection();
        $adminId = $adminId ?? ($_SESSION['admin_id'] ?? null);
        $adminName = $_SESSION['admin_name'] ?? ($_SESSION['admin_username'] ?? 'System / Guest');
        $ip = get_client_ip();

        $stmt = $db->prepare("
            INSERT INTO activity_logs (admin_id, admin_name, action, module, description, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([
            $adminId ? (int)$adminId : null,
            $adminName,
            substr($action, 0, 60),
            substr($module, 0, 50),
            $description,
            $ip
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Assign an array of permission IDs or keys to an admin
 *
 * @param int $adminId
 * @param array $permissionIds Array of integer permission IDs
 * @return bool
 */
function sync_admin_permissions($adminId, array $permissionIds) {
    $db = getDBConnection();
    try {
        $db->beginTransaction();

        // Remove existing
        $delStmt = $db->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
        $delStmt->execute([(int)$adminId]);

        // Insert new
        if (!empty($permissionIds)) {
            $insertStmt = $db->prepare("INSERT INTO admin_permissions (admin_id, permission_id) VALUES (?, ?)");
            foreach ($permissionIds as $permId) {
                $pId = (int)$permId;
                if ($pId > 0) {
                    $insertStmt->execute([(int)$adminId, $pId]);
                }
            }
        }

        $db->commit();

        // Invalidate session cache if updating currently logged in admin
        if (!empty($_SESSION['admin_id']) && $_SESSION['admin_id'] == $adminId) {
            unset($_SESSION['cached_permissions']);
        }

        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error syncing admin permissions: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all available permissions grouped by module
 *
 * @return array [module => [permission, ...]]
 */
function get_grouped_permissions() {
    $db = getDBConnection();
    $stmt = $db->query("SELECT * FROM permissions ORDER BY module ASC, id ASC");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($all as $p) {
        $mod = $p['module'];
        if (!isset($grouped[$mod])) {
            $grouped[$mod] = [];
        }
        $grouped[$mod][] = $p;
    }
    return $grouped;
}
