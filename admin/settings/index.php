<?php
/**
 * Platform Settings & Configuration Console
 * View and update core website settings with settings.* permissions.
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';

// Authorization Check: Requires settings.view
require_permission('settings.view');

$db = getDBConnection();

$errors = [];
$successMsg = '';

// Handle Settings Update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Backend Check: Requires settings.edit
    if (!is_super_admin() && !has_permission('settings.edit')) {
        require_permission('settings.edit');
    }

    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security session expired. Please refresh and try again.";
    } else {
        $postSettings = $_POST['settings'] ?? [];
        if (is_array($postSettings)) {
            $updStmt = $db->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            foreach ($postSettings as $key => $val) {
                $updStmt->execute([trim($val), $key]);
            }

            log_activity('settings_change', 'settings', "Updated platform configuration parameters", null);
            $successMsg = "Platform settings saved successfully!";
        }
    }
}

$adminPageTitle = "Platform Settings";
require_once __DIR__ . '/../includes/admin-header.php';

// Fetch all settings
$settingsRows = $db->query("SELECT * FROM settings ORDER BY setting_group ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$groupedSettings = [];
foreach ($settingsRows as $row) {
    $groupedSettings[$row['setting_group']][] = $row;
}
?>

<div class="admin-page-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div>
        <h2 style="font-family:var(--admin-heading); font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">
            Platform Settings
        </h2>
        <p style="color:#64748b; font-size:0.9rem;">
            Configure website meta properties, contact details, portal rules, and operational modes.
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:1rem 1.25rem; border-radius:10px; margin-bottom:1.5rem; font-size:0.9rem;">
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

<form action="" method="POST">
    <?= csrf_field() ?>

    <div style="display:flex; flex-direction:column; gap:1.5rem; margin-bottom:1.5rem;">
        <?php foreach ($groupedSettings as $groupName => $items): ?>
        <div class="admin-card" style="background:#fff; border:1px solid var(--admin-border); border-radius:12px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:0 0 1.25rem 0; border-bottom:1px solid #f1f5f9; padding-bottom:0.75rem; text-transform:capitalize; display:flex; align-items:center; gap:0.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <?= h($groupName) ?> Configurations
            </h3>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1.25rem;">
                <?php foreach ($items as $st): ?>
                <div>
                    <label style="display:block; font-weight:700; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                        <?= ucwords(str_replace('_', ' ', $st['setting_key'])) ?>
                    </label>

                    <?php if ($st['setting_key'] === 'maintenance_mode' || $st['setting_key'] === 'enable_job_alerts'): ?>
                        <select name="settings[<?= h($st['setting_key']) ?>]" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                            <option value="0" <?= $st['setting_value'] == '0' ? 'selected' : '' ?>>Disabled / Off</option>
                            <option value="1" <?= $st['setting_value'] == '1' ? 'selected' : '' ?>>Enabled / Active</option>
                        </select>
                    <?php elseif (str_contains($st['setting_key'], 'description') || str_contains($st['setting_key'], 'copyright')): ?>
                        <textarea name="settings[<?= h($st['setting_key']) ?>]" rows="2" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; resize:vertical;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>><?= h($st['setting_value']) ?></textarea>
                    <?php else: ?>
                        <input type="text" name="settings[<?= h($st['setting_key']) ?>]" value="<?= h($st['setting_value']) ?>" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" <?= (!is_super_admin() && !has_permission('settings.edit')) ? 'disabled' : '' ?>>
                    <?php endif; ?>

                    <?php if (!empty($st['description'])): ?>
                        <span style="font-size:0.75rem; color:#64748b; margin-top:0.25rem; display:block;">
                            <?= h($st['description']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (is_super_admin() || has_permission('settings.edit')): ?>
    <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
        <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.75rem 2rem; font-weight:700; border-radius:8px; display:inline-flex; align-items:center; gap:0.5rem; font-size:0.95rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            Save Configuration Changes
        </button>
    </div>
    <?php endif; ?>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
