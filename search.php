<?php
/**
 * Public Website - Search Results Page
 * Full-text keyword search across titles, content, excerpts, and companies.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ads-placeholder.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$db = getDBConnection();

$articles = [];
$totalResults = 0;

if (!empty($query)) {
    $searchPattern = '%' . $query . '%';
    $sql = "
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE a.status = 'published' AND (
            a.title LIKE ? OR 
            a.excerpt LIKE ? OR 
            a.content LIKE ? OR 
            a.job_company LIKE ? OR
            c.name LIKE ?
        )
        ORDER BY a.published_at DESC
        LIMIT 30
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
    $articles = $stmt->fetchAll();
    $totalResults = count($articles);
}

$pageTitle = !empty($query) ? "Search: " . h($query) : "Search Articles & Job Guides";
$metaDescription = "Search verified job openings, resume tips, and career exam frameworks on CareerPulse.";

include __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="site-container">
        <h1>Search Results</h1>
        <p>
            <?= !empty($query) ? 'Displaying results for: "<strong>' . h($query) . '</strong>"' : 'Find the job guides and career roadmaps you need' ?>
        </p>
    </div>
</section>

<section class="page-section">
    <div class="site-container">
        <!-- Search Bar with Value -->
        <div style="max-width: 680px; margin: 0 auto 3rem;">
            <form action="<?= BASE_URL ?>/search.php" method="GET" style="display:flex; gap:0.5rem;">
                <input type="text" name="q" value="<?= h($query) ?>" class="form-control" placeholder="Search by job title, company, skill, or exam..." required style="padding:0.85rem 1.25rem;">
                <button type="submit" class="btn btn-primary" style="padding:0.85rem 1.5rem; flex-shrink:0;">
                    Search
                </button>
            </form>
        </div>

        <?php if (!empty($query)): ?>
            <div class="section-header">
                <div class="section-title-wrap">
                    <h2>Found <?= $totalResults ?> <?= $totalResults === 1 ? 'Article' : 'Articles' ?></h2>
                    <p class="section-subtitle">Showing all published matches</p>
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
            <?php else: ?>
                <div class="empty-state-card">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 1rem; color:var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <h3 style="font-size:1.4rem; color:var(--secondary); margin-bottom:0.5rem;">No Results Found for "<?= h($query) ?>"</h3>
                    <p style="color:var(--text-muted); max-width:480px; margin:0 auto 1.5rem;">
                        Try searching with different keywords like "Remote", "Resume", "Civil", or "Interview".
                    </p>
                    <a href="<?= BASE_URL ?>/articles.php" class="btn btn-primary">Browse All Articles</a>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div style="text-align:center; padding:2rem 0;">
                <p style="color:var(--text-muted); font-size:1.1rem;">Please enter a search term above to explore guides and articles.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
