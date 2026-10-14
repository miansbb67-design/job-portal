<?php
/**
 * Admin Panel - Monetization: Ad Placement
 * Matches Screenshot 1:1 with slot cards, on/off status pills, drag-reorder, and configuration modal.
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth_functions.php';
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../../includes/monetization-functions.php';

// Authorization check
if (!is_super_admin() && !has_permission('settings.view') && !has_permission('dashboard.view')) {
    redirect(ADMIN_URL . '/403.php');
}

$db = getDBConnection();
init_monetization_tables($db);

$successMsg = '';
$errorMsg = '';

// Handle Form Submissions (Update single slot or batch reorder)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $errorMsg = 'Security verification failed or session expired.';
    } elseif ($action === 'update_slot') {
        $slotKey = trim($_POST['slot_key'] ?? '');
        $isEnabled = isset($_POST['is_enabled']) ? 1 : 0;
        $adNetwork = trim($_POST['ad_network'] ?? 'adsense');
        $customCode = trim($_POST['custom_code'] ?? '');
        $deviceTarget = trim($_POST['device_target'] ?? 'all');

        $stmt = $db->prepare("
            UPDATE ad_placements 
            SET is_enabled = ?, ad_network = ?, custom_code = ?, device_target = ?, updated_at = NOW() 
            WHERE slot_key = ?
        ");
        $stmt->execute([$isEnabled, $adNetwork, $customCode, $deviceTarget, $slotKey]);

        log_activity('Updated Ad Placement Slot: ' . $slotKey, 'monetization', $_SESSION['admin_id'] ?? 1);
        $successMsg = 'Ad placement slot updated successfully!';
    } elseif ($action === 'toggle_slot') {
        $slotKey = trim($_POST['slot_key'] ?? '');
        $stmt = $db->prepare("UPDATE ad_placements SET is_enabled = NOT is_enabled, updated_at = NOW() WHERE slot_key = ?");
        $stmt->execute([$slotKey]);
        
        $newStatus = $db->query("SELECT is_enabled FROM ad_placements WHERE slot_key = " . $db->quote($slotKey))->fetchColumn();
        log_activity('Toggled Ad Placement ' . $slotKey . ' to ' . ($newStatus ? 'ON' : 'OFF'), 'monetization', $_SESSION['admin_id'] ?? 1);
        $successMsg = 'Slot status changed to ' . ($newStatus ? 'ON' : 'OFF') . '!';
    } elseif ($action === 'reorder_slots') {
        $order = $_POST['order'] ?? [];
        if (is_array($order)) {
            $upStmt = $db->prepare("UPDATE ad_placements SET sort_order = ? WHERE slot_key = ?");
            foreach ($order as $idx => $key) {
                $upStmt->execute([$idx + 1, $key]);
            }
            $successMsg = 'Ad placement order saved successfully!';
        }
    }
}

// Fetch all slots
$slots = get_all_ad_placements($db);

$adminPageTitle = "Ad Placement";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">Ad Placement</h1>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="<?= ADMIN_URL ?>/monetization/settings.php" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#334155; padding:0.5rem 0.85rem; border-radius:8px; font-size:0.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Settings
            </a>
            <a href="<?= ADMIN_URL ?>/monetization/policy-checklist.php" class="admin-btn" style="background:#ecfdf5; border:1px solid #a7f3d0; color:#059669; padding:0.5rem 0.85rem; border-radius:8px; font-size:0.85rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                Policy Checklist
            </a>
        </div>
    </div>

    <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success" style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:0.75rem 1rem; border-radius:10px; margin-bottom:1.25rem; font-size:0.875rem; display:flex; align-items:center; gap:0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            <span><?= h($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; padding:0.75rem 1rem; border-radius:10px; margin-bottom:1.25rem; font-size:0.875rem; display:flex; align-items:center; gap:0.5rem;">
            <span><?= h($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Info Banner (Matches Screenshot note) -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.1rem 1.25rem; margin-bottom:1.75rem; color:#475569; font-size:0.9rem; line-height:1.5;">
        <strong>Ad Slot Placement Manager:</strong> Drag slots to reorder priority. Each slot maintains proper content separation to strictly adhere to Google AdSense, Adsterra, and partner monetization policies.
    </div>

    <!-- Placement Slots List (Matches Screenshot Cards) -->
    <div class="ad-placement-list" id="adPlacementList" style="display:flex; flex-direction:column; gap:0.85rem;">
        <?php foreach ($slots as $slot): 
            $isOn = !empty($slot['is_enabled']);
        ?>
            <div class="ad-slot-card" data-slot-key="<?= h($slot['slot_key']) ?>" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1.1rem 1.35rem; display:flex; align-items:center; justify-content:space-between; box-shadow:0 1px 3px rgba(0,0,0,0.03); transition:all 0.15s ease;">
                <!-- Left: Drag handle, Title, and Description -->
                <div style="display:flex; align-items:center; gap:1rem; flex:1; min-width:0;">
                    <!-- Drag handle icon (⋮⋮) -->
                    <div class="drag-handle" style="color:#94a3b8; cursor:grab; padding:0.25rem; display:flex; align-items:center;" title="Drag to reorder slot">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="9" cy="6" r="1.5"></circle><circle cx="15" cy="6" r="1.5"></circle>
                            <circle cx="9" cy="12" r="1.5"></circle><circle cx="15" cy="12" r="1.5"></circle>
                            <circle cx="9" cy="18" r="1.5"></circle><circle cx="15" cy="18" r="1.5"></circle>
                        </svg>
                    </div>

                    <!-- Slot Details -->
                    <div style="min-width:0;">
                        <div style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.2rem;">
                            <span style="font-size:1.05rem; font-weight:700; color:#0f172a;">
                                <?= h($slot['slot_name']) ?>
                            </span>

                            <!-- Status Badge matching screenshot (off / on) -->
                            <?php if ($isOn): ?>
                                <span class="status-pill status-on" style="background:#ecfdf5; color:#059669; font-size:0.7rem; font-weight:800; padding:2px 8px; border-radius:999px; border:1px solid #a7f3d0; text-transform:lowercase;">
                                    on
                                </span>
                            <?php else: ?>
                                <span class="status-pill status-off" style="background:#f1f5f9; color:#94a3b8; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:999px; border:1px solid #e2e8f0; text-transform:lowercase;">
                                    off
                                </span>
                            <?php endif; ?>

                            <!-- Network Badge -->
                            <span style="font-size:0.65rem; background:#f8fafc; color:#64748b; padding:1px 6px; border-radius:6px; border:1px solid #e2e8f0; text-transform:uppercase; font-weight:700;">
                                <?= h($slot['ad_network']) ?>
                            </span>
                        </div>

                        <!-- Subtitle (Screenshot: "Header, after navigation", "Between article paragraphs", etc.) -->
                        <p style="font-size:0.825rem; color:#64748b; margin:0;">
                            <?= h($slot['slot_description']) ?>
                        </p>
                    </div>
                </div>

                <!-- Right: Action Buttons -->
                <div style="display:flex; align-items:center; gap:0.5rem; flex-shrink:0;">
                    <!-- Quick Toggle Switch Form -->
                    <form method="POST" action="" style="display:inline-block; margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="toggle_slot">
                        <input type="hidden" name="slot_key" value="<?= h($slot['slot_key']) ?>">
                        <button type="submit" class="admin-btn" style="background:<?= $isOn ? '#fef2f2' : '#ecfdf5' ?>; border:1px solid <?= $isOn ? '#fecaca' : '#a7f3d0' ?>; color:<?= $isOn ? '#dc2626' : '#059669' ?>; padding:0.4rem 0.75rem; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;" title="Click to toggle ON/OFF">
                            <?= $isOn ? 'Turn Off' : 'Turn On' ?>
                        </button>
                    </form>

                    <!-- Edit / Configure Button -->
                    <button type="button" class="admin-btn" onclick="openConfigureModal(<?= htmlspecialchars(json_encode($slot), ENT_QUOTES, 'UTF-8') ?>)" style="background:#ffffff; border:1px solid #cbd5e1; color:#1e293b; padding:0.4rem 0.85rem; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:0.35rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                        Configure
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Slot Configuration Modal -->
<div class="media-modal-backdrop" id="slotModal" style="display:none; z-index:100060;" onclick="closeConfigureModal(event)" role="dialog" aria-modal="true">
    <div class="media-modal-dialog" style="max-width:540px; border-radius:14px; overflow:hidden;" onclick="event.stopPropagation()">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            <input type="hidden" name="action" value="update_slot">
            <input type="hidden" name="slot_key" id="modalSlotKey" value="">

            <div class="media-modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:1.1rem 1.35rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 id="modalSlotName" style="font-size:1.1rem; font-weight:800; color:#0f172a; margin:0;">Configure Ad Slot</h3>
                    <p id="modalSlotDesc" style="font-size:0.75rem; color:#64748b; margin:2px 0 0 0;">Placement settings and ad script</p>
                </div>
                <button type="button" onclick="closeConfigureModal()" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;">&times;</button>
            </div>

            <div style="padding:1.35rem;">
                <!-- Status Toggle -->
                <div style="margin-bottom:1.25rem; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; padding:0.85rem 1rem; border-radius:10px; border:1px solid #e2e8f0;">
                    <div>
                        <div style="font-weight:700; color:#0f172a; font-size:0.9rem;">Slot Status</div>
                        <div style="font-size:0.75rem; color:#64748b;">Enable or disable rendering this ad slot</div>
                    </div>
                    <label style="position:relative; display:inline-block; width:48px; height:26px;">
                        <input type="checkbox" name="is_enabled" id="modalIsEnabled" value="1" style="opacity:0; width:0; height:0;">
                        <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                    </label>
                </div>

                <!-- Network Chooser -->
                <div style="margin-bottom:1.25rem;">
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                        Ad Network / Provider
                    </label>
                    <select name="ad_network" id="modalAdNetwork" class="form-input" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
                        <option value="adsense">Google AdSense (Responsive Auto Unit)</option>
                        <option value="adsterra">Adsterra Ads (Banner / Social Bar)</option>
                        <option value="monetag">Monetag (In-Page Push / Banner)</option>
                        <option value="custom">Custom HTML / Partner Script Code</option>
                    </select>
                </div>

                <!-- Device Target -->
                <div style="margin-bottom:1.25rem;">
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                        Target Devices
                    </label>
                    <select name="device_target" id="modalDeviceTarget" class="form-input" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
                        <option value="all">All Devices (Desktop, Tablet & Mobile)</option>
                        <option value="desktop">Desktop & Tablet Only</option>
                        <option value="mobile">Mobile Only</option>
                    </select>
                </div>

                <!-- Custom Code / Script override -->
                <div>
                    <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                        Ad Script Code Snippet (HTML / JS)
                    </label>
                    <textarea name="custom_code" id="modalCustomCode" rows="4" placeholder="Paste your AdSense <ins> code, Adsterra script, or custom partner banner HTML..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem; line-height:1.4;"></textarea>
                    <span style="font-size:0.75rem; color:#64748b; display:block; margin-top:0.35rem;">
                        Leave blank to automatically render the network's global settings code.
                    </span>
                </div>
            </div>

            <div style="padding:0.85rem 1.35rem; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <button type="button" onclick="closeConfigureModal()" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#475569; padding:0.5rem 1rem; border-radius:8px; font-weight:600;">
                    Cancel
                </button>
                <button type="submit" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.5rem 1.35rem; border-radius:8px; font-weight:700;">
                    Save Placement
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Switch slider styling */
input:checked + .slider {
    background-color: #059669 !important;
}
input:focus + .slider {
    box-shadow: 0 0 1px #059669;
}
input:checked + .slider:before {
    transform: translateX(22px);
}
.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .3s;
    border-radius: 50%;
}
.ad-slot-card:hover {
    border-color: #cbd5e1 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
}
</style>

<script>
function openConfigureModal(slot) {
    if (!slot) return;
    document.getElementById('modalSlotKey').value = slot.slot_key || '';
    document.getElementById('modalSlotName').textContent = slot.slot_name || 'Configure Ad Slot';
    document.getElementById('modalSlotDesc').textContent = slot.slot_description || '';
    document.getElementById('modalIsEnabled').checked = (parseInt(slot.is_enabled, 10) === 1);
    document.getElementById('modalAdNetwork').value = slot.ad_network || 'adsense';
    document.getElementById('modalDeviceTarget').value = slot.device_target || 'all';
    document.getElementById('modalCustomCode').value = slot.custom_code || '';

    const modal = document.getElementById('slotModal');
    if (modal) modal.style.display = 'flex';
}

function closeConfigureModal(e) {
    if (e && e.target && e.target.id !== 'slotModal') return;
    const modal = document.getElementById('slotModal');
    if (modal) modal.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
