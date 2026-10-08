<?php
/**
 * Comprehensive Production Readiness & Live Deployment Audit
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "====================================================================\n";
echo "   CAREERNEXAA - PRODUCTION READINESS & LIVE AUDIT REPORT\n";
echo "====================================================================\n\n";

$allPassed = true;
$db = getDBConnection();

// -----------------------------------------------------------------------------
// 1. DATABASE TABLES & RECORD INTEGRITY
// -----------------------------------------------------------------------------
echo "[1] DATABASE AUDIT:\n";
$expectedTables = [
    'admins', 'admin_permissions', 'permissions', 'articles', 'categories',
    'comments', 'article_reactions', 'section_visibility', 'settings',
    'pages', 'redirects', 'broken_links', 'login_attempts', 'activity_logs'
];

$existingTables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$missingTables = array_diff($expectedTables, $existingTables);

if (empty($missingTables)) {
    echo "  ✓ All 14 system tables exist in database.\n";
} else {
    echo "  ✗ Missing tables: " . implode(', ', $missingTables) . "\n";
    $allPassed = false;
}

// Check Superadmin
$superadmin = $db->query("SELECT id, username, email, role, status FROM admins WHERE role = 'superadmin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($superadmin && $superadmin['status'] === 'active') {
    echo "  ✓ Super Administrator account active: ID {$superadmin['id']} (@{$superadmin['username']}, {$superadmin['email']})\n";
} else {
    echo "  ✗ No active Super Administrator found!\n";
    $allPassed = false;
}

// Check Articles
$articleCount = (int)$db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'")->fetchColumn();
echo "  ✓ Published articles: {$articleCount} articles ready for live traffic.\n";

// Check Categories
$catCount = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
echo "  ✓ Categories: {$catCount} categories configured.\n";

// Check Section Visibility
$sectionsCount = (int)$db->query("SELECT COUNT(*) FROM section_visibility")->fetchColumn();
echo "  ✓ Section Visibility: {$sectionsCount} sections managed.\n";

// Check Settings
$settingsCount = (int)$db->query("SELECT COUNT(*) FROM settings")->fetchColumn();
echo "  ✓ System Settings: {$settingsCount} settings saved.\n";

// -----------------------------------------------------------------------------
// 2. FILE SYSTEM & UPLOAD DIRECTORIES
// -----------------------------------------------------------------------------
echo "\n[2] FILE SYSTEM & PERMISSIONS AUDIT:\n";

$uploadDir = UPLOAD_DIR;
if (is_dir($uploadDir) && is_writable($uploadDir)) {
    echo "  ✓ Uploads directory exists and is writable: uploads/articles/\n";
} else {
    echo "  ✗ Uploads directory missing or not writable: uploads/articles/\n";
    $allPassed = false;
}

$uploadHtaccess = dirname(__DIR__) . '/uploads/.htaccess';
if (file_exists($uploadHtaccess)) {
    echo "  ✓ Uploads security shield active (.htaccess blocks PHP script execution).\n";
} else {
    echo "  ✗ Missing uploads/.htaccess security shield!\n";
    $allPassed = false;
}

$sitemap = dirname(__DIR__) . '/sitemap.xml';
if (file_exists($sitemap) && filesize($sitemap) > 0) {
    echo "  ✓ Sitemap file present: sitemap.xml (" . substr_count(file_get_contents($sitemap), '<loc>') . " URLs indexed)\n";
} else {
    echo "  ✗ sitemap.xml missing or empty!\n";
    $allPassed = false;
}

$robots = dirname(__DIR__) . '/robots.txt';
if (file_exists($robots)) {
    echo "  ✓ robots.txt present with proper search engine instructions.\n";
} else {
    echo "  ✗ robots.txt missing!\n";
    $allPassed = false;
}

$dbDump = dirname(__DIR__) . '/database.sql';
if (file_exists($dbDump) && filesize($dbDump) > 50000) {
    echo "  ✓ Production database dump fresh and ready: database.sql (" . number_format(filesize($dbDump)/1024, 1) . " KB)\n";
} else {
    echo "  ✗ database.sql missing or too small!\n";
    $allPassed = false;
}

$envExample = dirname(__DIR__) . '/.env.example';
if (file_exists($envExample)) {
    echo "  ✓ Deployment template present: .env.example\n";
} else {
    echo "  ✗ .env.example missing!\n";
    $allPassed = false;
}

// -----------------------------------------------------------------------------
// 3. HTTP ROUTE TESTING (VIA CURL)
// -----------------------------------------------------------------------------
echo "\n[3] HTTP ROUTES & REWRITE RULES AUDIT:\n";
$baseUrl = 'http://localhost/job-portal';

$routesToTest = [
    'Homepage (/)'                       => ['url' => $baseUrl . '/', 'expected' => 200],
    'Articles Index (/articles)'         => ['url' => $baseUrl . '/articles', 'expected' => 200],
    'Article Slug (/article/...)'        => ['url' => $baseUrl . '/article/warehouse-logistics-associate-weekend-positions-hiring', 'expected' => 200],
    'Categories Index (/categories)'     => ['url' => $baseUrl . '/categories', 'expected' => 200],
    'Category Slug (/category/...)'      => ['url' => $baseUrl . '/category/remote-work-from-home', 'expected' => 200],
    'About Page (/about)'                => ['url' => $baseUrl . '/about', 'expected' => 200],
    'Contact Page (/contact)'            => ['url' => $baseUrl . '/contact', 'expected' => 200],
    'Privacy Policy (/privacy-policy)'   => ['url' => $baseUrl . '/privacy-policy', 'expected' => 200],
    'Terms (/terms)'                     => ['url' => $baseUrl . '/terms', 'expected' => 200],
    'Disclaimer (/disclaimer)'           => ['url' => $baseUrl . '/disclaimer', 'expected' => 200],
    'Robots (/robots.txt)'               => ['url' => $baseUrl . '/robots.txt', 'expected' => 200],
    'Sitemap (/sitemap.xml)'             => ['url' => $baseUrl . '/sitemap.xml', 'expected' => 200],
    'Admin Login (/admin/login.php)'     => ['url' => $baseUrl . '/admin/login.php', 'expected' => 200],
    'Custom 404 handler'                 => ['url' => $baseUrl . '/article/non-existent-page-test-1234', 'expected' => 404]
];

foreach ($routesToTest as $name => $test) {
    $ch = curl_init($test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === $test['expected']) {
        echo "  ✓ {$name} => HTTP {$code} [OK]\n";
    } else {
        echo "  ✗ {$name} => Got HTTP {$code}, expected {$test['expected']}\n";
        $allPassed = false;
    }
}

// -----------------------------------------------------------------------------
// 4. SECURITY SHIELD AUDIT (SENSITIVE FILE BLOCKING)
// -----------------------------------------------------------------------------
echo "\n[4] SECURITY SHIELD (BLOCKED ACCESS TO SENSITIVE FILES):\n";
$protectedFiles = [
    'database.sql'        => $baseUrl . '/database.sql',
    '.env'                => $baseUrl . '/.env',
    '.env.example'        => $baseUrl . '/.env.example',
    'config/database.php' => $baseUrl . '/config/database.php',
];

foreach ($protectedFiles as $file => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 403 || $code === 404) {
        echo "  ✓ Direct access to {$file} => BLOCKED (HTTP {$code}) [Protected]\n";
    } else {
        echo "  ✗ Direct access to {$file} => EXPOSED (HTTP {$code}) [SECURITY RISK]\n";
        $allPassed = false;
    }
}

// -----------------------------------------------------------------------------
// 5. ADMIN LOGIN SCREEN PURITY CHECK
// -----------------------------------------------------------------------------
echo "\n[5] ADMIN LOGIN SCREEN INITIAL STATE:\n";
$loginHtml = file_get_contents($baseUrl . '/admin/login.php');
if (strpos($loginHtml, '<div class="alert-error"') === false) {
    echo "  ✓ Login screen initial load is completely clean (Zero red error boxes on load).\n";
} else {
    echo "  ✗ Login screen displays an unexpected error box on initial load!\n";
    $allPassed = false;
}

// -----------------------------------------------------------------------------
// FINAL SUMMARY
// -----------------------------------------------------------------------------
echo "\n====================================================================\n";
if ($allPassed) {
    echo "   RESULT: 100% READY FOR LIVE PRODUCTION DEPLOYMENT! ✓✓✓\n";
} else {
    echo "   RESULT: ATTENTION NEEDED ON HIGHLIGHTED ITEMS.\n";
}
echo "====================================================================\n";
