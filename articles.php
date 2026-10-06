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
$categorySlug = isset($_GET['category']) ? trim($_GET['category']) : '';
$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$perPage = ARTICLES_PER_PAGE;
$offset = ($currentPage - 1) * $perPage;

$whereClauses = ["a.status = 'published'"];
$params = [];

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

$isTrending = isset($_GET['trending']) && $_GET['trending'] == '1';
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

// SEO setup
$pageTitle = $isTrending
    ? "Trending Articles & Guides"
    : ($activeCategory ? "{$activeCategory['name']} Articles & Guides" : "All Articles (Page {$currentPage})");
$metaDescription = $activeCategory 
    ? "Browse articles, job updates, and career roadmaps in {$activeCategory['name']}." 
    : "Comprehensive catalog of employment advice, ATS resume tips, exam preparation, and hiring updates.";
$metaKeywords = $activeCategory 
    ? "{$activeCategory['name']}, {$activeCategory['name']} jobs, career guides, interview preparation, CareerPulse"
    : "career articles, job vacancies, ATS resume guides, behavioral interviews, tech hiring, government exams, CareerPulse";

include __DIR__ . '/includes/header.php';
?>

<!-- Page Banner -->
<section class="page-banner">
    <div class="site-container">
        <h1><?= $activeCategory ? h($activeCategory['name']) : 'Jobs & Career Guides' ?></h1>
        <p>
            <?= $activeCategory ? h($activeCategory['description']) : 'Browse verified job listings, interview strategies, resume blueprints, and career guides.' ?>
        </p>
    </div>
</section>

<section class="page-section">
    <div class="site-container">
        <!-- Filter Tabs / Pills -->
        <div style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                <a href="<?= BASE_URL ?>/articles.php" class="btn <?= empty($categorySlug) ? 'btn-primary' : 'btn-secondary' ?>" style="font-size:0.85rem; padding:0.4rem 0.9rem;">
                    All Categories
                </a>
                <?php 
                $allCats = get_all_categories();
                foreach ($allCats as $cat): 
                ?>
                    <a href="<?= BASE_URL ?>/articles.php?category=<?= h($cat['slug']) ?>" 
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
                            $thumbPath = !empty($article['featured_image']) && file_exists(__DIR__ . '/' . $article['featured_image']) 
                                ? BASE_URL . '/' . $article['featured_image'] 
                                : BASE_URL . '/assets/images/default-article.jpg';
                            ?>
                            <img src="<?= $thumbPath ?>" alt="<?= h($article['title']) ?>" class="article-thumb" loading="lazy">
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
                                <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>">
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
                                <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($article['category_slug']) ?>" style="font-weight:600; color:var(--text-muted); font-size:0.825rem;">
                                    <?= h($article['category_name']) ?>
                                </a>
                                <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>" class="read-more-link">
                                    Read Guide &rarr;
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Navigation -->
            <?php if ($totalPages > 1): ?>
                <nav class="pagination-nav" aria-label="Articles Pagination">
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?><?= !empty($categorySlug) ? '&category=' . urlencode($categorySlug) : '' ?>" class="page-btn" aria-label="Previous page">&larr; Prev</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i ?><?= !empty($categorySlug) ? '&category=' . urlencode($categorySlug) : '' ?>" 
                               class="page-btn <?= ($i === $currentPage) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?><?= !empty($categorySlug) ? '&category=' . urlencode($categorySlug) : '' ?>" class="page-btn" aria-label="Next page">Next &rarr;</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state-card">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1rem; color:var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <h3 style="font-size:1.4rem; color:var(--secondary); margin-bottom:0.5rem;">No Articles Found</h3>
                <p style="color:var(--text-muted); max-width:450px; margin:0 auto 1.5rem;">There are currently no published articles in this sector. Check back shortly as new guides are added daily.</p>
                <a href="<?= BASE_URL ?>/articles.php" class="btn btn-primary">Browse All Articles</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
