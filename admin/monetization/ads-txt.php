<?php
/**
 * Admin Panel - Monetization: Ads.txt File Manager
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

$adsTxtPath = __DIR__ . '/../../ads.txt';
$successMsg = '';
$errorMsg = '';

// Save ads.txt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errorMsg = 'Security verification failed or session expired.';
    } else {
        $content = trim($_POST['ads_txt_content'] ?? '');
        if (@file_put_contents($adsTxtPath, $content . "\n") !== false) {
            log_activity('Updated ads.txt file', 'monetization', $_SESSION['admin_id'] ?? 1);
            $successMsg = 'ads.txt file updated and saved successfully!';
        } else {
            $errorMsg = 'Unable to write to ads.txt file. Please check folder write permissions.';
        }
    }
}

// Load current content or default
$currentContent = '';
if (file_exists($adsTxtPath)) {
    $currentContent = file_get_contents($adsTxtPath);
} else {
    $pubId = get_monetization_setting('adsense_pub_id', '');
    $cleanPub = !empty($pubId) ? str_replace('ca-', '', $pubId) : 'pub-XXXXXXXXXXXXXXXX';
    $currentContent = "# Google AdSense Publisher Record\ngoogle.com, {$cleanPub}, DIRECT, f08c47fec0942fa0\n";
}

$adminPageTitle = "Ads.txt";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">Ads.txt Manager</h1>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="<?= BASE_URL ?>/ads.txt" target="_blank" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#334155; padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                View Live ads.txt &rarr;
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

    <!-- Info card -->
    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1.25rem; margin-bottom:1.5rem; font-size:0.875rem; color:#475569; line-height:1.5;">
        <strong>What is ads.txt?</strong> Authorized Digital Sellers (ads.txt) is an IAB initiative that lets you declare who is authorized to sell your ad inventory. Having a verified <code>ads.txt</code> prevents ad fraud and guarantees 100% of your AdSense revenue is credited to your account.
    </div>

    <!-- Quick Append Buttons -->
    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:0.85rem;">
        <button type="button" onclick="appendAdSenseTemplate()" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#0f172a; padding:0.4rem 0.75rem; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer;">
            + Insert AdSense Row
        </button>
        <button type="button" onclick="appendAdsterraTemplate()" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#0f172a; padding:0.4rem 0.75rem; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer;">
            + Insert Adsterra Row
        </button>
        <button type="button" onclick="appendMonetagTemplate()" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#0f172a; padding:0.4rem 0.75rem; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer;">
            + Insert Monetag Row
        </button>
    </div>

    <!-- Code Editor Form -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

            <div style="margin-bottom:1.25rem;">
                <label for="adsTxtArea" style="display:block; font-size:0.875rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    ads.txt Records (One line per authorized partner)
                </label>
                <textarea 
                    name="ads_txt_content" 
                    id="adsTxtArea" 
                    rows="14" 
                    style="width:100%; padding:0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-family:'Courier New', monospace; font-size:0.875rem; line-height:1.6; color:#0f172a; background:#f8fafc;"
                ><?= h($currentContent) ?></textarea>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:0.8rem; color:#64748b;">
                    File Location: <code><?= h($adsTxtPath) ?></code>
                </span>
                <button type="submit" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.65rem 1.75rem; border-radius:8px; font-weight:700; cursor:pointer;">
                    Save ads.txt File
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function appendAdSenseTemplate() {
    const area = document.getElementById('adsTxtArea');
    const pubId = '<?= h(str_replace('ca-', '', get_monetization_setting('adsense_pub_id', 'pub-XXXXXXXXXXXXXXXX'))) ?>';
    const row = `google.com, ${pubId}, DIRECT, f08c47fec0942fa0\n`;
    if (!area.value.includes('google.com')) {
        area.value = row + area.value;
    } else {
        area.value += row;
    }
}

function appendAdsterraTemplate() {
    const area = document.getElementById('adsTxtArea');
    const row = `adsterra.com, YOUR_ACCOUNT_ID, DIRECT\n`;
    area.value += row;
}

function appendMonetagTemplate() {
    const area = document.getElementById('adsTxtArea');
    const row = `propellerads.com, YOUR_PUBLISHER_ID, DIRECT\n`;
    area.value += row;
}
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
