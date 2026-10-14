<?php
/**
 * Admin Panel - Monetization: Monetag Integration
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
    if (!verify_csrf_token($token)) {
        $errorMsg = 'Security verification failed or session expired.';
    } else {
        save_monetization_setting('monetag_enabled', isset($_POST['monetag_enabled']) ? '1' : '0');
        save_monetization_setting('monetag_multitag_code', trim($_POST['monetag_multitag_code'] ?? ''));
        save_monetization_setting('monetag_push_enable', isset($_POST['monetag_push_enable']) ? '1' : '0');
        save_monetization_setting('monetag_push_code', trim($_POST['monetag_push_code'] ?? ''));
        save_monetization_setting('monetag_vignette_enable', isset($_POST['monetag_vignette_enable']) ? '1' : '0');
        save_monetization_setting('monetag_vignette_code', trim($_POST['monetag_vignette_code'] ?? ''));
        save_monetization_setting('monetag_popunder_code', trim($_POST['monetag_popunder_code'] ?? ''));

        log_activity('Updated Monetag Settings', 'monetization', $_SESSION['admin_id'] ?? 1);
        $successMsg = 'Monetag settings saved successfully!';
    }
}

$monetagEnabled = get_monetization_setting('monetag_enabled', '0');
$multiTagCode = get_monetization_setting('monetag_multitag_code', '');
$pushEnabled = get_monetization_setting('monetag_push_enable', '0');
$pushCode = get_monetization_setting('monetag_push_code', '');
$vignetteEnabled = get_monetization_setting('monetag_vignette_enable', '0');
$vignetteCode = get_monetization_setting('monetag_vignette_code', '');
$popunderCode = get_monetization_setting('monetag_popunder_code', '');

$adminPageTitle = "Monetag";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">Monetag</h1>
        </div>
        <a href="<?= ADMIN_URL ?>/monetization/placement.php" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#334155; padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:600; text-decoration:none;">
            View Ad Placements &rarr;
        </a>
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

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <!-- Master Switch for Monetag -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem 1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.25rem 0;">Enable Monetag Network</h3>
                <p style="font-size:0.8rem; color:#64748b; margin:0;">Toggle Monetag MultiTag and advertising scripts</p>
            </div>
            <label style="position:relative; display:inline-block; width:48px; height:26px;">
                <input type="checkbox" name="monetag_enabled" value="1" <?= $monetagEnabled === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
            </label>
        </div>

        <!-- 1. MultiTag Script (All-in-one AI auto placement) -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.2rem 0;">Monetag MultiTag Script (Recommended)</h3>
            <p style="font-size:0.8rem; color:#64748b; margin:0 0 1rem 0;">MultiTag combines Popunder, Push, and In-Page Push automatically with maximum CPM.</p>
            <textarea name="monetag_multitag_code" rows="3" placeholder="Paste your Monetag MultiTag script snippet here..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"><?= h($multiTagCode) ?></textarea>
        </div>

        <!-- 2. In-Page Push -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div>
                    <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.2rem 0;">In-Page Push (IPP)</h3>
                    <p style="font-size:0.8rem; color:#64748b; margin:0;">Native floating banner appearing at the bottom or corner of the screen.</p>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="monetag_push_enable" value="1" <?= $pushEnabled === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>
            <textarea name="monetag_push_code" rows="3" placeholder="Paste In-Page Push tag code..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"><?= h($pushCode) ?></textarea>
        </div>

        <!-- 3. Vignette Banner -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div>
                    <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.2rem 0;">Vignette Banner (Interstitial)</h3>
                    <p style="font-size:0.8rem; color:#64748b; margin:0;">Full-screen transitional banner shown between page loads with close button.</p>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="monetag_vignette_enable" value="1" <?= $vignetteEnabled === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>
            <textarea name="monetag_vignette_code" rows="3" placeholder="Paste Vignette Banner script tag..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"><?= h($vignetteCode) ?></textarea>
        </div>

        <div style="text-align:right;">
            <button type="submit" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.65rem 1.75rem; border-radius:8px; font-weight:700; cursor:pointer;">
                Save Monetag Settings
            </button>
        </div>
    </form>
</div>

<style>
input:checked + .slider { background-color: #059669 !important; }
input:checked + .slider:before { transform: translateX(22px); }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
