<?php
/**
 * Public Website - Categories Explorer & Category Articles Page
 * Shows list of all categories OR all articles under a specific category.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ads-placeholder.php';

$db = getDBConnection();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (!empty($slug)) {
    // 1. Specific Category View
    $catStmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
    $catStmt->execute([$slug]);
    $category = $catStmt->fetch();

    if (!$category) {
        set_flash('error', 'The requested category does not exist.');
        header('Location: ' . BASE_URL . '/categories');
        exit;
    }

    $pageTitle = "{$category['name']} - Career Articles & Job Guides";
    $metaDescription = !empty($category['description']) ? $category['description'] : "Explore all guides and employment opportunities in {$category['name']}.";
    $metaKeywords = "{$category['name']}, {$category['name']} jobs, {$category['name']} career advice, exam schedules, CareerNexaa";

    // Pagination for Category
    $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) $currentPage = 1;
    $perPage = ARTICLES_PER_PAGE;
    $offset = ($currentPage - 1) * $perPage;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published' AND (is_trash = 0 OR is_trash IS NULL)");
    $countStmt->execute([$category['id']]);
    $totalArticles = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalArticles / $perPage);

    $articlesStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE a.category_id = ? AND a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)
        ORDER BY a.published_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $articlesStmt->execute([$category['id']]);
    $articles = $articlesStmt->fetchAll();

    include __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
        <div class="site-container">
            <nav class="article-breadcrumbs" style="justify-content:center; color:#94a3b8; margin-bottom:0.75rem;">
                <a href="<?= BASE_URL ?>/" style="color:#cbd5e1;">Home</a>
                <span>&rsaquo;</span>
                <a href="<?= BASE_URL ?>/categories" style="color:#cbd5e1;">Categories</a>
                <span>&rsaquo;</span>
                <span style="color:#ffffff; font-weight:600;"><?= h($category['name']) ?></span>
            </nav>
            <h1><?= h($category['name']) ?></h1>
            <p><?= h($category['description']) ?></p>
        </div>
    </section>

    <section class="page-section">
        <div class="site-container">
            <div class="section-header">
                <div class="section-title-wrap">
                    <h2>Articles in this Sector</h2>
                    <p class="section-subtitle">Showing <?= count($articles) ?> of <?= $totalArticles ?> published articles</p>
                </div>
                <a href="<?= BASE_URL ?>/categories" class="view-all-link">&larr; All Categories</a>
            </div>

            <?php if (!empty($articles)): ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <article class="article-card">
                            <div class="article-thumb-wrap">
                                <?php 
                                $thumbPath = get_article_thumbnail_url($article);
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
                                    <a href="<?= get_article_url($article['slug']) ?>">
                                        <?= h($article['title']) ?>
                                    </a>
                                </h3>

                                <p class="article-excerpt">
                                    <?= h($article['excerpt']) ?>
                                </p>

                                <?php if ($article['is_job_posting'] && !empty($article['job_company'])): ?>
                                    <div class="job-card-specs">
                                        <span class="job-spec-item"><strong>Company:</strong> <?= h($article['job_company']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="article-footer">
                                    <span style="font-weight:600; color:var(--text-muted); font-size:0.825rem;">
                                        <?= h($article['category_name']) ?>
                                    </span>
                                    <a href="<?= get_article_url($article['slug']) ?>" class="read-more-link">
                                        Read Guide &rarr;
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-nav">
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= get_category_url($slug) ?>?page=<?= $i ?>" class="page-btn <?= ($i === $currentPage) ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state-card">
                    <h3>No Articles Found in this Category</h3>
                    <p style="color:var(--text-muted); margin:0.75rem 0 1.5rem;">New career guides are being added to this sector shortly.</p>
                    <a href="<?= BASE_URL ?>/articles" class="btn btn-primary">Browse All Articles</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
} else {
    // 2. All Categories Overview Page
    $pageTitle = "Explore Career & Job Sectors";
    $metaDescription = "Browse all career categories on CareerNexaa: Government Jobs, Private Tech, Remote Positions, Resume Prep, and Interview Frameworks.";

    $categories = get_all_categories(true);

    include __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
        <div class="site-container">
            <h1>Browse Career Categories</h1>
            <p>Select a sector below to explore specialized guides, career roadmaps, and latest vacancies.</p>
        </div>
    </section>

    <?php
    $categoryImages = [
        'government-jobs' => BASE_URL . '/uploads/articles/sample-govt-jobs.jpg',
        'private-tech-jobs' => BASE_URL . '/uploads/articles/sample-remote-jobs.jpg',
        'remote-work-from-home' => BASE_URL . '/uploads/articles/sample-remote-jobs.jpg',
        'career-advice-tips' => BASE_URL . '/uploads/articles/sample-ats-resume.jpg',
        'resume-interview-prep' => BASE_URL . '/uploads/articles/sample-interview-prep.jpg',
        'internships-entry-level' => BASE_URL . '/uploads/articles/sample-warehouse-jobs.jpg',
    ];
    ?>
    <section class="page-section">
        <div class="site-container">
            <div class="category-tiles-grid" style="grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));">
                <?php foreach ($categories as $cat): ?>
                <?php 
                    $tileImg = isset($categoryImages[$cat['slug']]) 
                        ? $categoryImages[$cat['slug']] 
                        : BASE_URL . '/assets/images/default-article.jpg';
                ?>
                <a href="<?= get_category_url($cat['slug']) ?>" class="category-photo-tile" style="height: 170px;">
                    <img src="<?= $tileImg ?>" alt="<?= h($cat['name']) ?>" class="cat-tile-img" loading="lazy">
                    <div class="cat-tile-scrim"></div>
                    <div class="cat-tile-content">
                        <h3 class="cat-tile-title" style="font-size: 1.15rem;"><?= h($cat['name']) ?></h3>
                        <p style="color: #cbd5e1; font-size: 0.775rem; margin-bottom: 0.35rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            <?= h($cat['description']) ?>
                        </p>
                        <span class="cat-tile-count"><?= (int)$cat['total_articles'] ?> Articles &rarr;</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

include __DIR__ . '/includes/footer.php';
?>
