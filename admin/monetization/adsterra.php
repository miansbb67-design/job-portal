<?php
/**
 * Admin Panel - Monetization: Adsterra Ads Integration
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
        save_monetization_setting('adsterra_enabled', isset($_POST['adsterra_enabled']) ? '1' : '0');
        save_monetization_setting('adsterra_social_bar_enable', isset($_POST['adsterra_social_bar_enable']) ? '1' : '0');
        save_monetization_setting('adsterra_social_bar_code', trim($_POST['adsterra_social_bar_code'] ?? ''));
        save_monetization_setting('adsterra_popunder_enable', isset($_POST['adsterra_popunder_enable']) ? '1' : '0');
        save_monetization_setting('adsterra_popunder_code', trim($_POST['adsterra_popunder_code'] ?? ''));
        save_monetization_setting('adsterra_banner_728_code', trim($_POST['adsterra_banner_728_code'] ?? ''));
        save_monetization_setting('adsterra_banner_300_code', trim($_POST['adsterra_banner_300_code'] ?? ''));

        log_activity('Updated Adsterra Monetization Settings', 'monetization', $_SESSION['admin_id'] ?? 1);
        $successMsg = 'Adsterra settings saved successfully!';
    }
}

$adsterraEnabled = get_monetization_setting('adsterra_enabled', '0');
$socialBarEnabled = get_monetization_setting('adsterra_social_bar_enable', '0');
$socialBarCode = get_monetization_setting('adsterra_social_bar_code', '');
$popunderEnabled = get_monetization_setting('adsterra_popunder_enable', '0');
$popunderCode = get_monetization_setting('adsterra_popunder_code', '');
$banner728Code = get_monetization_setting('adsterra_banner_728_code', '');
$banner300Code = get_monetization_setting('adsterra_banner_300_code', '');

$adminPageTitle = "Adsterra Ads";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">Adsterra Ads</h1>
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

        <!-- Master Switch for Adsterra -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem 1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.25rem 0;">Enable Adsterra Network</h3>
                <p style="font-size:0.8rem; color:#64748b; margin:0;">Toggle all Adsterra ad codes and scripts on your website</p>
            </div>
            <label style="position:relative; display:inline-block; width:48px; height:26px;">
                <input type="checkbox" name="adsterra_enabled" value="1" <?= $adsterraEnabled === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
            </label>
        </div>

        <!-- 1. Social Bar -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div>
                    <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.2rem 0;">Social Bar (High CPM)</h3>
                    <p style="font-size:0.8rem; color:#64748b; margin:0;">Interactive push-like notifications, chat widgets, and survey banners.</p>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="adsterra_social_bar_enable" value="1" <?= $socialBarEnabled === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>
            <textarea name="adsterra_social_bar_code" rows="3" placeholder="Paste your Adsterra Social Bar code script here..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"><?= h($socialBarCode) ?></textarea>
        </div>

        <!-- 2. Popunder -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div>
                    <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.2rem 0;">Popunder (OnClick Script)</h3>
                    <p style="font-size:0.8rem; color:#64748b; margin:0;">Opens monetization landing page in a background window on first click.</p>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="adsterra_popunder_enable" value="1" <?= $popunderEnabled === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>
            <textarea name="adsterra_popunder_code" rows="3" placeholder="Paste your Adsterra Popunder script tag here..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"><?= h($popunderCode) ?></textarea>
        </div>

        <!-- 3. Standard Banners (728x90 & 300x250) -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 1rem 0;">Adsterra Display Banners</h3>
            
            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    Leaderboard Banner (728x90) Script
                </label>
                <textarea name="adsterra_banner_728_code" rows="3" placeholder="Paste 728x90 banner code..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"><?= h($banner728Code) ?></textarea>
            </div>

            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    Medium Rectangle Banner (300x250) Script
                </label>
                <textarea name="adsterra_banner_300_code" rows="3" placeholder="Paste 300x250 sidebar banner code..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px; font-family:monospace; font-size:0.8rem;"><?= h($banner300Code) ?></textarea>
            </div>
        </div>

        <div style="text-align:right;">
            <button type="submit" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.65rem 1.75rem; border-radius:8px; font-weight:700; cursor:pointer;">
                Save Adsterra Settings
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
