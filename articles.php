<?php
/**
 * Public Website - Articles & Guides Listing Page
 * Supports pagination, category filtering, and sorting.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ads-placeholder.php';

$db = getDBConnection();

// Filters & Pagination
// Filters & Pagination
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$perPageSetting = (int)get_setting('items_per_page', defined('ARTICLES_PER_PAGE') ? ARTICLES_PER_PAGE : 8);
$perPage = ($perPageSetting >= 8) ? $perPageSetting : (defined('ARTICLES_PER_PAGE') && ARTICLES_PER_PAGE >= 8 ? ARTICLES_PER_PAGE : 8);
$offset = ($currentPage - 1) * $perPage;

$isVlogs = (isset($_GET['vlogs']) && $_GET['vlogs'] == '1') || (isset($_GET['video']) && $_GET['video'] == '1');
$isTrending = isset($_GET['trending']) && $_GET['trending'] == '1';
$isJobs = isset($_GET['jobs']) && $_GET['jobs'] == '1';

$whereClauses = ["a.status = 'published'", "(a.is_trash = 0 OR a.is_trash IS NULL)"];
$params = [];

if ($isVlogs) {
    $whereClauses[] = "(a.video_type IS NOT NULL AND a.video_type != 'none' AND a.video_type != '')";
} elseif ($isJobs) {
    $whereClauses[] = "a.is_job_posting = 1";
}

$activeCategory = null;
if (!empty($categorySlug)) {
    $catStmt = $db->prepare("SELECT * FROM categories WHERE slug = ?");
    $catStmt->execute([$categorySlug]);
    $activeCategory = $catStmt->fetch();
    if ($activeCategory) {
        $whereClauses[] = "a.category_id = ?";
        $params[] = $activeCategory['id'];
    }
}

$whereSql = implode(' AND ', $whereClauses);

// Count Total
$countStmt = $db->prepare("SELECT COUNT(*) FROM articles a WHERE {$whereSql}");
$countStmt->execute($params);
$totalArticles = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);

$orderBy = $isTrending ? "a.views DESC, a.published_at DESC" : "a.published_at DESC";

// Fetch Articles
$sql = "
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE {$whereSql}
    ORDER BY {$orderBy}
    LIMIT {$perPage} OFFSET {$offset}
";
$articlesStmt = $db->prepare($sql);
$articlesStmt->execute($params);
$articles = $articlesStmt->fetchAll();

// Dynamic Titles, Descriptions & SEO
if ($isVlogs) {
    $pageHeading = "Career Vlogs & Video Guides";
    $pageDesc = "Watch verified career vlogs, interview video guides, and multimedia walkthroughs.";
    $pageTitle = "Career Vlogs & Video Guides";
    $metaDescription = "Watch top career vlogs, video blueprints, and technical walkthroughs on CareerNexaa.";
    $canonicalUrl = BASE_URL . '/vlogs';
} elseif ($isTrending) {
    $pageHeading = "Trending Career Guides & Articles";
    $pageDesc = "Most read and trending articles, hiring updates, and job vacancies.";
    $pageTitle = "Trending Articles & Guides";
    $metaDescription = "Discover the most popular and trending job opportunities, ATS guides, and employment circulars.";
    $canonicalUrl = BASE_URL . '/trending';
} elseif ($isJobs) {
    $pageHeading = "Latest Job Circulars & Openings";
    $pageDesc = "Browse verified employment opportunities, public examinations, and corporate recruitment.";
    $pageTitle = "Job Openings & Circulars";
    $metaDescription = "Explore the newest verified job listings, tech careers, and competitive hiring circulars.";
    $canonicalUrl = BASE_URL . '/jobs';
} elseif ($activeCategory) {
    $pageHeading = h($activeCategory['name']) . " Articles & Guides";
    $pageDesc = !empty($activeCategory['description']) ? h($activeCategory['description']) : "Browse verified articles and jobs in " . h($activeCategory['name']) . ".";
    $pageTitle = "{$activeCategory['name']} Articles & Guides";
    $metaDescription = "Browse articles, job updates, and career roadmaps in {$activeCategory['name']}.";
    $canonicalUrl = BASE_URL . '/category/' . urlencode($activeCategory['slug']);
} else {
    $pageHeading = "Jobs & Career Guides";
    $pageDesc = "Browse verified job listings, interview strategies, resume blueprints, and career guides.";
    $pageTitle = "All Articles & Guides (Page {$currentPage})";
    $metaDescription = "Comprehensive catalog of employment advice, ATS resume tips, exam preparation, and hiring updates.";
    $canonicalUrl = BASE_URL . '/articles';
}

$metaKeywords = "career vlogs, job vacancies, ATS resume guides, behavioral interviews, tech hiring, CareerNexaa";

include __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="page-banner">
    <div class="site-container">
        <h1><?= $pageHeading ?></h1>
        <p><?= $pageDesc ?></p>
    </div>
</section>

<section class="page-section">
    <div class="site-container">
        <!-- Filter Tabs / Pills -->
        <div style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                <a href="<?= BASE_URL ?>/articles" class="btn <?= (empty($categorySlug) && !$isVlogs && !$isTrending && !$isJobs) ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                    All Categories
                </a>
                <a href="<?= BASE_URL ?>/vlogs" class="btn <?= $isVlogs ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                    Vlogs
                </a>
                <a href="<?= BASE_URL ?>/trending" class="btn <?= $isTrending ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                    Trending
                </a>
                <?php 
                $allCats = get_all_categories();
                foreach ($allCats as $cat): 
                ?>
                    <a href="<?= BASE_URL ?>/category/<?= h($cat['slug']) ?>" 
                       class="btn <?= ($categorySlug === $cat['slug']) ? 'btn-primary' : 'btn-secondary' ?>" 
                       style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                        <?= h($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div style="font-size: 0.9rem; color: var(--text-muted);">
                Showing <?= count($articles) ?> of <?= $totalArticles ?> items
            </div>
        </div>

        <?php if (!empty($articles)): ?>
            <div class="articles-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="article-card">
                        <div class="article-thumb-wrap">
                            <?php 
                            $thumbPath = get_article_thumbnail_url($article);
                            ?>
                            <img src="<?= $thumbPath ?>" alt="<?= h($article['title']) ?>" class="article-thumb" loading="lazy" decoding="async">
                            <span class="article-badge <?= $article['is_job_posting'] ? 'job-badge' : '' ?>">
                                <?= $article['is_job_posting'] ? 'Job Opening' : h($article['category_name']) ?>
                            </span>
                        </div>

                        <div class="article-content">
                            <div class="article-meta">
                                <span><?= format_date($article['published_at']) ?></span>
                                <span class="meta-dot">&bull;</span>
                                <span><?= (int)$article['views'] ?> views</span>
                            </div>

                            <h3 class="article-title">
                                <a href="<?= BASE_URL ?>/article/<?= h($article['slug']) ?>">
                                    <?= h($article['title']) ?>
                                </a>
                            </h3>

                            <p class="article-excerpt">
                                <?= h($article['excerpt']) ?>
                            </p>

                            <?php if ($article['is_job_posting'] && !empty($article['job_company'])): ?>
                                <div class="job-card-specs">
                                    <span class="job-spec-item"><strong>Company:</strong> <?= h($article['job_company']) ?></span>
                                    <?php if (!empty($article['job_salary'])): ?>
                                        <span class="job-spec-item"><strong>Pay:</strong> <?= h($article['job_salary']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="article-footer">
                                <a href="<?= BASE_URL ?>/category/<?= h($article['category_slug']) ?>" style="font-weight:600; color:var(--text-muted); font-size:0.825rem;">
                                    <?= h($article['category_name']) ?>
                                </a>
                                <a href="<?= BASE_URL ?>/article/<?= h($article['slug']) ?>" class="read-more-link">
                                    Read Guide &rarr;
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Monetization Slot: Between Content Ad (Between content sections / listing rows) -->
            <?php if (function_exists('render_monetization_ad')): ?>
                <div style="margin: 2rem 0;">
                    <?php render_monetization_ad('between_content_ad'); ?>
                </div>
            <?php endif; ?>

            <!-- Pagination Navigation -->
            <?php 
            $paginationQuery = '';
            if ($isVlogs) $paginationQuery .= '&vlogs=1';
            elseif ($isTrending) $paginationQuery .= '&trending=1';
            elseif ($isJobs) $paginationQuery .= '&jobs=1';
            if (!empty($categorySlug)) $paginationQuery .= '&category=' . urlencode($categorySlug);
            ?>
            <?php if ($totalPages > 1): ?>
                <nav class="pagination-nav" aria-label="Articles Pagination">
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?><?= $paginationQuery ?>" class="page-btn" aria-label="Previous page">&larr; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?><?= $paginationQuery ?>" 
                               class="page-btn <?= ($i === $currentPage) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?><?= $paginationQuery ?>" class="page-btn" aria-label="Next page">Next &rarr;</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state-card">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1rem; color:var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <h3 style="font-size:1.4rem; color:var(--secondary); margin-bottom:0.5rem;">No Articles Found</h3>
                <p style="color:var(--text-muted); max-width:450px; margin:0 auto 1.5rem;">There are currently no published articles in this section. Check back shortly as new guides are added daily.</p>
                <a href="<?= BASE_URL ?>/articles" class="btn btn-primary">Browse All Articles</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
