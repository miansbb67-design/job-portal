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

// Fallback SEO variables if not set by individual page
$pageTitle = isset($pageTitle) ? h($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' - ' . SITE_TAGLINE;
$metaDescription = isset($metaDescription) ? h($metaDescription) : 'Find the latest government jobs, private tech openings, remote career opportunities, resume templates, and expert interview strategies.';
$metaKeywords = isset($metaKeywords) && !empty($metaKeywords) ? h($metaKeywords) : 'career advice, verified job listings, ATS resume tips, interview preparation, government exams, private tech hiring, employment guides, ' . SITE_NAME;
$metaAuthor = isset($metaAuthor) && !empty($metaAuthor) ? h($metaAuthor) : SITE_NAME . ' Editorial Team';
$metaPublisher = SITE_NAME;
$canonicalUrl = isset($canonicalUrl) ? h($canonicalUrl) : BASE_URL . ($_SERVER['REQUEST_URI'] ?? '/');
$ogImage = isset($ogImage) ? h($ogImage) : BASE_URL . '/assets/images/og-default.jpg';
$ogType = isset($ogType) ? h($ogType) : 'website';

// Get current script for active class
$currentScript = basename($_SERVER['PHP_SELF']);

// Load categories for menu dropdown
$headerCategories = get_all_categories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $metaDescription ?>">
    <meta name="keywords" content="<?= $metaKeywords ?>">
    <meta name="author" content="<?= $metaAuthor ?>">
    <meta name="publisher" content="<?= $metaPublisher ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <link rel="publisher" href="<?= BASE_URL ?>/">
    <link rel="author" href="<?= BASE_URL ?>/about.php">

    <!-- Open Graph / Social Sharing -->
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $metaDescription ?>">
    <meta property="og:type" content="<?= $ogType ?>">
    <meta property="og:url" content="<?= $canonicalUrl ?>">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
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
    <meta name="twitter:creator" content="@<?= SITE_NAME ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
    <!-- Skip to Content for Accessibility -->
    <a href="#main-content" class="skip-to-content">Skip to main content</a>

    <!-- Main Header & Navigation -->
    <header class="site-header" id="site-header">
        <div class="header-inner">
            <!-- Brand Logo (CareerPulse) -->
            <a href="<?= BASE_URL ?>/" class="pine-brand-title" aria-label="<?= SITE_NAME ?> Homepage">
                Career<span class="brand-green">Pulse</span>
            </a>

            <!-- Primary Navigation Links (Center) -->
            <nav class="pine-nav" id="main-navigation" aria-label="Main Navigation">
                <a href="<?= BASE_URL ?>/" class="pine-nav-link <?= ($currentScript == 'index.php') ? 'active' : '' ?>">Home</a>
                <a href="<?= BASE_URL ?>/articles.php" class="pine-nav-link <?= ($currentScript == 'articles.php' && empty($_GET['trending'])) ? 'active' : '' ?>">Jobs & Articles</a>
                <a href="<?= BASE_URL ?>/articles.php?trending=1" class="pine-nav-link <?= (!empty($_GET['trending'])) ? 'active' : '' ?>">Trending</a>
                
                <!-- Topics Dropdown Menu -->
                <div class="pine-dropdown-group">
                    <button type="button" class="pine-nav-link pine-dropdown-btn" id="topicsMenuBtn" aria-expanded="false">
                        Categories <span class="dropdown-arrow">▾</span>
                    </button>
                    <div class="pine-dropdown-card" id="topicsMenuDropdown">
                        <?php foreach ($headerCategories as $cat): ?>
                            <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($cat['slug']) ?>" class="pine-dropdown-item">
                                <?= h($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                        <div class="pine-dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/categories.php" class="pine-dropdown-item view-all">
                            All Categories &rarr;
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Search Form on Right & Mobile Controls -->
            <div class="pine-header-right">
                <form action="<?= BASE_URL ?>/search.php" method="GET" class="pine-search-form" role="search">
                    <label for="q-header" class="sr-only">Search</label>
                    <input id="q-header" type="search" name="q" value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>" placeholder="Search jobs, guides, exams…" autocomplete="off" class="pine-search-input">
                </form>

                <!-- Mobile Search Toggle -->
                <button type="button" class="pine-icon-btn md-hidden" id="mobileSearchBtn" aria-label="Search">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                </button>

                <!-- Mobile Menu Toggle -->
                <button type="button" class="pine-icon-btn lg-hidden" id="mobileMenuToggle" aria-expanded="false" aria-controls="mobileNavMenu" aria-label="Toggle navigation menu">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <!-- Mobile Expandable Search Bar -->
        <div class="mobile-search-bar" id="mobileSearchBar" hidden>
            <div class="site-container">
                <form action="<?= BASE_URL ?>/search.php" method="GET" role="search">
                    <input type="search" name="q" placeholder="Search jobs, guides, articles…" autocomplete="off" class="mobile-search-input" value="<?= isset($_GET['q']) ? h($_GET['q']) : '' ?>">
                </form>
            </div>
        </div>

        <!-- Mobile Navigation Menu -->
        <nav class="mobile-nav-panel" id="mobileNavMenu" hidden aria-label="Mobile Navigation">
            <div class="site-container mobile-nav-inner">
                <a href="<?= BASE_URL ?>/" class="mobile-nav-link <?= ($currentScript == 'index.php') ? 'active' : '' ?>">Home</a>
                <a href="<?= BASE_URL ?>/articles.php" class="mobile-nav-link <?= ($currentScript == 'articles.php' && empty($_GET['trending'])) ? 'active' : '' ?>">Articles</a>
                <a href="<?= BASE_URL ?>/articles.php?trending=1" class="mobile-nav-link <?= (!empty($_GET['trending'])) ? 'active' : '' ?>">Trending</a>
                
                <div class="mobile-topics-section">
                    <span class="mobile-topics-title">Topics</span>
                    <div class="mobile-chips-grid">
                        <?php foreach ($headerCategories as $cat): ?>
                            <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($cat['slug']) ?>" class="mobile-topic-chip">
                                <?= h($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Global Flash Notification Container -->
    <div class="site-container">
        <?= display_flash_messages(); ?>
    </div>

    <!-- Main Content Area Starts -->
    <main id="main-content">
