<?php
/**
 * Public Website - Tag Topic Archive Page
 * CareerNexaa - Modern Jobs & Career Guidance Hub
 * Displays all verified articles, tutorials, and hiring circulars tagged with the given tag.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header("Location: " . BASE_URL . "/articles.php");
    exit;
}

$db = getDBConnection();

// Fetch Tag Details
$tagStmt = $db->prepare("SELECT * FROM tags WHERE slug = ? LIMIT 1");
$tagStmt->execute([$slug]);
$tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

if (!$tag) {
    // If not found in tags table, attempt to match formatted slug
    $fallbackName = ucwords(str_replace('-', ' ', $slug));
    $tag = [
        'id' => 0,
        'name' => $fallbackName,
        'slug' => $slug,
        'description' => 'Browse verified guides and publications tagged with ' . $fallbackName
    ];
}

$tagName = $tag['name'];

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Count Matching Articles (Published & not trashed)
$countStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM articles a 
    WHERE (a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL))
      AND (
        a.tags = :name1 
        OR a.tags LIKE :like1 
        OR a.tags LIKE :like2 
        OR a.tags LIKE :like3
      )
");
$countStmt->execute([
    ':name1' => $tagName,
    ':like1' => $tagName . ',%',
    ':like2' => '%, ' . $tagName . '%',
    ':like3' => '%,' . $tagName . '%'
]);
$totalArticles = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalArticles / $perPage);

// Fetch Articles
$articlesStmt = $db->prepare("
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE (a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL))
      AND (
        a.tags = :name1 
        OR a.tags LIKE :like1 
        OR a.tags LIKE :like2 
        OR a.tags LIKE :like3
      )
    ORDER BY a.published_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$articlesStmt->execute([
    ':name1' => $tagName,
    ':like1' => $tagName . ',%',
    ':like2' => '%, ' . $tagName . '%',
    ':like3' => '%,' . $tagName . '%'
]);
$articles = $articlesStmt->fetchAll(PDO::FETCH_ASSOC);

// SEO setup
$siteName = get_site_name();
$pageTitle = "#" . $tagName . " - Career Guides & Jobs | " . $siteName;
$metaDescription = !empty($tag['description']) ? $tag['description'] : "Read the latest verified articles and resources tagged with {$tagName} on {$siteName}.";
$canonicalUrl = BASE_URL . '/tag/' . urlencode($tag['slug']);

include __DIR__ . '/includes/header.php';
?>

<!-- Tag Archive Hero Header -->
<div class="page-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:3rem 0 2.5rem;">
    <div class="site-container">
        <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.75rem; font-size:0.85rem; color:#64748b; font-weight:600;">
            <a href="<?= BASE_URL ?>/" style="color:#64748b; text-decoration:none;">Home</a>
            <span>&rsaquo;</span>
            <a href="<?= BASE_URL ?>/articles" style="color:#64748b; text-decoration:none;">Articles</a>
            <span>&rsaquo;</span>
            <span style="color:#059669;">#<?= h($tagName) ?></span>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:1.25rem;">
            <div>
                <div style="display:inline-flex; align-items:center; gap:0.4rem; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; padding:0.25rem 0.75rem; border-radius:9999px; font-size:0.8rem; font-weight:700; margin-bottom:0.5rem;">
                    <span>Topic Tag</span> &bull; <span><?= $totalArticles ?> publication<?= $totalArticles !== 1 ? 's' : '' ?></span>
                </div>
                <h1 style="font-family:var(--font-heading); font-size:2.25rem; font-weight:800; color:#0f172a; margin:0 0 0.5rem 0; letter-spacing:-0.02em;">
                    #<?= h($tagName) ?>
                </h1>
                <?php if (!empty($tag['description'])): ?>
                    <p style="color:#64748b; font-size:1rem; max-width:680px; margin:0; line-height:1.6;">
                        <?= h($tag['description']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div>
                <a href="<?= BASE_URL ?>/articles" class="admin-btn" style="background:#ffffff; border:1px solid #cbd5e1; color:#334155; padding:0.55rem 1.15rem; border-radius:8px; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:0.4rem;">
                    &larr; All Articles
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main Articles Grid -->
<main class="site-container" style="padding:3rem 1.5rem;">
    <?php if (empty($articles)): ?>
        <div style="text-align:center; padding:4rem 2rem; background:#ffffff; border:1px dashed #cbd5e1; border-radius:12px; max-width:600px; margin:0 auto;">
            <div style="width:54px; height:54px; border-radius:50%; background:#f1f5f9; display:inline-flex; align-items:center; justify-content:center; color:#94a3b8; margin-bottom:1rem;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            </div>
            <h3 style="font-family:var(--font-heading); font-size:1.35rem; color:#0f172a; margin-bottom:0.5rem;">No Articles Found</h3>
            <p style="color:#64748b; font-size:0.95rem; margin-bottom:1.5rem;">There are currently no published articles tagged under "<?= h($tagName) ?>".</p>
            <a href="<?= BASE_URL ?>/articles" class="admin-btn admin-btn-primary" style="padding:0.6rem 1.5rem; border-radius:8px; text-decoration:none; display:inline-block; font-weight:700;">
                Browse All Guides
            </a>
        </div>
    <?php else: ?>
        <div class="cp-card-grid-3">
            <?php foreach ($articles as $art): ?>
            <?php 
                $thumbPath = get_article_thumbnail_url($art);
                $artRawTags = !empty($art['tags']) ? $art['tags'] : ($art['keywords'] ?? '');
                $artTags = array_filter(array_map('trim', explode(',', (string)$artRawTags)));
            ?>
            <article class="cp-article-card">
                <div class="cp-card-thumb-wrap">
                    <a href="<?= get_article_url($art['slug']) ?>">
                        <img src="<?= $thumbPath ?>" alt="<?= h($art['title']) ?>" class="cp-card-thumb" loading="lazy" decoding="async">
                    </a>
                    <span class="cp-badge-floating <?= $art['is_job_posting'] ? 'job-badge' : '' ?>">
                        <?= $art['is_job_posting'] ? 'Job Opening' : h($art['category_name']) ?>
                    </span>
                </div>

                <div class="cp-card-body">
                    <div class="cp-card-meta-top">
                        <span class="cp-card-cat-name"><?= h($art['category_name']) ?></span>
                        <span>&bull;</span>
                        <span><?= format_date($art['published_at']) ?></span>
                    </div>

                    <h3 class="cp-card-title">
                        <a href="<?= get_article_url($art['slug']) ?>">
                            <?= h($art['title']) ?>
                        </a>
                    </h3>

                    <?php if (!empty($artTags)): ?>
                    <div class="card-tags-chip-row">
                        <?php foreach (array_slice($artTags, 0, 3) as $tg): ?>
                            <a href="<?= BASE_URL ?>/tag/<?= urlencode(strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $tg), '-'))) ?>" class="card-tag-chip" style="<?= strcasecmp($tg, $tagName) === 0 ? 'background:#ecfdf5; color:#047857; font-weight:800; border:1px solid #a7f3d0;' : '' ?>">
                                #<?= h($tg) ?>
                            </a>
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
                        <a href="<?= get_article_url($art['slug']) ?>" class="cp-card-read-link">
                            <?= $art['is_job_posting'] ? 'View Job &rarr;' : 'Read Guide &rarr;' ?>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="display:flex; justify-content:center; gap:0.5rem; margin-top:3rem;">
            <?php if ($page > 1): ?>
                <a href="<?= BASE_URL ?>/tag/<?= urlencode($tag['slug']) ?>?page=<?= $page - 1 ?>" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#334155; padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:700;">
                    &larr; Prev
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="<?= BASE_URL ?>/tag/<?= urlencode($tag['slug']) ?>?page=<?= $i ?>" class="admin-btn" style="padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:700; <?= $i === $page ? 'background:#059669; color:#fff; border:1px solid #059669;' : 'background:#fff; border:1px solid #cbd5e1; color:#334155;' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= BASE_URL ?>/tag/<?= urlencode($tag['slug']) ?>?page=<?= $page + 1 ?>" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#334155; padding:0.5rem 1rem; border-radius:8px; text-decoration:none; font-weight:700;">
                    Next &rarr;
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
