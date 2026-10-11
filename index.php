<?php
/**
 * Public Website - Home Page
 * CareerNexaa - Modern Jobs & Career Guidance Hub
 * Dynamically driven by Admin "Home Sections & Menus" configuration:
 * 1. HERO (Featured stories & live radar)
 * 2. LATEST (Latest jobs & articles feed)
 * 3. CATEGORIES (Explore topics & tracks)
 * 4. POPULAR (Ranked most popular reads)
 * 5. ARTICLES (Long-form career guides & editorials)
 * 6. RECOMMENDED (Editor's hand-picked selections)
 * 7. NEWSLETTER (Subscriber capture & updates)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ads-placeholder.php';

$pageTitle = "Jobs & Career Guidance Hub | Verified Openings, Resume Tips & Exam Guides";
$metaDescription = "Discover verified government jobs, tech careers, remote positions, ATS resume guides, and interview frameworks on CareerNexaa.";
$metaKeywords = "jobs 2026, career guidance, government jobs, remote tech jobs, ATS resume format, interview questions, job portal, CareerNexaa";

$db = getDBConnection();

// Fetch Section Configurations from "Home Sections & Menus"
$heroSec        = get_home_section('hero', 'Featured', 'Hand-picked stories worth your time', 5);
$latestSec      = get_home_section('latest', 'Latest Jobs & Posts', 'Fresh opportunities & publications', 8);
$categoriesSec  = get_home_section('categories', 'Explore Categories', 'Browse by topic', 8);
$popularSec     = get_home_section('popular', 'Most Popular', 'All-time favourites', 6);
$articlesSec    = get_home_section('articles', 'Latest Articles', 'Long-form reads', 6);
$recommendedSec = get_home_section('recommended', 'Recommended For You', 'Editor picks', 6);
$newsletterSec  = get_home_section('newsletter', 'Stay in the loop', 'Get new career updates in your inbox', 0);

// 1. DATA: HERO Section
$heroArticles = [];
if (!empty($heroSec['is_enabled'])) {
    $heroLimit = max(1, (int)$heroSec['item_limit']);
    $heroWhere = "a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)";
    $heroParams = [];
    if (!empty($heroSec['category_id'])) {
        $heroWhere .= " AND a.category_id = ?";
        $heroParams[] = (int)$heroSec['category_id'];
    }
    $heroStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE {$heroWhere}
        ORDER BY a.is_featured DESC, a.views DESC, a.published_at DESC
        LIMIT {$heroLimit}
    ");
    $heroStmt->execute($heroParams);
    $heroArticles = $heroStmt->fetchAll();
}
$spotlightArticle = !empty($heroArticles) ? $heroArticles[0] : null;
$spotlightId = $spotlightArticle ? (int)$spotlightArticle['id'] : 0;
$heroCompanionArticles = !empty($heroArticles) ? array_slice($heroArticles, 1) : [];

// 2. DATA: LATEST Section
$latestArticles = [];
if (!empty($latestSec['is_enabled'])) {
    $latestLimit = max(1, (int)$latestSec['item_limit']);
    $latestWhere = "a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)";
    $latestParams = [];
    if (!empty($latestSec['category_id'])) {
        $latestWhere .= " AND a.category_id = ?";
        $latestParams[] = (int)$latestSec['category_id'];
    }
    $latestStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE {$latestWhere}
        ORDER BY a.published_at DESC
        LIMIT {$latestLimit}
    ");
    $latestStmt->execute($latestParams);
    $latestArticles = $latestStmt->fetchAll();
}

// 3. DATA: CATEGORIES Section
$categoriesList = [];
if (!empty($categoriesSec['is_enabled'])) {
    $catLimit = max(1, (int)$categoriesSec['item_limit']);
    $catStmt = $db->prepare("
        SELECT c.*, COUNT(CASE WHEN a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL) THEN 1 END) as total_articles
        FROM categories c
        LEFT JOIN articles a ON c.id = a.category_id
        GROUP BY c.id
        ORDER BY total_articles DESC, c.name ASC
        LIMIT {$catLimit}
    ");
    $catStmt->execute();
    $categoriesList = $catStmt->fetchAll();
}

// 4. DATA: POPULAR Section
$popularArticles = [];
if (!empty($popularSec['is_enabled'])) {
    $popularLimit = max(1, (int)$popularSec['item_limit']);
    $popWhere = "a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)";
    $popParams = [];
    if (!empty($popularSec['category_id'])) {
        $popWhere .= " AND a.category_id = ?";
        $popParams[] = (int)$popularSec['category_id'];
    }
    $popStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE {$popWhere}
        ORDER BY a.views DESC, a.likes DESC, a.published_at DESC
        LIMIT {$popularLimit}
    ");
    $popStmt->execute($popParams);
    $popularArticles = $popStmt->fetchAll();
}

// 5. DATA: ARTICLES Section (Long-form Editorial Guides)
$longFormArticles = [];
if (!empty($articlesSec['is_enabled'])) {
    $artLimit = max(1, (int)$articlesSec['item_limit']);
    $artWhere = "a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL) AND a.is_job_posting = 0";
    $artParams = [];
    if (!empty($articlesSec['category_id'])) {
        $artWhere .= " AND a.category_id = ?";
        $artParams[] = (int)$articlesSec['category_id'];
    }
    $artStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE {$artWhere}
        ORDER BY a.published_at DESC
        LIMIT {$artLimit}
    ");
    $artStmt->execute($artParams);
    $longFormArticles = $artStmt->fetchAll();
}

// 6. DATA: RECOMMENDED Section
$recommendedArticles = [];
if (!empty($recommendedSec['is_enabled'])) {
    $recLimit = max(1, (int)$recommendedSec['item_limit']);
    $recWhere = "a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)";
    $recParams = [];
    if (!empty($recommendedSec['category_id'])) {
        $recWhere .= " AND a.category_id = ?";
        $recParams[] = (int)$recommendedSec['category_id'];
    }
    $recStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE {$recWhere}
        ORDER BY a.is_recommended DESC, a.views DESC, a.published_at DESC
        LIMIT {$recLimit}
    ");
    $recStmt->execute($recParams);
    $recommendedArticles = $recStmt->fetchAll();
}

include __DIR__ . '/includes/header.php';
?>

<!-- ================= Interactive Top Search Banner ================= -->
<div class="cp-search-strip" style="background:#ffffff; border-bottom:1px solid #f1f5f9; padding:1.25rem 0;">
    <div class="site-container">
        <form action="<?= BASE_URL ?>/search.php" method="GET" style="display:flex; gap:0.75rem; align-items:center; max-width:760px; margin:0 auto; flex-wrap:wrap;">
            <div style="position:relative; flex:1; min-width:240px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" style="position:absolute; left:1rem; top:50%; transform:translateY(-50%); pointer-events:none;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="q" placeholder="Search career guides, remote jobs, ATS resume tips..." style="width:100%; padding:0.7rem 1rem 0.7rem 2.8rem; border:1px solid #cbd5e1; border-radius:9999px; font-size:0.95rem; outline:none; transition:border 0.2s;" onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='#cbd5e1'">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary" style="padding:0.7rem 1.6rem; font-weight:700; border-radius:9999px; background:#059669; color:#fff; border:none; cursor:pointer;">
                Search CareerNexaa
            </button>
        </form>
    </div>
</div>

<!-- ================= 1. SECTION: HERO (Featured Stories & Spotlight) ================= -->
<?php if (!empty($heroSec['is_enabled']) && !empty($spotlightArticle)): ?>
<section class="dynamic-home-section" id="section-hero">
    <div class="site-container">
        <div class="home-section-head">
            <div class="home-section-head-left">
                <span class="home-section-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <?= h($heroSec['title'] ?: 'Featured') ?>
                </span>
                <h1 class="home-section-title"><?= h($heroSec['title'] ?: 'Featured') ?></h1>
                <?php if (!empty($heroSec['subtitle'])): ?>
                    <p class="home-section-sub"><?= h($heroSec['subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/articles.php?featured=1" class="home-section-action-link">
                    Explore Spotlight &rarr;
                </a>
            </div>
        </div>

        <div class="home-hero-split">
            <!-- Left: Main Big Spotlight Guide -->
            <?php 
                $spotThumb = get_article_thumbnail_url($spotlightArticle);
                $spotRawTags = !empty($spotlightArticle['tags']) ? $spotlightArticle['tags'] : ($spotlightArticle['keywords'] ?? '');
                $spotTags = array_filter(array_map('trim', explode(',', (string)$spotRawTags)));
            ?>
            <article class="hero-spotlight-box">
                <div class="hero-spotlight-media">
                    <img src="<?= $spotThumb ?>" alt="<?= h($spotlightArticle['title']) ?>" loading="eager">
                    <div class="hero-badge-overlay">
                        <span class="hero-featured-tag">★ Featured</span>
                        <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($spotlightArticle['category_slug']) ?>" class="hero-cat-tag">
                            <?= h($spotlightArticle['category_name']) ?>
                        </a>
                    </div>
                </div>

                <div class="hero-spotlight-body">
                    <h2 class="hero-spotlight-title">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($spotlightArticle['slug']) ?>">
                            <?= h($spotlightArticle['title']) ?>
                        </a>
                    </h2>

                    <?php if (!empty($spotTags)): ?>
                    <div class="card-tags-chip-row">
                        <?php foreach (array_slice($spotTags, 0, 4) as $tg): ?>
                            <span class="card-tag-chip">#<?= h($tg) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <p class="hero-spotlight-excerpt">
                        <?= h($spotlightArticle['excerpt']) ?>
                    </p>

                    <div class="hero-spotlight-meta">
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <span class="cp-author-avatar"><?= strtoupper(substr($spotlightArticle['author_name'], 0, 1)) ?></span>
                            <div>
                                <span style="font-weight:700; color:#0f172a; font-size:0.85rem;"><?= h($spotlightArticle['author_name']) ?></span>
                                <span style="font-size:0.75rem; color:#64748b; display:block;"><?= format_date($spotlightArticle['published_at']) ?></span>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:1rem;">
                            <span style="font-size:0.8rem; color:#64748b; display:inline-flex; align-items:center; gap:0.25rem;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <?= (int)$spotlightArticle['views'] ?> views
                            </span>
                            <a href="<?= BASE_URL ?>/article.php?slug=<?= h($spotlightArticle['slug']) ?>" class="read-guide-btn">
                                Read Article &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Right: Companion Hero Cards List -->
            <div class="hero-side-card">
                <div class="hero-side-header">
                    <span class="hero-side-title">
                        <span class="live-pulse-dot"></span>
                        Top Stories
                    </span>
                    <a href="<?= BASE_URL ?>/articles.php" style="color:#059669; font-size:0.8rem; font-weight:700; text-decoration:none;">View All &rarr;</a>
                </div>

                <div style="display:flex; flex-direction:column; gap:0.5rem; justify-content:space-around; flex:1;">
                    <?php if (!empty($heroCompanionArticles)): ?>
                        <?php foreach ($heroCompanionArticles as $cArt): ?>
                        <?php 
                            $cThumb = get_article_thumbnail_url($cArt);
                            $cRawTags = !empty($cArt['tags']) ? $cArt['tags'] : ($cArt['keywords'] ?? '');
                            $cTags = array_filter(array_map('trim', explode(',', (string)$cRawTags)));
                        ?>
                        <div class="hero-side-item">
                            <a href="<?= BASE_URL ?>/article.php?slug=<?= h($cArt['slug']) ?>" class="hero-side-thumb">
                                <img src="<?= $cThumb ?>" alt="<?= h($cArt['title']) ?>" loading="lazy">
                            </a>
                            <div class="hero-side-item-info">
                                <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($cArt['category_slug']) ?>" style="color:#0284c7; font-size:0.75rem; font-weight:700; text-decoration:none;">
                                    <?= h($cArt['category_name']) ?>
                                </a>
                                <h4 class="hero-side-item-title">
                                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($cArt['slug']) ?>">
                                        <?= h($cArt['title']) ?>
                                    </a>
                                </h4>
                                <div style="font-size:0.72rem; color:#64748b; display:flex; align-items:center; gap:0.5rem;">
                                    <span><?= format_date($cArt['published_at']) ?></span>
                                    <span>&bull;</span>
                                    <span><?= (int)$cArt['views'] ?> views</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color:#94a3b8; font-size:0.85rem; text-align:center; padding:2rem 0;">
                            More featured stories will appear here.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= 2. SECTION: LATEST (Latest Jobs & Vlogs) ================= -->
<?php if (!empty($latestSec['is_enabled']) && !empty($latestArticles)): ?>
<section class="dynamic-home-section dynamic-home-section-alt" id="section-latest">
    <div class="site-container">
        <div class="home-section-head">
            <div class="home-section-head-left">
                <span class="home-section-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    <?= h($latestSec['title'] ?: 'Latest') ?>
                </span>
                <h2 class="home-section-title"><?= h($latestSec['title'] ?: 'Latest Jobs & Articles') ?></h2>
                <?php if (!empty($latestSec['subtitle'])): ?>
                    <p class="home-section-sub"><?= h($latestSec['subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/articles.php" class="home-section-action-link">
                    View All &rarr;
                </a>
            </div>
        </div>

        <div class="cp-card-grid-3">
            <?php foreach ($latestArticles as $art): ?>
            <?php 
                $aThumb = get_article_thumbnail_url($art);
                $aRawTags = !empty($art['tags']) ? $art['tags'] : ($art['keywords'] ?? '');
                $aTags = array_filter(array_map('trim', explode(',', (string)$aRawTags)));
            ?>
            <article class="cp-article-card">
                <div class="cp-card-thumb-wrap">
                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>">
                        <img src="<?= $aThumb ?>" alt="<?= h($art['title']) ?>" class="cp-card-thumb" loading="lazy">
                    </a>
                    <span class="cp-badge-floating <?= $art['is_job_posting'] ? 'job-badge' : '' ?>">
                        <?= $art['is_job_posting'] ? 'Job Vacancy' : h($art['category_name']) ?>
                    </span>
                </div>

                <div class="cp-card-body">
                    <div class="cp-card-meta-top">
                        <span class="cp-card-cat-name"><?= h($art['category_name']) ?></span>
                        <span>&bull;</span>
                        <span><?= format_date($art['published_at']) ?></span>
                    </div>

                    <h3 class="cp-card-title">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>">
                            <?= h($art['title']) ?>
                        </a>
                    </h3>

                    <?php if (!empty($aTags)): ?>
                    <div class="card-tags-chip-row">
                        <?php foreach (array_slice($aTags, 0, 3) as $tg): ?>
                            <span class="card-tag-chip">#<?= h($tg) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <p class="cp-card-excerpt">
                        <?= h($art['excerpt']) ?>
                    </p>

                    <?php if ($art['is_job_posting'] && !empty($art['job_salary'])): ?>
                    <div class="cp-job-chips-box">
                        <span style="font-weight:700; color:#047857;"><?= h($art['job_salary']) ?></span>
                        <?php if (!empty($art['job_location'])): ?>
                            <span>&bull; <?= h($art['job_location']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="cp-card-footer">
                        <div class="cp-author-chip">
                            <span class="cp-author-avatar"><?= strtoupper(substr($art['author_name'], 0, 1)) ?></span>
                            <span><?= h($art['author_name']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>" class="cp-card-read-link">
                            <?= $art['is_job_posting'] ? 'View Job &rarr;' : 'Read Guide &rarr;' ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= 3. SECTION: CATEGORIES (Explore Categories) ================= -->
<?php if (!empty($categoriesSec['is_enabled']) && !empty($categoriesList)): ?>
<section class="dynamic-home-section" id="section-categories">
    <div class="site-container">
        <div class="home-section-head">
            <div class="home-section-head-left">
                <span class="home-section-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    <?= h($categoriesSec['title'] ?: 'Categories') ?>
                </span>
                <h2 class="home-section-title"><?= h($categoriesSec['title'] ?: 'Explore Categories') ?></h2>
                <?php if (!empty($categoriesSec['subtitle'])): ?>
                    <p class="home-section-sub"><?= h($categoriesSec['subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/categories.php" class="home-section-action-link">
                    All Categories &rarr;
                </a>
            </div>
        </div>

        <div class="sector-tracks-grid">
            <?php foreach ($categoriesList as $cat): ?>
                <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($cat['slug']) ?>" class="sector-card">
                    <div class="sector-icon-box">
                        <?php if (strpos($cat['slug'], 'govt') !== false || strpos($cat['slug'], 'government') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 10v11M9 10v11M15 10v11M19 10v11M12 2L2 7h20L12 2z"></path></svg>
                        <?php elseif (strpos($cat['slug'], 'tech') !== false || strpos($cat['slug'], 'private') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        <?php elseif (strpos($cat['slug'], 'remote') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <?php elseif (strpos($cat['slug'], 'resume') !== false || strpos($cat['slug'], 'interview') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <?php elseif (strpos($cat['slug'], 'trad') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        <?php else: ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                        <?php endif; ?>
                    </div>
                    <span class="sector-card-name"><?= h($cat['name']) ?></span>
                    <span class="sector-card-count"><?= (int)$cat['total_articles'] ?> Guides &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= 4. SECTION: POPULAR (Ranked All-Time Favourites) ================= -->
<?php if (!empty($popularSec['is_enabled']) && !empty($popularArticles)): ?>
<section class="dynamic-home-section dynamic-home-section-alt" id="section-popular">
    <div class="site-container">
        <div class="home-section-head">
            <div class="home-section-head-left">
                <span class="home-section-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"></path></svg>
                    <?= h($popularSec['title'] ?: 'Popular') ?>
                </span>
                <h2 class="home-section-title"><?= h($popularSec['title'] ?: 'Most Popular Guides') ?></h2>
                <?php if (!empty($popularSec['subtitle'])): ?>
                    <p class="home-section-sub"><?= h($popularSec['subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/articles.php?trending=1" class="home-section-action-link">
                    Trending Radar &rarr;
                </a>
            </div>
        </div>

        <div class="home-ranked-grid">
            <?php $rankIndex = 1; ?>
            <?php foreach ($popularArticles as $pop): ?>
            <?php 
                $pThumb = get_article_thumbnail_url($pop);
                $pRawTags = !empty($pop['tags']) ? $pop['tags'] : ($pop['keywords'] ?? '');
                $pTags = array_filter(array_map('trim', explode(',', (string)$pRawTags)));
            ?>
            <article class="ranked-story-card">
                <div class="ranked-number-badge">
                    #<?= $rankIndex++ ?>
                </div>

                <div style="flex:1; min-width:0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                        <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($pop['category_slug']) ?>" style="color:#059669; font-weight:700; font-size:0.75rem; text-decoration:none;">
                            <?= h($pop['category_name']) ?>
                        </a>
                        <span style="font-size:0.72rem; color:#64748b; font-weight:600;">
                            <?= (int)$pop['views'] ?> views
                        </span>
                    </div>

                    <h3 style="font-family:var(--font-heading); font-size:1rem; font-weight:700; line-height:1.35; margin:0 0 0.35rem 0;">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($pop['slug']) ?>" style="color:#0f172a; text-decoration:none;">
                            <?= h($pop['title']) ?>
                        </a>
                    </h3>

                    <?php if (!empty($pTags)): ?>
                    <div class="card-tags-chip-row" style="margin-bottom:0.35rem;">
                        <?php foreach (array_slice($pTags, 0, 2) as $tg): ?>
                            <span class="card-tag-chip">#<?= h($tg) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <span style="font-size:0.72rem; color:#64748b;"><?= format_date($pop['published_at']) ?> &bull; <?= h($pop['author_name']) ?></span>
                </div>

                <a href="<?= BASE_URL ?>/article.php?slug=<?= h($pop['slug']) ?>" style="width:72px; height:54px; border-radius:8px; overflow:hidden; flex-shrink:0;">
                    <img src="<?= $pThumb ?>" alt="<?= h($pop['title']) ?>" style="width:100%; height:100%; object-fit:cover;" loading="lazy">
                </a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= 5. SECTION: ARTICLES (Long-form Reads) ================= -->
<?php if (!empty($articlesSec['is_enabled']) && !empty($longFormArticles)): ?>
<section class="dynamic-home-section" id="section-articles">
    <div class="site-container">
        <div class="home-section-head">
            <div class="home-section-head-left">
                <span class="home-section-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                    <?= h($articlesSec['title'] ?: 'Articles') ?>
                </span>
                <h2 class="home-section-title"><?= h($articlesSec['title'] ?: 'Long-form Reads & Editorials') ?></h2>
                <?php if (!empty($articlesSec['subtitle'])): ?>
                    <p class="home-section-sub"><?= h($articlesSec['subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/articles.php" class="home-section-action-link">
                    All Long-reads &rarr;
                </a>
            </div>
        </div>

        <div class="cp-card-grid-3">
            <?php foreach ($longFormArticles as $art): ?>
            <?php 
                $artThumb = get_article_thumbnail_url($art);
                $artRawTags = !empty($art['tags']) ? $art['tags'] : ($art['keywords'] ?? '');
                $artTags = array_filter(array_map('trim', explode(',', (string)$artRawTags)));
            ?>
            <article class="cp-article-card">
                <div class="cp-card-thumb-wrap">
                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>">
                        <img src="<?= $artThumb ?>" alt="<?= h($art['title']) ?>" class="cp-card-thumb" loading="lazy">
                    </a>
                    <span class="cp-badge-floating">
                        <?= h($art['category_name']) ?>
                    </span>
                </div>

                <div class="cp-card-body">
                    <div class="cp-card-meta-top">
                        <span class="cp-card-cat-name"><?= h($art['category_name']) ?></span>
                        <span>&bull;</span>
                        <span><?= format_date($art['published_at']) ?></span>
                    </div>

                    <h3 class="cp-card-title">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>">
                            <?= h($art['title']) ?>
                        </a>
                    </h3>

                    <?php if (!empty($artTags)): ?>
                    <div class="card-tags-chip-row">
                        <?php foreach (array_slice($artTags, 0, 3) as $tg): ?>
                            <span class="card-tag-chip">#<?= h($tg) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <p class="cp-card-excerpt">
                        <?= h($art['excerpt']) ?>
                    </p>

                    <div class="cp-card-footer">
                        <div class="cp-author-chip">
                            <span class="cp-author-avatar"><?= strtoupper(substr($art['author_name'], 0, 1)) ?></span>
                            <span><?= h($art['author_name']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>" class="cp-card-read-link">
                            Read Guide &rarr;
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= 6. SECTION: RECOMMENDED (Editor Picks) ================= -->
<?php if (!empty($recommendedSec['is_enabled']) && !empty($recommendedArticles)): ?>
<section class="dynamic-home-section dynamic-home-section-alt" id="section-recommended">
    <div class="site-container">
        <div class="home-section-head">
            <div class="home-section-head-left">
                <span class="home-section-eyebrow">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    <?= h($recommendedSec['title'] ?: 'Recommended') ?>
                </span>
                <h2 class="home-section-title"><?= h($recommendedSec['title'] ?: 'Recommended For You') ?></h2>
                <?php if (!empty($recommendedSec['subtitle'])): ?>
                    <p class="home-section-sub"><?= h($recommendedSec['subtitle']) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/articles.php" class="home-section-action-link">
                    Browse All &rarr;
                </a>
            </div>
        </div>

        <div class="cp-card-grid-3">
            <?php foreach ($recommendedArticles as $art): ?>
            <?php 
                $rThumb = get_article_thumbnail_url($art);
                $rRawTags = !empty($art['tags']) ? $art['tags'] : ($art['keywords'] ?? '');
                $rTags = array_filter(array_map('trim', explode(',', (string)$rRawTags)));
            ?>
            <article class="cp-article-card" style="border-top:3px solid #059669;">
                <div class="cp-card-thumb-wrap">
                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>">
                        <img src="<?= $rThumb ?>" alt="<?= h($art['title']) ?>" class="cp-card-thumb" loading="lazy">
                    </a>
                    <span class="cp-badge-floating" style="background:linear-gradient(135deg, #059669 0%, #10b981 100%); color:#fff;">
                        ★ Editor's Pick
                    </span>
                </div>

                <div class="cp-card-body">
                    <div class="cp-card-meta-top">
                        <span class="cp-card-cat-name"><?= h($art['category_name']) ?></span>
                        <span>&bull;</span>
                        <span><?= format_date($art['published_at']) ?></span>
                    </div>

                    <h3 class="cp-card-title">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>">
                            <?= h($art['title']) ?>
                        </a>
                    </h3>

                    <?php if (!empty($rTags)): ?>
                    <div class="card-tags-chip-row">
                        <?php foreach (array_slice($rTags, 0, 3) as $tg): ?>
                            <span class="card-tag-chip">#<?= h($tg) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <p class="cp-card-excerpt">
                        <?= h($art['excerpt']) ?>
                    </p>

                    <div class="cp-card-footer">
                        <div class="cp-author-chip">
                            <span class="cp-author-avatar"><?= strtoupper(substr($art['author_name'], 0, 1)) ?></span>
                            <span><?= h($art['author_name']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($art['slug']) ?>" class="cp-card-read-link">
                            Read Guide &rarr;
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= 7. SECTION: NEWSLETTER (Stay in the Loop) ================= -->
<?php if (!empty($newsletterSec['is_enabled'])): ?>
<section class="dynamic-home-section" id="section-newsletter">
    <div class="site-container">
        <div class="home-newsletter-box">
            <div class="home-newsletter-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
            </div>
            <h2 class="home-newsletter-title"><?= h($newsletterSec['title'] ?: 'Stay in the loop') ?></h2>
            <p class="home-newsletter-sub"><?= h($newsletterSec['subtitle'] ?: 'Get new career updates, hiring alerts, and interview strategies delivered to your inbox.') ?></p>

            <form class="home-newsletter-form" onsubmit="event.preventDefault(); alert('Thank you for subscribing to CareerNexaa! You are now in the loop.'); this.reset();">
                <input type="email" class="home-newsletter-input" placeholder="Enter your work email address..." required>
                <button type="submit" class="home-newsletter-btn">
                    Subscribe Free
                </button>
            </form>
            <p style="font-size:0.75rem; color:#a7f3d0; margin-top:1rem; opacity:0.8;">🔒 Zero spam. Unsubscribe at any time with one click.</p>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
