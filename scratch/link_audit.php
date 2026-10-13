<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

$results = [
    'frontend_templates' => [],
    'admin_templates' => [],
    'database_links' => [],
    'dead_links' => [],
    'hash_links' => [],
    'summary' => []
];

// Helper to test if an internal link has a matching physical file or valid route
function check_internal_target($url) {
    $baseUrl = rtrim(BASE_URL, '/');
    $adminUrl = rtrim(ADMIN_URL, '/');

    // Remove query string and fragment
    $clean = strtok($url, '?');
    $clean = strtok($clean, '#');

    // Strip BASE_URL or /job-portal
    if (strpos($clean, $adminUrl) === 0) {
        $rel = substr($clean, strlen($adminUrl));
        $fullPath = __DIR__ . '/../admin' . $rel;
    } elseif (strpos($clean, $baseUrl) === 0) {
        $rel = substr($clean, strlen($baseUrl));
        if ($rel === '' || $rel === '/') {
            $fullPath = __DIR__ . '/../index.php';
        } else {
            $fullPath = __DIR__ . '/..' . $rel;
        }
    } else {
        $rel = ltrim($clean, '/');
        $fullPath = __DIR__ . '/../' . $rel;
    }

    if (is_dir($fullPath)) {
        if (file_exists(rtrim($fullPath, '/') . '/index.php')) {
            return ['valid' => true, 'path' => rtrim($fullPath, '/') . '/index.php'];
        }
    }

    if (file_exists($fullPath)) {
        return ['valid' => true, 'path' => $fullPath];
    }

    // Check virtual rewrite routes: /article/slug or /category/slug
    if (preg_match('#^/article/([^/]+)$#i', $rel, $m) || preg_match('#^article/([^/]+)$#i', $rel, $m)) {
        return ['valid' => true, 'route' => 'virtual_article_slug'];
    }
    if (preg_match('#^/category/([^/]+)$#i', $rel, $m) || preg_match('#^category/([^/]+)$#i', $rel, $m)) {
        return ['valid' => true, 'route' => 'virtual_category_slug'];
    }

    return ['valid' => false, 'attempted' => $fullPath];
}

echo "=== STARTING COMPREHENSIVE LINK AUDIT ===\n\n";

// 1. Audit Header and Footer templates
$filesToScan = [
    'Header' => __DIR__ . '/../includes/header.php',
    'Footer' => __DIR__ . '/../includes/footer.php',
    'Homepage' => __DIR__ . '/../index.php',
    'Article View' => __DIR__ . '/../article.php',
    'Articles Archive' => __DIR__ . '/../articles.php',
    'Categories Archive' => __DIR__ . '/../categories.php',
    'Admin Header' => __DIR__ . '/../admin/includes/admin-header.php',
    'Admin Index' => __DIR__ . '/../admin/index.php',
    'Admin Articles' => __DIR__ . '/../admin/articles.php',
    'Admin Analytics' => __DIR__ . '/../admin/analytics.php',
    'Admin SEO' => __DIR__ . '/../admin/seo.php',
    'Admin Categories' => __DIR__ . '/../admin/categories.php',
    'Admin Tags' => __DIR__ . '/../admin/tags.php'
];

