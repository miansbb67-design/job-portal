<?php
/**
 * Admin Panel - Monetization: Global Settings & Controls
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
        save_monetization_setting('master_enable', isset($_POST['master_enable']) ? '1' : '0');
        save_monetization_setting('show_ad_label', isset($_POST['show_ad_label']) ? '1' : '0');
        save_monetization_setting('ad_label_text', trim($_POST['ad_label_text'] ?? 'Advertisement'));
        save_monetization_setting('hide_for_admin', isset($_POST['hide_for_admin']) ? '1' : '0');
        save_monetization_setting('ad_spacing', trim($_POST['ad_spacing'] ?? 'normal'));
        save_monetization_setting('disable_on_legal_pages', isset($_POST['disable_on_legal_pages']) ? '1' : '0');

        log_activity('Updated Global Monetization Settings', 'monetization', $_SESSION['admin_id'] ?? 1);
        $successMsg = 'Monetization settings saved successfully!';
    }
}

$masterEnable = get_monetization_setting('master_enable', '1');
$showAdLabel = get_monetization_setting('show_ad_label', '1');
$adLabelText = get_monetization_setting('ad_label_text', 'Advertisement');
$hideForAdmin = get_monetization_setting('hide_for_admin', '0');
$adSpacing = get_monetization_setting('ad_spacing', 'normal');
$disableOnLegal = get_monetization_setting('disable_on_legal_pages', '1');

$adminPageTitle = "Monetization Settings";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">Monetization Settings</h1>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="<?= ADMIN_URL ?>/monetization/placement.php" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#334155; padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:600; text-decoration:none;">
                Ad Placements &rarr;
            </a>
        </div>
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

        <!-- Global Master Switch -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem 1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.25rem 0;">Master Ad Monetization Switch</h3>
                <p style="font-size:0.8rem; color:#64748b; margin:0;">Turn ALL advertisements on or off across the entire website in 1 click</p>
            </div>
            <label style="position:relative; display:inline-block; width:48px; height:26px;">
                <input type="checkbox" name="master_enable" value="1" <?= $masterEnable === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
            </label>
        </div>

        <!-- Ad Disclosure Label -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <div>
                    <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 0.2rem 0;">Ad Disclosure Label</h3>
                    <p style="font-size:0.8rem; color:#64748b; margin:0;">Displays a transparent "Advertisement" disclosure tag above every ad slot (Mandatory for AdSense policy).</p>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="show_ad_label" value="1" <?= $showAdLabel === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>

            <div>
                <label for="ad_label_text" style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    Disclosure Text Label
                </label>
                <input type="text" name="ad_label_text" id="ad_label_text" value="<?= h($adLabelText) ?>" class="form-input" style="width:100%; max-width:320px; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
            </div>
        </div>

        <!-- Compliance & UX Options -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
            <h3 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0 0 1rem 0;">Audience & Quality Protections</h3>

            <!-- Hide for Admin -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 0; border-bottom:1px solid #f1f5f9;">
                <div>
                    <div style="font-weight:700; color:#0f172a; font-size:0.9rem;">Hide Ads for Logged-In Administrators</div>
                    <div style="font-size:0.775rem; color:#64748b;">Prevents accidental staff clicks and provides a clean editing experience</div>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="hide_for_admin" value="1" <?= $hideForAdmin === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>

            <!-- Disable on Legal Pages -->
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.75rem 0; border-bottom:1px solid #f1f5f9;">
                <div>
                    <div style="font-weight:700; color:#0f172a; font-size:0.9rem;">Suppress Ads on Legal & Compliance Pages</div>
                    <div style="font-size:0.775rem; color:#64748b;">Disables ads on Privacy Policy, Cookie Policy, Terms of Service, and Contact forms</div>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="disable_on_legal_pages" value="1" <?= $disableOnLegal === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>

            <!-- Ad Spacing -->
            <div style="padding-top:1rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    Accidental Click Spacing (Buffer Margins)
                </label>
                <select name="ad_spacing" class="form-input" style="width:100%; max-width:320px; padding:0.6rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
                    <option value="normal" <?= $adSpacing === 'normal' ? 'selected' : '' ?>>Normal (1.5rem margin - Recommended)</option>
                    <option value="relaxed" <?= $adSpacing === 'relaxed' ? 'selected' : '' ?>>Relaxed (2.5rem margin - Maximum Protection)</option>
                    <option value="compact" <?= $adSpacing === 'compact' ? 'selected' : '' ?>>Compact (0.75rem margin)</option>
                </select>
            </div>
        </div>

        <div style="text-align:right;">
            <button type="submit" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.65rem 1.75rem; border-radius:8px; font-weight:700; cursor:pointer;">
                Save Monetization Settings
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
