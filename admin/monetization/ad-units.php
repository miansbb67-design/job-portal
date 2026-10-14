<?php
/**
 * Admin Panel - Monetization: Reusable Ad Units
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../../includes/monetization-functions.php';

if (!is_super_admin() && !has_permission('settings.view') && !has_permission('dashboard.view')) {
    redirect(ADMIN_URL . '/403.php');
}

$db = getDBConnection();
init_monetization_tables($db);

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!verify_csrf_token($token)) {
        $errorMsg = 'Security verification failed or session expired.';
    } elseif ($action === 'create_unit') {
        $name = trim($_POST['unit_name'] ?? '');
        $network = trim($_POST['network'] ?? 'adsense');
        $format = trim($_POST['unit_format'] ?? 'display');
        $code = trim($_POST['code_snippet'] ?? '');

        if (empty($name)) {
            $errorMsg = 'Please enter an Ad Unit name.';
        } else {
            $stmt = $db->prepare("INSERT INTO ad_units (unit_name, network, unit_format, code_snippet, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())");
            $stmt->execute([$name, $network, $format, $code]);
            $successMsg = 'Ad Unit created successfully!';
        }
    } elseif ($action === 'delete_unit') {
        $id = (int)($_POST['unit_id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM ad_units WHERE id = ?");
            $stmt->execute([$id]);
            $successMsg = 'Ad Unit deleted successfully.';
        }
    } elseif ($action === 'toggle_unit') {
        $id = (int)($_POST['unit_id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE ad_units SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $successMsg = 'Ad Unit status updated.';
        }
    }
}

$units = $db->query("SELECT * FROM ad_units ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = "Ad Units";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">Ad Units</h1>
        </div>
        <button type="button" onclick="openNewUnitModal()" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.55rem 1.15rem; border-radius:8px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:0.4rem;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            + Create New Ad Unit
        </button>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success" style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:0.75rem 1rem; border-radius:10px; margin-bottom:1.25rem; font-size:0.875rem;">
            ✓ <?= h($successMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:0.75rem 1rem; border-radius:10px; margin-bottom:1.25rem; font-size:0.875rem;">
            <?= h($errorMsg) ?>
        </div>
    <?php endif; ?>

    <!-- Table of Ad Units -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
        <?php if (empty($units)): ?>
            <div style="text-align:center; padding:3.5rem 1rem; color:#64748b;">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin-bottom:0.75rem;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                <h3 style="font-size:1.1rem; font-weight:700; color:#0f172a; margin:0 0 0.35rem 0;">No Custom Ad Units Yet</h3>
                <p style="font-size:0.85rem; margin:0 0 1rem 0;">Create reusable ad units for Google AdSense, Adsterra, or partner banners.</p>
                <button type="button" onclick="openNewUnitModal()" class="admin-btn" style="background:#059669; color:#fff; border:none; padding:0.5rem 1rem; border-radius:8px; font-weight:700;">
                    + Create First Ad Unit
                </button>
            </div>
        <?php else: ?>
            <table class="admin-table" style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                <thead style="background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                    <tr>
                        <th style="padding:0.85rem 1.25rem; font-weight:700; color:#475569;">Unit Name</th>
                        <th style="padding:0.85rem 1rem; font-weight:700; color:#475569;">Network</th>
                        <th style="padding:0.85rem 1rem; font-weight:700; color:#475569;">Format</th>
                        <th style="padding:0.85rem 1rem; font-weight:700; color:#475569;">Status</th>
                        <th style="padding:0.85rem 1.25rem; font-weight:700; color:#475569; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($units as $u): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:1rem 1.25rem; font-weight:700; color:#0f172a;">
                                <?= h($u['unit_name']) ?>
                            </td>
                            <td style="padding:1rem 1rem;">
                                <span style="background:#ecfdf5; color:#059669; padding:2px 8px; border-radius:6px; font-size:0.75rem; font-weight:700; text-transform:uppercase;">
                                    <?= h($u['network']) ?>
                                </span>
                            </td>
                            <td style="padding:1rem 1rem; color:#64748b; font-size:0.85rem;">
                                <?= h(ucwords(str_replace('_', ' ', $u['unit_format']))) ?>
                            </td>
                            <td style="padding:1rem 1rem;">
                                <form method="POST" action="" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_unit">
                                    <input type="hidden" name="unit_id" value="<?= $u['id'] ?>">
                                    <button type="submit" style="background:none; border:none; cursor:pointer;">
                                        <?php if ($u['is_active']): ?>
                                            <span style="background:#ecfdf5; color:#059669; font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:999px; border:1px solid #a7f3d0;">Active</span>
                                        <?php else: ?>
                                            <span style="background:#f1f5f9; color:#94a3b8; font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:999px; border:1px solid #e2e8f0;">Paused</span>
                                        <?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td style="padding:1rem 1.25rem; text-align:right;">
                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this ad unit?');" style="display:inline-block; margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_unit">
                                    <input type="hidden" name="unit_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="admin-btn" style="background:none; border:none; color:#ef4444; font-weight:700; cursor:pointer; font-size:0.85rem;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Create Ad Unit Modal -->
<div class="media-modal-backdrop" id="newUnitModal" style="display:none; z-index:100060;" onclick="closeNewUnitModal(event)">
    <div class="media-modal-dialog" style="max-width:540px; border-radius:14px; overflow:hidden;" onclick="event.stopPropagation()">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="create_unit">

            <div class="media-modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:1.1rem 1.35rem; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:0;">Create New Ad Unit</h3>
                <button type="button" onclick="closeNewUnitModal()" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;">&times;</button>
            </div>

            <div style="padding:1.35rem;">
                <div style="margin-bottom:1.15rem;">
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">Unit Name *</label>
                    <input type="text" name="unit_name" class="form-input" placeholder="e.g. Header Leaderboard 728x90" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>

                <div style="margin-bottom:1.15rem; display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;">
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">Network</label>
                        <select name="network" class="form-input" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
                            <option value="adsense">Google AdSense</option>
                            <option value="adsterra">Adsterra</option>
                            <option value="monetag">Monetag</option>
                            <option value="custom">Custom Partner</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">Format</label>
                        <select name="unit_format" class="form-input" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
                            <option value="display">Display Banner (Responsive)</option>
                            <option value="in_article">In-Article Responsive</option>
                            <option value="in_feed">In-Feed Native</option>
                            <option value="native">Native Banner</option>
                            <option value="social_bar">Social Bar (Adsterra)</option>
                            <option value="popunder">Popunder</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">Ad Code Snippet (HTML / JS)</label>
                    <textarea name="code_snippet" rows="4" placeholder="Paste the <ins> code or <script> provided by your ad network..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"></textarea>
                </div>
            </div>

            <div style="padding:0.85rem 1.35rem; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between;">
                <button type="button" onclick="closeNewUnitModal()" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#475569; padding:0.5rem 1rem; border-radius:8px; font-weight:600;">Cancel</button>
                <button type="submit" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.5rem 1.35rem; border-radius:8px; font-weight:700;">Create Unit</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNewUnitModal() {
    const modal = document.getElementById('newUnitModal');
    if (modal) modal.style.display = 'flex';
}
function closeNewUnitModal(e) {
    if (e && e.target && e.target.id !== 'newUnitModal') return;
    const modal = document.getElementById('newUnitModal');
    if (modal) modal.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
