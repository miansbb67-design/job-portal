<?php
/**
 * Admin Panel - Monetization: Google AdSense Dashboard
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errorMsg = 'Security verification failed or session expired.';
    } else {
        $pubId = trim($_POST['adsense_pub_id'] ?? '');
        $autoAds = isset($_POST['adsense_auto_ads']) ? '1' : '0';
        $adsenseStatus = trim($_POST['adsense_status'] ?? 'active');

        // Clean pub id: ensure ca-pub- format
        if (!empty($pubId) && strpos($pubId, 'ca-pub-') === false && strpos($pubId, 'pub-') === 0) {
            $pubId = 'ca-' . $pubId;
        }

        save_monetization_setting('adsense_pub_id', $pubId);
        save_monetization_setting('adsense_auto_ads', $autoAds);
        save_monetization_setting('adsense_status', $adsenseStatus);

        log_activity('Updated Google AdSense Settings', 'monetization', $_SESSION['admin_id'] ?? 1);
        $successMsg = 'Google AdSense settings saved successfully!';
    }
}

$pubId = get_monetization_setting('adsense_pub_id', '');
$autoAds = get_monetization_setting('adsense_auto_ads', '1');
$adsenseStatus = get_monetization_setting('adsense_status', !empty($pubId) ? 'active' : 'pending');

// Stats
$activeSlotsCount = (int)$db->query("SELECT COUNT(*) FROM ad_placements WHERE is_enabled = 1")->fetchColumn();
$totalSlotsCount = (int)$db->query("SELECT COUNT(*) FROM ad_placements")->fetchColumn();
$adsTxtExists = file_exists(__DIR__ . '/../../ads.txt');

$adminPageTitle = "AdSense Dashboard";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">AdSense Dashboard</h1>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="<?= ADMIN_URL ?>/monetization/placement.php" class="admin-btn" style="background:#059669; color:#fff; border:none; padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                Manage Ad Placements
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

    <!-- Overview Stats Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.75rem;">
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">AdSense Status</div>
            <div style="font-size:1.45rem; font-weight:800; color:<?= !empty($pubId) ? '#059669' : '#f59e0b' ?>; margin:0.35rem 0;">
                <?= !empty($pubId) ? 'Connected' : 'Setup Required' ?>
            </div>
            <div style="font-size:0.775rem; color:#64748b;"><?= !empty($pubId) ? h($pubId) : 'No Publisher ID configured' ?></div>
        </div>

        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Active Ad Slots</div>
            <div style="font-size:1.45rem; font-weight:800; color:#0f172a; margin:0.35rem 0;">
                <?= $activeSlotsCount ?> / <?= $totalSlotsCount ?>
            </div>
            <div style="font-size:0.775rem; color:#64748b;"><a href="<?= ADMIN_URL ?>/monetization/placement.php" style="color:#059669; font-weight:600; text-decoration:none;">Configure slots &rarr;</a></div>
        </div>

        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
            <div style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">Ads.txt Compliance</div>
            <div style="font-size:1.45rem; font-weight:800; color:<?= $adsTxtExists ? '#059669' : '#dc2626' ?>; margin:0.35rem 0;">
                <?= $adsTxtExists ? 'Verified' : 'Missing' ?>
            </div>
            <div style="font-size:0.775rem; color:#64748b;"><a href="<?= ADMIN_URL ?>/monetization/ads-txt.php" style="color:#059669; font-weight:600; text-decoration:none;">Edit ads.txt &rarr;</a></div>
        </div>
    </div>

    <!-- Main Configuration Card -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.5rem;">
        <h2 style="font-size:1.2rem; font-weight:800; color:#0f172a; margin:0 0 1rem 0;">Google AdSense Account Credentials</h2>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div style="margin-bottom:1.35rem;">
                <label for="adsense_pub_id" style="display:block; font-size:0.875rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    AdSense Publisher ID <span style="color:#e11d48;">*</span>
                </label>
                <input 
                    type="text" 
                    id="adsense_pub_id" 
                    name="adsense_pub_id" 
                    class="form-input" 
                    value="<?= h($pubId) ?>" 
                    placeholder="e.g. ca-pub-1234567890123456"
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.95rem; font-family:monospace;"
                >
                <span style="font-size:0.775rem; color:#64748b; display:block; margin-top:0.35rem;">
                    Found in your Google AdSense Account > Settings > Account information (format: <code>ca-pub-XXXXXXXXXXXXXXXX</code>).
                </span>
            </div>

            <div style="margin-bottom:1.35rem; display:flex; align-items:center; justify-content:space-between; background:#f8fafc; padding:1rem 1.15rem; border-radius:10px; border:1px solid #e2e8f0;">
                <div>
                    <div style="font-weight:700; color:#0f172a; font-size:0.9rem;">Google Auto Ads (Recommended)</div>
                    <div style="font-size:0.775rem; color:#64748b;">Automatically lets Google place high-yield ads in optimal positions across your site</div>
                </div>
                <label style="position:relative; display:inline-block; width:48px; height:26px;">
                    <input type="checkbox" name="adsense_auto_ads" value="1" <?= $autoAds === '1' ? 'checked' : '' ?> style="opacity:0; width:0; height:0;">
                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.3s; border-radius:34px;"></span>
                </label>
            </div>

            <div style="margin-bottom:1.5rem;">
                <label for="adsense_status" style="display:block; font-size:0.875rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    Approval Status
                </label>
                <select name="adsense_status" id="adsense_status" class="form-input" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
                    <option value="active" <?= $adsenseStatus === 'active' ? 'selected' : '' ?>>Active / Approved (Live ads display to visitors)</option>
                    <option value="pending" <?= $adsenseStatus === 'pending' ? 'selected' : '' ?>>Site Under Review (Verification code active in header)</option>
                    <option value="paused" <?= $adsenseStatus === 'paused' ? 'selected' : '' ?>>Paused (Temporarily suppress AdSense)</option>
                </select>
            </div>

            <div style="text-align:right;">
                <button type="submit" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.65rem 1.75rem; border-radius:8px; font-weight:700; cursor:pointer;">
                    Save AdSense Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Script Injection Preview -->
    <div style="background:#0f172a; border-radius:12px; padding:1.25rem 1.5rem; color:#94a3b8; font-size:0.85rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.65rem;">
            <span style="font-weight:700; color:#f8fafc; font-size:0.85rem;">Active Google AdSense Header Script</span>
            <span style="font-size:0.7rem; background:#1e293b; color:#34d399; padding:2px 8px; border-radius:999px;">Auto-Injected in &lt;head&gt;</span>
        </div>
        <pre style="margin:0; overflow-x:auto; font-family:monospace; color:#38bdf8; font-size:0.8rem; line-height:1.5;"><code>&lt;script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= !empty($pubId) ? h($pubId) : 'ca-pub-XXXXXXXXXXXXXXXX' ?>" crossorigin="anonymous"&gt;&lt;/script&gt;</code></pre>
    </div>
</div>

<style>
input:checked + .slider { background-color: #059669 !important; }
input:checked + .slider:before { transform: translateX(22px); }
.slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .3s; border-radius: 50%; }
</style>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
