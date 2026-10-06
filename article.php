<?php
/**
 * Public Website - Single Article Page
 * Renders full article content, job specs, related guides, and conditional JobPosting JSON-LD.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ads-placeholder.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: ' . BASE_URL . '/articles.php');
    exit;
}

$db = getDBConnection();

// Fetch article with category and author info
$stmt = $db->prepare("
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE a.slug = ? AND a.status = 'published'
    LIMIT 1
");
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// Increment View Count
$updateViews = $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
$updateViews->execute([$article['id']]);

// SEO Setup
$pageTitle = !empty($article['seo_title']) ? $article['seo_title'] : $article['title'];
$metaDescription = !empty($article['meta_description']) ? $article['meta_description'] : $article['excerpt'];
$metaKeywords = generate_article_keywords($article);
$metaAuthor = !empty($article['author_name']) ? $article['author_name'] : SITE_NAME . ' Editorial Team';
$articleSection = !empty($article['category_name']) ? $article['category_name'] : 'Career Advice';
$articlePublishedTime = date('c', strtotime($article['published_at'] ?? $article['created_at']));
$articleModifiedTime = date('c', strtotime($article['updated_at'] ?? $article['created_at']));
$canonicalUrl = BASE_URL . '/article.php?slug=' . urlencode($article['slug']);
$ogType = 'article';
$ogImage = !empty($article['featured_image']) && file_exists(__DIR__ . '/' . $article['featured_image']) 
    ? BASE_URL . '/' . $article['featured_image'] 
    : BASE_URL . '/assets/images/default-article.jpg';

// Structured Data (JSON-LD)
// STRICT REQUIREMENT: Add JobPosting structured data ONLY when is_job_posting == 1 and essential data is present!
$structuredDataJson = '';
if (!empty($article['is_job_posting']) && !empty($article['job_company'])) {
    $jobPostingData = [
        "@context" => "https://schema.org/",
        "@type" => "JobPosting",
        "title" => $article['title'],
        "description" => strip_tags($article['excerpt']),
        "identifier" => [
            "@type" => "PropertyValue",
            "name" => $article['job_company'],
            "value" => "JOB-" . $article['id']
        ],
        "datePosted" => date('c', strtotime($article['published_at'] ?? $article['created_at'])),
        "validThrough" => !empty($article['job_deadline']) ? date('c', strtotime($article['job_deadline'] . ' 23:59:59')) : date('c', strtotime('+60 days', strtotime($article['created_at']))),
        "employmentType" => !empty($article['job_type']) ? $article['job_type'] : "FULL_TIME",
        "hiringOrganization" => [
            "@type" => "Organization",
            "name" => $article['job_company']
        ],
        "jobLocation" => [
            "@type" => "Place",
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => !empty($article['job_location']) ? $article['job_location'] : "Headquarters",
                "addressLocality" => !empty($article['job_location']) ? $article['job_location'] : "Global",
                "addressCountry" => "US"
            ]
        ]
    ];

    if (!empty($article['job_salary'])) {
        $jobPostingData["baseSalary"] = [
            "@type" => "MonetaryAmount",
            "currency" => "USD",
            "value" => [
                "@type" => "QuantitativeValue",
                "value" => $article['job_salary'],
                "unitText" => "HOUR"
            ]
        ];
    }

    $structuredDataJson = '<script type="application/ld+json">' . json_encode($jobPostingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
} else {
    // Standard BlogPosting schema for editorial articles
    $blogPostingData = [
        "@context" => "https://schema.org/",
        "@type" => "BlogPosting",
        "headline" => $article['title'],
        "description" => $article['excerpt'],
        "image" => $ogImage,
        "author" => [
            "@type" => "Person",
            "name" => $article['author_name']
        ],
        "publisher" => [
            "@type" => "Organization",
            "name" => SITE_NAME,
            "logo" => [
                "@type" => "ImageObject",
                "url" => BASE_URL . "/assets/images/default-article.jpg"
            ]
        ],
        "datePublished" => date('c', strtotime($article['published_at'] ?? $article['created_at'])),
        "dateModified" => date('c', strtotime($article['updated_at'] ?? $article['created_at']))
    ];
    $structuredDataJson = '<script type="application/ld+json">' . json_encode($blogPostingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
}

$extraHead = $structuredDataJson;

// Fetch Related Articles from same category
$relatedStmt = $db->prepare("
    SELECT a.*, c.name as category_name, c.slug as category_slug
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE a.category_id = ? AND a.id != ? AND a.status = 'published'
    ORDER BY a.published_at DESC
    LIMIT 3
");
$relatedStmt->execute([$article['category_id'], $article['id']]);
$relatedArticles = $relatedStmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Article Header Section -->
<header class="single-article-header">
    <div class="site-container single-article-container">
        <!-- Breadcrumbs -->
        <nav class="article-breadcrumbs" aria-label="Breadcrumbs">
            <a href="<?= BASE_URL ?>/">Home</a>
            <span>&rsaquo;</span>
            <a href="<?= BASE_URL ?>/articles.php">Articles</a>
            <span>&rsaquo;</span>
            <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($article['category_slug']) ?>">
                <?= h($article['category_name']) ?>
            </a>
            <span>&rsaquo;</span>
            <span style="color:var(--text-primary); font-weight:600;"><?= truncate_words($article['title'], 35) ?></span>
        </nav>

        <h1 class="single-title"><?= h($article['title']) ?></h1>

        <div class="single-meta-bar">
            <span>By <strong><?= h($article['author_name']) ?></strong></span>
            <span class="meta-dot">&bull;</span>
            <span>Published on <?= format_date($article['published_at']) ?></span>
            <?php if (!empty($article['updated_at']) && $article['updated_at'] !== $article['published_at']): ?>
                <span class="meta-dot">&bull;</span>
                <span style="color:var(--accent-emerald);">Updated: <?= format_date($article['updated_at']) ?></span>
            <?php endif; ?>
            <span class="meta-dot">&bull;</span>
            <span><?= (int)$article['views'] ?> views</span>
            <span class="meta-dot">&bull;</span>
            <span class="article-badge <?= $article['is_job_posting'] ? 'job-badge' : '' ?>" style="position:static;">
                <?= $article['is_job_posting'] ? 'Job Opportunity' : h($article['category_name']) ?>
            </span>
        </div>
    </div>
</header>

<!-- Article Body Section -->
<div class="page-section" style="padding-top: 2rem;">
    <div class="site-container single-article-container">


        <!-- Lead Excerpt -->
        <div class="single-lead-excerpt">
            <?= h($article['excerpt']) ?>
        </div>

        <!-- Featured Image -->
        <?php if (!empty($article['featured_image']) && file_exists(__DIR__ . '/' . $article['featured_image'])): ?>
            <img src="<?= BASE_URL ?>/<?= h($article['featured_image']) ?>" 
                 alt="<?= h($article['title']) ?>" 
                 class="single-featured-image">
        <?php endif; ?>

        <!-- Genuine Job Posting Highlight Card (Shown ONLY for genuine Job Posts) -->
        <?php if (!empty($article['is_job_posting']) && !empty($article['job_company'])): ?>
            <div class="job-posting-card">
                <div class="job-posting-card-header">
                    <div>
                        <div style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Hiring Organization</div>
                        <h2 class="job-company-title"><?= h($article['job_company']) ?></h2>
                    </div>
                    <span class="job-status-pill">Active Opening</span>
                </div>

                <div class="job-specs-grid">
                    <?php if (!empty($article['job_location'])): ?>
                        <div class="spec-box">
                            <div class="spec-label">Location</div>
                            <div class="spec-value"><?= h($article['job_location']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($article['job_type'])): ?>
                        <div class="spec-box">
                            <div class="spec-label">Employment Type</div>
                            <div class="spec-value"><?= h($article['job_type']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($article['job_salary'])): ?>
                        <div class="spec-box">
                            <div class="spec-label">Compensation</div>
                            <div class="spec-value"><?= h($article['job_salary']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($article['job_deadline'])): ?>
                        <div class="spec-box">
                            <div class="spec-label">Application Deadline</div>
                            <div class="spec-value" style="color:var(--danger);"><?= format_date($article['job_deadline']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="background:var(--bg-surface-elevated); border-radius:var(--radius-sm); padding:1.1rem; border:1px solid var(--border-color); font-size:0.875rem; color:var(--text-muted); line-height:1.5;">
                    <strong style="color:var(--primary);">Applicant Guidance:</strong> Apply only through official company channels or verified HR career kiosks. Never submit payment for recruitment or interview slots.
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Prose Content -->
        <div class="article-prose">
            <?= sanitize_article_html($article['content']) ?>
        </div>

        <!-- Article Topic Keywords & Tags -->
        <?php 
        $articleKeywordsRaw = !empty($article['keywords']) ? $article['keywords'] : $metaKeywords;
        $keywordTags = array_filter(array_map('trim', explode(',', $articleKeywordsRaw)));
        ?>
        <?php if (!empty($keywordTags)): ?>
            <div class="article-tags-wrap" style="margin:2.25rem 0 1.5rem 0; padding-top:1.25rem; border-top:1px solid var(--border-color);">
                <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                    <span style="font-size:0.8rem; font-weight:700; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.04em;">
                        🏷️ Topic Tags:
                    </span>
                    <?php foreach ($keywordTags as $tag): ?>
                        <?php if (strlen($tag) > 1 && strtolower($tag) !== strtolower(SITE_NAME)): ?>
                            <a href="<?= BASE_URL ?>/search.php?q=<?= urlencode($tag) ?>" class="article-tag-badge" style="display:inline-flex; align-items:center; background:var(--bg-surface-elevated); color:var(--text-main); font-size:0.775rem; font-weight:600; padding:0.25rem 0.65rem; border-radius:9999px; border:1px solid var(--border-color); text-decoration:none; transition:all 0.15s ease;">
                                #<?= h($tag) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Social Share Bar -->
        <div class="article-share-section">
            <span style="font-weight:700; color:#ffffff; font-size:0.95rem;">Share this guide:</span>
            <div class="share-links">
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-twitter">
                    Twitter / X
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-linkedin">
                    LinkedIn
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" class="share-btn share-facebook">
                    Facebook
                </a>
            </div>
        </div>

        <!-- Editorial Ethics Disclaimer -->
        <div style="margin-top:2.5rem; background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.35rem; font-size:0.85rem; color:var(--text-muted); line-height:1.6;">
            <strong style="color:#ffffff;">Editorial Transparency & Policy Note:</strong> At <?= SITE_NAME ?>, our mission is to deliver truthful, carefully vetted career insights. All job postings and guides are verified against official company releases. For additional questions or to report out-of-date information, please visit our <a href="<?= BASE_URL ?>/contact.php">Contact Page</a>.
        </div>

    </div>
</div>

<!-- Related Articles Section -->
<?php if (!empty($relatedArticles)): ?>
<section class="page-section page-section-alt">
    <div class="site-container">
        <div class="section-header">
            <div class="section-title-wrap">
                <h2>Related Articles in <?= h($article['category_name']) ?></h2>
                <p class="section-subtitle">Continue reading recommended employment resources from this sector.</p>
            </div>
        </div>

        <div class="articles-grid">
            <?php foreach ($relatedArticles as $rel): ?>
                <article class="article-card">
                    <div class="article-thumb-wrap">
                        <?php 
                        $thumbPath = !empty($rel['featured_image']) && file_exists(__DIR__ . '/' . $rel['featured_image']) 
                            ? BASE_URL . '/' . $rel['featured_image'] 
                            : BASE_URL . '/assets/images/default-article.jpg';
                        ?>
                        <img src="<?= $thumbPath ?>" alt="<?= h($rel['title']) ?>" class="article-thumb" loading="lazy">
                        <span class="article-badge"><?= h($rel['category_name']) ?></span>
                    </div>

                    <div class="article-content">
                        <div class="article-meta">
                            <span><?= format_date($rel['published_at']) ?></span>
                            <span class="meta-dot">&bull;</span>
                            <span><?= (int)$rel['views'] ?> views</span>
                        </div>

                        <h3 class="article-title">
                            <a href="<?= BASE_URL ?>/article.php?slug=<?= h($rel['slug']) ?>">
                                <?= h($rel['title']) ?>
                            </a>
                        </h3>

                        <p class="article-excerpt">
                            <?= h($rel['excerpt']) ?>
                        </p>

                        <div class="article-footer">
                            <a href="<?= BASE_URL ?>/article.php?slug=<?= h($rel['slug']) ?>" class="read-more-link">
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

<?php include __DIR__ . '/includes/footer.php'; ?>
