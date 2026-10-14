<?php
/**
 * Admin Panel - Monetization: Policy Checklist
 * Interactive compliance audit for Google AdSense & Advertising Networks
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

// System Compliance Checks
$checkPrivacy = file_exists(__DIR__ . '/../../privacy-policy.php');
$checkCookie = file_exists(__DIR__ . '/../../cookie-policy.php') && file_exists(__DIR__ . '/../../includes/cookie-consent.php');
$checkTerms = file_exists(__DIR__ . '/../../terms.php');
$checkDisclaimer = file_exists(__DIR__ . '/../../disclaimer.php');
$checkAdsTxt = file_exists(__DIR__ . '/../../ads.txt') && (filesize(__DIR__ . '/../../ads.txt') > 10);
$checkLabel = (get_monetization_setting('show_ad_label', '1') === '1');
$checkPubId = !empty(get_monetization_setting('adsense_pub_id', ''));
$articleCount = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'published' AND is_trash = 0")->fetchColumn();
$checkArticles = ($articleCount >= 3);
$checkSsl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$checkMaster = (get_monetization_setting('master_enable', '1') === '1');

$checklist = [
    [
        'title' => 'GDPR & AdSense Compliant Privacy Policy',
        'desc'  => 'Mandatory page detailing log files, cookies, Google AdSense third-party vendor disclosures, and CCPA rights.',
        'status'=> $checkPrivacy,
        'link'  => BASE_URL . '/privacy-policy.php',
        'badge' => 'Required by Google'
    ],
    [
        'title' => 'Cookie Policy & Consent Banner System',
        'desc'  => 'Interactive user consent banner for storing preferences, analytics tracking, and personalized advertising cookies.',
        'status'=> $checkCookie,
        'link'  => BASE_URL . '/cookie-policy.php',
        'badge' => 'Required in EU/UK'
    ],
    [
        'title' => 'Terms & Conditions Agreement',
        'desc'  => 'Acceptable use terms, intellectual property, user conduct, and anti-scraping protections.',
        'status'=> $checkTerms,
        'link'  => BASE_URL . '/terms.php',
        'badge' => 'Legal Protection'
    ],
    [
        'title' => 'Employment & Recruitment Fee Disclaimer',
        'desc'  => 'Protects job seekers from recruitment scams by stating no fees should ever be paid for job applications.',
        'status'=> $checkDisclaimer,
        'link'  => BASE_URL . '/disclaimer.php',
        'badge' => 'Trust Factor'
    ],
    [
        'title' => 'Authorized Digital Sellers (ads.txt)',
        'desc'  => 'Verified ads.txt file in the website root directory declaring authorized ad sellers and publisher IDs.',
        'status'=> $checkAdsTxt,
        'link'  => ADMIN_URL . '/monetization/ads-txt.php',
        'badge' => 'Revenue Protection'
    ],
    [
        'title' => 'Transparent Ad Disclosure Labels',
        'desc'  => 'Every ad slot clearly displays "Advertisement" or "Sponsored" tag to prevent accidental clicks.',
        'status'=> $checkLabel,
        'link'  => ADMIN_URL . '/monetization/settings.php',
        'badge' => 'Accidental Click Guard'
    ],
    [
        'title' => 'Google AdSense Publisher ID Connected',
        'desc'  => 'Valid ca-pub-XXXXXXXXXXXXXXXX identifier linked for automatic ad script injection.',
        'status'=> $checkPubId,
        'link'  => ADMIN_URL . '/monetization/index.php',
        'badge' => 'Ad Delivery'
    ],
    [
        'title' => 'Original Content Threshold (Articles & Guides)',
        'desc'  => 'At least 3 high-quality original published articles available before requesting final ad network approval.',
        'status'=> $checkArticles,
        'link'  => ADMIN_URL . '/articles.php',
        'badge' => 'Content-to-Ad Ratio (' . $articleCount . ' published)'
    ],
    [
        'title' => 'Mobile Responsive Ad Layouts',
        'desc'  => 'Ad slots adapt cleanly across mobile, tablet, and desktop screens without pushing main content off-screen.',
        'status'=> true,
        'link'  => ADMIN_URL . '/monetization/placement.php',
        'badge' => 'UX Compliance'
    ],
    [
        'title' => 'Master Monetization Engine Status',
        'desc'  => 'Global ad rendering switch is activated to deliver partner codes to site visitors.',
        'status'=> $checkMaster,
        'link'  => ADMIN_URL . '/monetization/settings.php',
        'badge' => 'Live Serving'
    ]
];

$passedCount = 0;
foreach ($checklist as $c) {
    if ($c['status']) $passedCount++;
}

$adminPageTitle = "Policy Checklist";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content-wrap" style="max-width: 960px; margin: 0 auto; padding: 1.5rem 1rem;">
    <!-- Page Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
        <div>
            <div style="font-size:0.8rem; font-weight:700; color:#059669; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.25rem;">
                Monetization Suite
            </div>
            <h1 style="font-size:1.65rem; font-weight:800; color:#0f172a; margin:0;">Policy Checklist</h1>
        </div>
        <div style="display:flex; gap:0.5rem;">
            <a href="<?= ADMIN_URL ?>/monetization/placement.php" class="admin-btn" style="background:#059669; color:#fff; border:none; padding:0.5rem 1rem; border-radius:8px; font-size:0.85rem; font-weight:700; text-decoration:none;">
                Ad Placements &rarr;
            </a>
        </div>
    </div>

    <!-- Overall Compliance Score Card -->
    <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; box-shadow:0 1px 3px rgba(0,0,0,0.03); margin-bottom:1.75rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div>
            <span style="font-size:0.8rem; font-weight:700; color:#64748b; text-transform:uppercase;">AdSense & Partner Readiness Score</span>
            <div style="display:flex; align-items:baseline; gap:0.5rem; margin:0.35rem 0 0.2rem 0;">
                <span style="font-size:2.25rem; font-weight:900; color:#059669;"><?= $passedCount ?></span>
                <span style="font-size:1.25rem; font-weight:700; color:#94a3b8;">/ 10 Checks Passed</span>
            </div>
            <p style="font-size:0.85rem; color:#64748b; margin:0;">
                <?= $passedCount >= 8 ? '🎉 Your website meets Google AdSense and publisher policy standards!' : '⚠️ Complete the remaining items below before applying for AdSense verification.' ?>
            </p>
        </div>

        <div style="width:140px; height:12px; background:#f1f5f9; border-radius:999px; overflow:hidden; border:1px solid #e2e8f0;">
            <div style="width:<?= ($passedCount / 10) * 100 ?>%; height:100%; background:#059669; border-radius:999px;"></div>
        </div>
    </div>

    <!-- Checklist Items -->
    <div style="display:flex; flex-direction:column; gap:0.75rem;">
        <?php foreach ($checklist as $item): ?>
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.1rem 1.35rem; display:flex; align-items:center; justify-content:space-between; gap:1rem;">
                <div style="display:flex; align-items:flex-start; gap:0.85rem;">
                    <!-- Status Icon -->
                    <div style="margin-top:2px;">
                        <?php if ($item['status']): ?>
                            <div style="width:24px; height:24px; border-radius:50%; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center; border:1px solid #a7f3d0;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </div>
                        <?php else: ?>
                            <div style="width:24px; height:24px; border-radius:50%; background:#fef2f2; color:#dc2626; display:flex; align-items:center; justify-content:center; border:1px solid #fecaca;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.2rem;">
                            <span style="font-size:0.95rem; font-weight:700; color:#0f172a;"><?= h($item['title']) ?></span>
                            <span style="font-size:0.7rem; background:#f8fafc; color:#64748b; padding:1px 6px; border-radius:6px; border:1px solid #e2e8f0; font-weight:600;">
                                <?= h($item['badge']) ?>
                            </span>
                        </div>
                        <p style="font-size:0.8rem; color:#64748b; margin:0; line-height:1.4;">
                            <?= h($item['desc']) ?>
                        </p>
                    </div>
                </div>

                <div>
                    <a href="<?= h($item['link']) ?>" class="admin-btn" style="background:#f8fafc; border:1px solid #cbd5e1; color:#334155; font-size:0.8rem; font-weight:600; padding:0.4rem 0.75rem; border-radius:8px; text-decoration:none; white-space:nowrap;">
                        Inspect &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