foreach ($filesToScan as $name => $path) {
    if (!file_exists($path)) {
        echo "WARNING: File not found: $path\n";
        continue;
    }
    $content = file_get_contents($path);
    preg_match_all('/href=["\']([^"\']+)["\']/i', $content, $matches);
    $foundLinks = array_unique($matches[1]);
    
    echo "--- Scanning $name (" . count($foundLinks) . " links found) ---\n";
    foreach ($foundLinks as $lnk) {
        $raw = trim($lnk);
        if ($raw === '#' || strpos($raw, '#') === 0 && strlen($raw) === 1) {
            $results['hash_links'][] = ['source' => $name, 'link' => $raw];
            echo "  [DUMMY HASH]: $raw in $name\n";
            continue;
        }

        // Ignore PHP echo expressions directly inside unparsed strings
        if (strpos($raw, '<?=') !== false || strpos($raw, '<?php') !== false) {
            // It's dynamic PHP, extract path pattern
            continue;
        }

        // If external link (http:// or https://)
        if (preg_match('#^https?://#i', $raw)) {
            // Check if local or external
            if (strpos($raw, 'http://localhost') === 0 || strpos($raw, 'http://127.0.0.1') === 0) {
                $status = check_internal_target($raw);
                if (!$status['valid']) {
                    $results['dead_links'][] = ['source' => $name, 'link' => $raw, 'status' => $status];
                    echo "  [DEAD INTERNAL]: $raw in $name (target missing)\n";
                } else {
                    echo "  [OK INTERNAL]: $raw\n";
                }
            } else {
                echo "  [EXTERNAL]: $raw\n";
            }
        } else {
            // Relative link
            $status = check_internal_target($raw);
            if (!$status['valid']) {
                $results['dead_links'][] = ['source' => $name, 'link' => $raw, 'status' => $status];
                echo "  [DEAD RELATIVE]: $raw in $name (target missing: {$status['attempted']})\n";
            } else {
                echo "  [OK RELATIVE]: $raw\n";
            }
        }
    }
    echo "\n";
}

// 2. Audit Dynamic Header & Footer Menus stored in Database
echo "--- Checking Database Header/Footer Menus ---\n";
$headerMenu = get_header_menu();
echo "Header Menu Items (" . count($headerMenu) . "):\n";
foreach ($headerMenu as $m) {
    $url = $m['url'] ?? '';
    $label = $m['label'] ?? '';
    $status = check_internal_target($url);
    echo "  Label: '$label' -> URL: '$url' -> Valid: " . ($status['valid'] ? 'YES' : 'NO') . "\n";
    if (!$status['valid']) {
        $results['dead_links'][] = ['source' => 'DB Header Menu', 'link' => $url, 'label' => $label];
    }
}

$footerMenu = get_footer_menu();
echo "Footer Menu Items (" . count($footerMenu) . "):\n";
foreach ($footerMenu as $m) {
    $url = $m['url'] ?? '';
    $label = $m['label'] ?? '';
    $status = check_internal_target($url);
    echo "  Label: '$label' -> URL: '$url' -> Valid: " . ($status['valid'] ? 'YES' : 'NO') . "\n";
    if (!$status['valid']) {
        $results['dead_links'][] = ['source' => 'DB Footer Menu', 'link' => $url, 'label' => $label];
    }
}
echo "\n";

// 3. Audit Categories & Articles
echo "--- Checking Category and Article Links ---\n";
$cats = $db->query("SELECT id, name, slug FROM categories")->fetchAll(PDO::FETCH_ASSOC);
echo "Total Categories: " . count($cats) . "\n";
foreach ($cats as $c) {
    if (empty($c['slug'])) {
        echo "  [WARNING] Category ID {$c['id']} '{$c['name']}' has EMPTY slug!\n";
    }
}

$articles = $db->query("SELECT id, title, slug, is_job_posting FROM articles WHERE is_trash = 0 LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "Sample Active Articles: " . count($articles) . "\n";
foreach ($articles as $a) {
    if (empty($a['slug'])) {
        echo "  [WARNING] Article ID {$a['id']} '{$a['title']}' has EMPTY slug!\n";
    }
}

// 4. Audit Pages table
$pages = $db->query("SELECT id, title, slug, status FROM pages")->fetchAll(PDO::FETCH_ASSOC);
echo "Static Pages in DB (" . count($pages) . "):\n";
foreach ($pages as $p) {
    echo "  Page: '{$p['title']}' -> slug: '{$p['slug']}' -> status: {$p['status']}\n";
}

echo "\n=== SUMMARY ===\n";
echo "Total Dummy Hash Links: " . count($results['hash_links']) . "\n";
echo "Total Dead/Broken Links: " . count($results['dead_links']) . "\n";
if (!empty($results['hash_links'])) {
    echo "List of Dummy Hash Links found:\n";
    foreach ($results['hash_links'] as $hl) {
        echo " - {$hl['source']}: {$hl['link']}\n";
    }
}
if (!empty($results['dead_links'])) {
    echo "List of Dead Links found:\n";
    foreach ($results['dead_links'] as $dl) {
        echo " - {$dl['source']}: {$dl['link']}\n";
    }
}
