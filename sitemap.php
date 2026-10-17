<?php
/**
 * Dynamic XML Sitemap Engine
 * CareerNexaa - Jobs & Career Portal
 *
 * Produces 100% valid W3C / Google Search Console compliant XML sitemap.
 * Automatically synchronizes published articles, categories, and static routes.
 * Dynamically resolves production canonical domain to prevent GSC host mismatch errors.
 */

// Buffer output to prevent whitespace or BOM corruption
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Send strict XML header with utf-8 encoding
header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex, follow');
header('Cache-Control: public, max-age=1800');

/**
 * Determine the canonical base URL for Google Search Console
 * Ensures Googlebot receives URLs belonging to the verified domain (careernexaa.com).
 *
 * @return string
 */
function get_sitemap_base_url(): string {
    // 1. Manual override via query parameter (for testing: ?domain=https://...)
    if (!empty($_GET['domain']) && filter_var($_GET['domain'], FILTER_VALIDATE_URL)) {
        return rtrim($_GET['domain'], '/');
    }

    // 2. Explicit local development mode (for local testing: ?local=1)
    if (isset($_GET['local']) && defined('BASE_URL')) {
        return rtrim(BASE_URL, '/');
    }

    // 3. Environment variable (.env APP_URL / BASE_URL)
    $envAppUrl = getenv('APP_URL') ?: getenv('BASE_URL');
    if (!empty($envAppUrl) && strpos($envAppUrl, 'http') === 0 && strpos($envAppUrl, 'localhost') === false) {
        return rtrim($envAppUrl, '/');
    }

    // 4. Live hosting HTTP request detection (cPanel / VPS)
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = strtolower($_SERVER['HTTP_HOST']);
        if (!in_array($host, ['localhost', '127.0.0.1', '::1']) && strpos($host, '192.168.') !== 0 && strpos($host, '10.') !== 0) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
            $scheme = $isHttps ? 'https://' : 'https://'; // Production domains should always prefer HTTPS
            return $scheme . $host;
        }
    }

    // 5. Database setting override
    $dbSetting = get_setting('canonical_site_url', '');
    if (!empty($dbSetting) && strpos($dbSetting, 'http') === 0) {
        return rtrim($dbSetting, '/');
    }

    // 6. Default canonical live production domain for CareerNexaa
    return 'https://careernexaa.com';
}

$siteBaseUrl = get_sitemap_base_url();
$db = getDBConnection();

// Fetch all active published articles (excluding trash & noindex)
$articlesStmt = $db->query("
    SELECT id, title, slug, featured_image, updated_at, created_at, published_at, is_job_posting
    FROM articles
    WHERE is_trash = 0 
      AND status = 'published'
      AND (robots_meta IS NULL OR robots_meta NOT LIKE '%noindex%')
    ORDER BY id DESC
");
$articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories
$categoriesStmt = $db->query("
    SELECT id, name, slug, created_at
    FROM categories
    ORDER BY id ASC
");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Static route definitions
$staticPages = [
    ['path' => '/',                 'freq' => 'daily',   'prio' => '1.0'],
    ['path' => '/articles',         'freq' => 'daily',   'prio' => '0.9'],
    ['path' => '/vlogs',            'freq' => 'daily',   'prio' => '0.9'],
    ['path' => '/trending',         'freq' => 'daily',   'prio' => '0.9'],
    ['path' => '/jobs',             'freq' => 'daily',   'prio' => '0.9'],
    ['path' => '/categories',       'freq' => 'weekly',  'prio' => '0.8'],
    ['path' => '/about',            'freq' => 'monthly', 'prio' => '0.6'],
    ['path' => '/contact',          'freq' => 'monthly', 'prio' => '0.6'],
    ['path' => '/privacy-policy',   'freq' => 'yearly',  'prio' => '0.5'],
    ['path' => '/terms',            'freq' => 'yearly',  'prio' => '0.5'],
    ['path' => '/cookie-policy',    'freq' => 'yearly',  'prio' => '0.5'],
    ['path' => '/disclaimer',       'freq' => 'yearly',  'prio' => '0.5']
];

$today = date('Y-m-d');

// Build XML structure
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
$xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

// 1. Static Pages
foreach ($staticPages as $sp) {
    $loc = ($sp['path'] === '/') ? $siteBaseUrl . '/' : $siteBaseUrl . $sp['path'];
    $xml .= "    <url>\n";
    $xml .= "        <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "        <lastmod>{$today}</lastmod>\n";
    $xml .= "        <changefreq>{$sp['freq']}</changefreq>\n";
    $xml .= "        <priority>{$sp['prio']}</priority>\n";
    $xml .= "    </url>\n";
}

// 2. Categories
foreach ($categories as $cat) {
    $catDate = !empty($cat['created_at']) ? date('Y-m-d', strtotime($cat['created_at'])) : $today;
    $loc = $siteBaseUrl . '/category/' . urlencode($cat['slug']);
    $xml .= "    <url>\n";
    $xml .= "        <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "        <lastmod>{$catDate}</lastmod>\n";
    $xml .= "        <changefreq>weekly</changefreq>\n";
    $xml .= "        <priority>0.7</priority>\n";
    $xml .= "    </url>\n";
}

// 3. Articles & Jobs
foreach ($articles as $art) {
    $rawDate = !empty($art['updated_at']) 
        ? $art['updated_at'] 
        : (!empty($art['published_at']) ? $art['published_at'] : (!empty($art['created_at']) ? $art['created_at'] : 'now'));
    $modDate = date('Y-m-d', strtotime($rawDate));
    $loc = $siteBaseUrl . '/article/' . urlencode($art['slug']);
    $prio = !empty($art['is_job_posting']) ? '0.9' : '0.8';
    $freq = !empty($art['is_job_posting']) ? 'daily' : 'weekly';

    $xml .= "    <url>\n";
    $xml .= "        <loc>" . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
    $xml .= "        <lastmod>{$modDate}</lastmod>\n";
    $xml .= "        <changefreq>{$freq}</changefreq>\n";
    $xml .= "        <priority>{$prio}</priority>\n";

    // Google Image Sitemap extension for featured images
    if (!empty($art['featured_image'])) {
        $cleanImg = ltrim($art['featured_image'], '/\\');
        if (strpos($cleanImg, 'http') === 0) {
            $imgLoc = $cleanImg;
        } elseif (strpos($cleanImg, 'uploads/') === 0) {
            $imgLoc = $siteBaseUrl . '/' . $cleanImg;
        } else {
            $imgLoc = $siteBaseUrl . '/uploads/articles/' . $cleanImg;
        }
        $imgTitle = !empty($art['title']) ? $art['title'] : 'Career Article Image';

        $xml .= "        <image:image>\n";
        $xml .= "            <image:loc>" . htmlspecialchars($imgLoc, ENT_XML1, 'UTF-8') . "</image:loc>\n";
        $xml .= "            <image:title>" . htmlspecialchars($imgTitle, ENT_XML1, 'UTF-8') . "</image:title>\n";
        $xml .= "        </image:image>\n";
    }

    $xml .= "    </url>\n";
}

$xml .= '</urlset>' . "\n";

// Clear any accidental output and echo clean XML
ob_end_clean();
echo $xml;

// Asynchronous / passive disk cache synchronization (fallback for static web servers)
$diskPath = __DIR__ . '/sitemap.xml';
if (is_writable(__DIR__) || (file_exists($diskPath) && is_writable($diskPath))) {
    @file_put_contents($diskPath, $xml, LOCK_EX);
}
exit;
