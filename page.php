<?php
/**
 * Public Website - Dynamic Static Page Viewer
 * Displays pages from the 'pages' table (e.g. About, Privacy, Terms, Custom Pages).
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (empty($slug)) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

// Redirect canonical pages if dedicated PHP script exists
$canonicalScripts = [
    'about' => 'about.php',
    'about-us' => 'about.php',
    'privacy-policy' => 'privacy-policy.php',
    'privacy' => 'privacy-policy.php',
    'terms' => 'terms.php',
    'terms-and-conditions' => 'terms.php',
    'disclaimer' => 'disclaimer.php',
    'contact' => 'contact.php',
    'cookie-policy' => 'cookie-policy.php'
];

if (isset($canonicalScripts[strtolower($slug)])) {
    $target = $canonicalScripts[strtolower($slug)];
    if (file_exists(__DIR__ . '/' . $target)) {
        require_once __DIR__ . '/' . $target;
        exit;
    }
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM pages WHERE (slug = ? OR slug = ?) AND status = 'published' LIMIT 1");
$stmt->execute([$slug, str_replace('-', '', $slug)]);
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$page) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

$pageTitle = $page['title'] . ' | ' . get_site_name();
$metaDescription = !empty($page['meta_description']) ? $page['meta_description'] : 'Read our official ' . $page['title'] . ' details on CareerNexaa.';
include __DIR__ . '/includes/header.php';
?>

<div class="site-container" style="max-width:960px; margin:3rem auto; padding:0 1.25rem;">
    <!-- Breadcrumbs -->
    <nav class="article-breadcrumbs" style="margin-bottom:1.25rem; font-size:0.875rem; color:#64748b; display:flex; align-items:center; gap:0.5rem;">
        <a href="<?= BASE_URL ?>/" style="color:#64748b; text-decoration:none;">Home</a>
        <span>&rsaquo;</span>
        <span style="color:#0f172a; font-weight:600;"><?= h($page['title']) ?></span>
    </nav>

    <!-- Page Content Card -->
    <article class="page-content-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:2.5rem; box-shadow:0 4px 20px -2px rgba(0,0,0,0.05); line-height:1.75; color:#334155;">
        <h1 style="font-size:2.25rem; font-weight:800; color:#0f172a; margin-top:0; margin-bottom:1.5rem; letter-spacing:-0.025em; border-bottom:1px solid #f1f5f9; padding-bottom:1rem;">
            <?= h($page['title']) ?>
        </h1>

        <div class="page-body-content" style="font-size:1.05rem;">
            <?= sanitize_article_html($page['content']) ?>
        </div>
    </article>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
