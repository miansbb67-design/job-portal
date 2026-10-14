<?php

/**
 * Public Website Header
 * Reusable HTML head, SEO metadata, navigation, and mobile menu.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Send X-Robots-Tag HTTP header for search engine crawlers and SEO audit tools
if (!headers_sent()) {
    header('X-Robots-Tag: index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
}

// Dynamic branding and site configuration
$siteName = get_site_name();
$siteTagline = get_setting('site_tagline', defined('SITE_TAGLINE') ? SITE_TAGLINE : 'Jobs & Career Guidance Hub');
$siteLogoUrl = get_site_logo_url();
$siteFaviconUrl = get_site_favicon_url();

// Fallback SEO variables if not set by individual page
$pageTitle = isset($pageTitle) ? h($pageTitle) . ' | ' . $siteName : $siteName . ' - ' . $siteTagline;
$metaDescription = isset($metaDescription) ? h(clean_seo_description($metaDescription)) : 'Find the latest government jobs, private tech openings, remote career opportunities, resume templates, and expert interview strategies.';
$metaKeywords = isset($metaKeywords) && !empty($metaKeywords) ? h($metaKeywords) : 'career advice, verified job listings, ATS resume tips, interview preparation, government exams, private tech hiring, employment guides, ' . $siteName;
$metaAuthor = isset($metaAuthor) && !empty($metaAuthor) ? h($metaAuthor) : $siteName . ' Editorial Team';
$metaPublisher = $siteName;
$canonicalUrl = isset($canonicalUrl) ? h($canonicalUrl) : BASE_URL . ($_SERVER['REQUEST_URI'] ?? '/');
$ogImage = isset($ogImage) ? h($ogImage) : BASE_URL . '/assets/images/og-default.jpg';
$ogType = isset($ogType) ? h($ogType) : 'website';

// Get current script for active class
$currentScript = basename($_SERVER['PHP_SELF']);

// Load categories for menu dropdown
$headerCategories = get_all_categories();

// Track real live active visitor
track_active_visitor($pageTitle);
$dynamicHeaderMenu = get_header_menu();

// Fetch recent job vacancies & career updates for the notification bell
$headerNotifications = [];
$unreadNotifCount = 0;
try {
    $hDb = getDBConnection();
    $hNotifStmt = $hDb->query("
        SELECT a.id, a.title, a.slug, a.is_job_posting, a.job_company, a.published_at,
               c.name as category_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)
        ORDER BY a.published_at DESC
        LIMIT 5
    ");
    $headerNotifications = $hNotifStmt->fetchAll();

    // Check read notifications from cookie
    $rawReadCookie = $_COOKIE['site_read_notifications'] ?? '';
    $readNotifIds = [];
    if (!empty($rawReadCookie)) {
        $readNotifIds = array_filter(array_map('intval', explode(',', $rawReadCookie)));
    }

    foreach ($headerNotifications as &$hnItem) {
        $hnItem['is_read'] = in_array((int)$hnItem['id'], $readNotifIds, true);
        if (!$hnItem['is_read']) {
            $unreadNotifCount++;
        }
    }
    unset($hnItem);
} catch (Exception $e) {
    $headerNotifications = [];
    $unreadNotifCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XH87VZP3DS"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        
        gtag('js', new Date());

        gtag('config', 'G-XH87VZP3DS');
    </script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <!-- monetag -->
    <meta name="monetag" content="83343c17f22bb13eb87261dd7ce1dfdb">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $metaDescription ?>">
    <meta name="keywords" content="<?= $metaKeywords ?>">
    <meta name="author" content="<?= $metaAuthor ?>">
    <meta name="publisher" content="<?= $metaPublisher ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <link rel="publisher" href="<?= BASE_URL ?>/">
    <link rel="author" href="<?= BASE_URL ?>/about.php">

    <!-- Website Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="<?= BASE_URL ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>/favicon.svg">

    <!-- Open Graph / Social Sharing -->
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $metaDescription ?>">
    <meta property="og:type" content="<?= $ogType ?>">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:site_name" content="<?= h($siteName) ?>">
    <meta property="article:publisher" content="<?= BASE_URL ?>/">
    <?php if (!empty($metaAuthor)): ?>
        <meta property="article:author" content="<?= $metaAuthor ?>">
    <?php endif; ?>
    <?php if (!empty($articleSection)): ?>
        <meta property="article:section" content="<?= h($articleSection) ?>">
    <?php endif; ?>
    <?php if (!empty($articlePublishedTime)): ?>
        <meta property="article:published_time" content="<?= h($articlePublishedTime) ?>">
    <?php endif; ?>
    <?php if (!empty($articleModifiedTime)): ?>
        <meta property="article:modified_time" content="<?= h($articleModifiedTime) ?>">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $pageTitle ?>">
    <meta name="twitter:description" content="<?= $metaDescription ?>">
    <meta name="twitter:image" content="<?= $ogImage ?>">
    <meta name="twitter:creator" content="@<?= h($siteName) ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">

    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>

    <?php if (isset($extraHead)) echo $extraHead; ?>
    <?php if (function_exists('render_monetization_head_scripts')) render_monetization_head_scripts(); ?>
</head>

<body>
    <!-- Skip to Content for Accessibility -->
    <a href="#main-content" class="skip-to-content">Skip to main content</a>

    <!-- Main Header & Navigation -->
    <?php if (is_section_visible('header')): ?>
        <header class="site-header" id="site-header">
            <div class="header-inner">
                <!-- Brand Logo -->
                <a href="<?= BASE_URL ?>/" class="pine-brand-title" aria-label="<?= h($siteName) ?> Homepage" style="display:inline-flex; align-items:center; text-decoration:none;">
                    <?php if (!empty($siteLogoUrl)): ?>
                        <img src="<?= h($siteLogoUrl) ?>" alt="<?= h($siteName) ?>" class="site-header-logo" style="max-height:40px; width:auto; object-fit:contain; display:block;">
                    <?php else: ?>
                        <?= format_brand_name($siteName) ?>
                    <?php endif; ?>
                </a>

                <!-- Primary Navigation Links (Center) -->
                <nav class="pine-nav" id="main-navigation" aria-label="Main Navigation">
                    <?php if (!empty($dynamicHeaderMenu)): ?>
                        <?php foreach ($dynamicHeaderMenu as $hItem): ?>
                            <?php
                            if (empty($hItem['enabled'])) continue;
                            $hUrl = $hItem['url'] ?? '/';
                            if (strpos($hUrl, 'http') !== 0 && strpos($hUrl, '/') !== 0) {
                                $hUrl = '/' . $hUrl;
                            }
                            $fullHUrl = (strpos($hUrl, 'http') === 0) ? $hUrl : rtrim(BASE_URL, '/') . $hUrl;
                            $isActive = false;
                            $reqUri = $_SERVER['REQUEST_URI'] ?? '';
                            if ($hUrl === '/' && ($currentScript === 'index.php' && ($reqUri === '/' || $reqUri === BASE_URL . '/' || substr($reqUri, -1) === '/'))) {
                                $isActive = true;
                            } elseif ($hUrl === '/vlogs' && (strpos($reqUri, '/vlogs') !== false || isset($_GET['vlogs']) || isset($_GET['video']))) {
                                $isActive = true;
                            } elseif ($hUrl === '/trending' && (strpos($reqUri, '/trending') !== false || isset($_GET['trending']))) {
                                $isActive = true;
                            } elseif ($hUrl === '/articles' && (strpos($reqUri, '/articles') !== false && !isset($_GET['vlogs']) && !isset($_GET['video']) && !isset($_GET['trending']))) {
                                $isActive = true;
                            } elseif ($hUrl !== '/' && strpos($reqUri, $hUrl) !== false) {
                                $isActive = true;
                            }
                            ?>
                            <a href="<?= h($fullHUrl) ?>" class="pine-nav-link <?= $isActive ? 'active' : '' ?>">
                                <?= h($hItem['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/" class="pine-nav-link <?= ($currentScript == 'index.php') ? 'active' : '' ?>">Home</a>
                        <a href="<?= BASE_URL ?>/vlogs" class="pine-nav-link <?= (strpos($_SERVER['REQUEST_URI'] ?? '', 'vlogs') !== false || isset($_GET['vlogs']) || isset($_GET['video'])) ? 'active' : '' ?>">Vlogs</a>
                        <a href="<?= BASE_URL ?>/articles" class="pine-nav-link <?= ($currentScript == 'articles.php' && empty($_GET['trending']) && empty($_GET['vlogs']) && empty($_GET['video'])) ? 'active' : '' ?>">Jobs & Articles</a>
                        <a href="<?= BASE_URL ?>/trending" class="pine-nav-link <?= (!empty($_GET['trending'])) ? 'active' : '' ?>">Trending</a>
                    <?php endif; ?>

                    <!-- Topics Dropdown Menu -->
                    <div class="pine-dropdown-group">
                        <button type="button" class="pine-nav-link pine-dropdown-btn" id="topicsMenuBtn" aria-expanded="false">
                            Categories <span class="dropdown-arrow">▾</span>
                        </button>
                        <div class="pine-dropdown-card" id="topicsMenuDropdown">
                            <?php foreach ($headerCategories as $cat): ?>
                                <a href="<?= BASE_URL ?>/category/<?= h($cat['slug']) ?>" class="pine-dropdown-item">
                                    <?= h($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                            <div class="pine-dropdown-divider"></div>
                            <a href="<?= BASE_URL ?>/categories" class="pine-dropdown-item view-all">
                                All Categories &rarr;
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- Search Form on Right & Mobile Controls -->
                <div class="pine-header-right">
                    <form action="<?= BASE_URL ?>/search" method="GET" class="pine-search-form" role="search">
                        <label for="q-header" class="sr-only">Search</label>
                        <input id="q-header" type="search" name="q" maxlength="100" value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>" placeholder="Search jobs, guides, exams…" autocomplete="off" class="pine-search-input" oninput="this.value = this.value.replace(/[<>]/g, '')">
                    </form>

                    <!-- Mobile Search Toggle -->
                    <button type="button" class="pine-icon-btn md-hidden" id="mobileSearchBtn" aria-label="Search">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </button>

                    <!-- Notification Bell Icon & Interactive Dropdown -->
                    <div class="site-bell-group" id="siteBellGroup">
                        <button type="button" class="site-bell-btn" id="siteBellBtn" aria-label="Instant Notifications" aria-expanded="false" title="Instant Job & Career Notifications">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span class="site-bell-badge" id="siteBellBadge" style="<?= $unreadNotifCount <= 0 ? 'display:none;' : '' ?>"><?= $unreadNotifCount ?></span>
                        </button>

                        <!-- Notifications Dropdown Popup -->
                        <div class="site-notif-dropdown" id="siteNotifDropdown" role="dialog" aria-modal="false" aria-label="Job & Career Notifications">
                            <div class="site-notif-header">
                                <div class="site-notif-header-title">
                                    <span class="site-notif-title">Notifications</span>
                                    <span class="site-notif-badge-pill <?= $unreadNotifCount <= 0 ? 'zero' : '' ?>" id="siteNotifBadgePill">
                                        <?= $unreadNotifCount > 0 ? ($unreadNotifCount . ' New') : 'All Caught Up' ?>
                                    </span>
                                </div>
                                <div class="site-notif-header-actions">
                                    <button type="button" class="site-notif-mark-all" id="siteNotifMarkAllBtn" title="Mark all notifications as read" <?= $unreadNotifCount <= 0 ? 'style="display:none;"' : '' ?>>
                                        Mark all read
                                    </button>
                                    <button type="button" class="site-notif-close-btn" id="siteNotifCloseBtn" aria-label="Close notification panel">&times;</button>
                                </div>
                            </div>

                            <!-- Instant Push Notification Alert Box -->
                            <div class="site-notif-push-card">
                                <div class="site-notif-push-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    </svg>
                                </div>
                                <div class="site-notif-push-content">
                                    <div class="site-notif-push-heading">Instant Hiring Alerts</div>
                                    <div class="site-notif-push-sub">Get live exam and job updates on your device.</div>
                                </div>
                                <button type="button" class="site-notif-enable-btn" id="siteNotifPushBtn" onclick="requestSitePushNotifications(this)">
                                    Turn On
                                </button>
                            </div>

                            <!-- Notifications List -->
                            <div class="site-notif-body">
                                <?php if (!empty($headerNotifications)): ?>
                                    <div class="site-notif-list" id="siteNotifList">
                                        <?php foreach ($headerNotifications as $n): ?>
                                            <a href="<?= get_article_url($n['slug']) ?>"
                                                class="site-notif-item <?= !empty($n['is_read']) ? 'is-read' : 'is-unread' ?>"
                                                data-id="<?= (int)$n['id'] ?>">
                                                <div class="site-notif-item-icon <?= $n['is_job_posting'] ? 'job-icon' : 'guide-icon' ?>">
                                                    <?php if ($n['is_job_posting']): ?>
                                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <rect x="2" y="7" width="20" height="14" rx="2" />
                                                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" />
                                                        </svg>
                                                    <?php else: ?>
                                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                                        </svg>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="site-notif-item-content">
                                                    <div class="site-notif-item-meta">
                                                        <span class="site-notif-type-tag <?= $n['is_job_posting'] ? 'job' : 'guide' ?>">
                                                            <?= $n['is_job_posting'] ? 'Job Vacancy' : h($n['category_name'] ?? 'Career Guide') ?>
                                                        </span>
                                                        <span class="site-notif-item-date"><?= format_date($n['published_at']) ?></span>
                                                    </div>
                                                    <div class="site-notif-item-title"><?= h($n['title']) ?></div>
                                                </div>
                                                <span class="site-notif-unread-dot" title="Unread notification" <?= !empty($n['is_read']) ? 'style="display:none;"' : '' ?>></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="site-notif-empty">
                                        <p>No new notifications right now.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Dropdown Footer -->
                            <div class="site-notif-footer">
                                <a href="<?= BASE_URL ?>/articles.php" class="site-notif-view-all">
                                    View all jobs & career updates &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Menu Toggle -->
                    <button type="button" class="pine-icon-btn lg-hidden" id="mobileMenuToggle" aria-expanded="false" aria-controls="mobileNavMenu" aria-label="Toggle navigation menu">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Expandable Search Bar -->
            <div class="mobile-search-bar" id="mobileSearchBar" hidden>
                <div class="site-container">
                    <form action="<?= BASE_URL ?>/search.php" method="GET" role="search">
                        <input type="search" name="q" maxlength="100" placeholder="Search jobs, guides, articles…" autocomplete="off" class="mobile-search-input" value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>" oninput="this.value = this.value.replace(/[<>]/g, '')">
                    </form>
                </div>
            </div>

            <!-- Mobile Navigation Menu -->
            <nav class="mobile-nav-panel" id="mobileNavMenu" hidden aria-label="Mobile Navigation">
                <div class="site-container mobile-nav-inner">
                    <a href="<?= BASE_URL ?>/" class="mobile-nav-link <?= ($currentScript == 'index.php') ? 'active' : '' ?>">Home</a>
                    <a href="<?= BASE_URL ?>/vlogs" class="mobile-nav-link <?= (!empty($_GET['vlogs']) || !empty($_GET['video'])) ? 'active' : '' ?>">Vlogs</a>
                    <a href="<?= BASE_URL ?>/articles" class="mobile-nav-link <?= ($currentScript == 'articles.php' && empty($_GET['trending']) && empty($_GET['vlogs']) && empty($_GET['video'])) ? 'active' : '' ?>">Articles</a>
                    <a href="<?= BASE_URL ?>/trending" class="mobile-nav-link <?= (!empty($_GET['trending'])) ? 'active' : '' ?>">Trending</a>

                    <div class="mobile-topics-section">
                        <span class="mobile-topics-title">Topics</span>
                        <div class="mobile-chips-grid">
                            <?php foreach ($headerCategories as $cat): ?>
                                <a href="<?= get_category_url($cat['slug']) ?>" class="mobile-topic-chip">
                                    <?= h($cat['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </nav>
        </header>
    <?php endif; ?>

    <!-- Monetization Slot: Header Ad (Header, after navigation) -->
    <?php if (function_exists('render_monetization_ad')): ?>
        <div class="site-container" style="padding-top: 0.5rem;">
            <?php render_monetization_ad('header_ad'); ?>
        </div>
    <?php endif; ?>

    <!-- Global Flash Notification Container -->
    <div class="site-container">
        <?= display_flash_messages(); ?>
    </div>

    <!-- Main Content Area Starts -->
    <main id="main-content">