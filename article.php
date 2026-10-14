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
    WHERE a.slug = ? AND a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)
    LIMIT 1
");
$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// Record unique view based on Client IP & Session (prevents view count on page refresh)
$viewWasCounted = record_unique_article_view($db, $article['id']);
if ($viewWasCounted) {
    $article['views'] = (int)$article['views'] + 1;
}

// Handle Traditional Comment Form POST (progressive enhancement fallback)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['submit_comment'])) {
    $token = $_POST['csrf_token'] ?? '';
    $honeypot = trim($_POST['comments_bot_trap'] ?? ($_POST['website_url'] ?? ''));
    if (empty($honeypot) && verify_csrf_token($token)) {
        $cName = trim($_POST['user_name'] ?? '');
        $cEmail = trim($_POST['user_email'] ?? '');
        $cText = trim($_POST['comment_text'] ?? '');
        $cRes = submit_article_comment($article['id'], $cName, $cEmail, $cText);
        if ($cRes['success']) {
            set_flash('success', $cRes['message']);
        } else {
            set_flash('error', $cRes['message']);
        }
    } else {
        set_flash('error', 'Security verification failed or session expired. Please try again.');
    }
    header('Location: ' . get_article_url($article['slug']) . '#comments');
    exit;
}

// Fetch Article Reactions & Comments Data
$reactionsData = get_article_reactions_data($article['id']);
$articleComments = get_article_comments($article['id'], 'approved', 100);
$commentsCount = count($articleComments);

// SEO Setup
$pageTitle = !empty($article['seo_title']) ? $article['seo_title'] : $article['title'];
$metaDescription = clean_seo_description(!empty($article['meta_description']) ? $article['meta_description'] : $article['excerpt']);
$metaKeywords = generate_article_keywords($article);
$metaAuthor = !empty($article['author_name']) ? $article['author_name'] : SITE_NAME . ' Editorial Team';
$articleSection = !empty($article['category_name']) ? $article['category_name'] : 'Career Advice';
$articlePublishedTime = date('c', strtotime($article['published_at'] ?? $article['created_at']));
$articleModifiedTime = date('c', strtotime($article['updated_at'] ?? $article['created_at']));
$canonicalUrl = get_article_url($article['slug']);
$ogType = 'article';
$ogImage = get_article_featured_image_url($article);

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

// Calculate read time and author initials
$wordCount = str_word_count(strip_tags($article['content'] ?? ''));
$readTime = max(1, ceil($wordCount / 200));

$authorInitials = '';
$authorParts = preg_split('/\s+/', trim($article['author_name'] ?? 'Emma'));
if (!empty($authorParts[0])) $authorInitials .= mb_substr($authorParts[0], 0, 1);
if (!empty($authorParts[1])) $authorInitials .= mb_substr($authorParts[1], 0, 1);
if (empty($authorInitials)) $authorInitials = 'EM';
$authorInitials = strtoupper($authorInitials);

// Fetch 4 Related Articles for the sidebar
$relatedStmt = $db->prepare("
    SELECT a.*, c.name as category_name, c.slug as category_slug
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    WHERE a.category_id = ? AND a.id != ? AND a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)
    ORDER BY a.published_at DESC
    LIMIT 4
");
$relatedStmt->execute([$article['category_id'], $article['id']]);
$sidebarRelated = $relatedStmt->fetchAll();

// If fewer than 4 in same category, backfill with recent articles
if (count($sidebarRelated) < 4) {
    $needed = 4 - count($sidebarRelated);
    $excludeIds = array_merge([$article['id']], array_column($sidebarRelated, 'id'));
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $fillStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        WHERE a.id NOT IN ($placeholders) AND a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)
        ORDER BY a.published_at DESC
        LIMIT $needed
    ");
    $fillStmt->execute($excludeIds);
    $fillArticles = $fillStmt->fetchAll();
    $sidebarRelated = array_merge($sidebarRelated, $fillArticles);
}

// Fetch Topics (all active categories)
$sidebarTopics = get_all_categories();

include __DIR__ . '/includes/header.php';
?>

<!-- Article Detail Page: 2-Column Editorial Layout (Picture 1 Match) -->
<div class="article-detail-page">
    <div class="site-container article-layout-container">
        <!-- LEFT MAIN COLUMN -->
        <article class="article-main-stream">
            <!-- 1. Breadcrumbs with / separator -->
            <nav class="article-breadcrumbs" aria-label="Breadcrumbs">
                <a href="<?= BASE_URL ?>/">Home</a>
                <span class="crumb-sep">/</span>
                <a href="<?= BASE_URL ?>/articles">Articles</a>
                <span class="crumb-sep">/</span>
                <a href="<?= get_category_url($article['category_slug']) ?>"><?= h($article['category_name']) ?></a>
                <span class="crumb-sep">/</span>
                <span class="crumb-current"><?= h($article['title']) ?></span>
            </nav>

            <!-- 2. Category Kicker Pill + Read Time -->
            <div class="article-kicker-row">
                <a href="<?= get_category_url($article['category_slug']) ?>" class="article-cat-pill">
                    <?= h(strtoupper($article['category_name'])) ?>
                </a>
                <span class="article-read-kicker">ARTICLE &bull; <?= $readTime ?> MIN READ</span>
            </div>

            <!-- 3. H1 Title -->
            <h1 class="article-hero-title"><?= h($article['title']) ?></h1>

            <!-- 4. Lead Excerpt (Clean subtitle paragraph, NO green box) -->
            <?php if (!empty($article['excerpt'])): ?>
                <p class="article-lead-text"><?= h($article['excerpt']) ?></p>
            <?php endif; ?>

            <!-- 5. Author & Meta Row -->
            <div class="article-author-bar">
                <div class="author-avatar-circle" title="<?= h($article['author_name']) ?>">
                    <?= h($authorInitials) ?>
                </div>
                <span class="author-display-name"><?= h($article['author_name']) ?></span>
                <span class="author-meta-dot">&bull;</span>
                <span class="author-pub-date"><?= date('F j, Y', strtotime($article['published_at'])) ?></span>
                <span class="author-meta-dot">&bull;</span>
                <span class="author-view-count"><?= (int)$article['views'] ?> views</span>
            </div>

            <!-- 6. Featured Image -->
            <?php 
            $singleFeaturedImg = get_article_featured_image_url($article);
            if (!empty($singleFeaturedImg)): 
            ?>
                <div class="article-featured-banner">
                    <img src="<?= h($singleFeaturedImg) ?>" 
                         alt="<?= h(!empty($article['image_alt']) ? $article['image_alt'] : $article['title']) ?>" 
                         class="article-featured-img"
                         loading="eager">
                </div>
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
                            <a href="<?= BASE_URL ?>/search?q=<?= urlencode($tag) ?>" class="article-tag-badge" style="display:inline-flex; align-items:center; background:var(--bg-surface-elevated); color:var(--text-main); font-size:0.775rem; font-weight:600; padding:0.25rem 0.65rem; border-radius:9999px; border:1px solid var(--border-color); text-decoration:none; transition:all 0.15s ease;">
                                #<?= h($tag) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Article Reactions Box (Like & Dislike) -->
        <div class="article-reactions-box" id="articleReactions">
            <div class="article-reactions-prompt">
                <div class="article-reactions-prompt-icon">💡</div>
                <div class="article-reactions-prompt-text">
                    <h4>Was this career guide helpful?</h4>
                    <p>Your feedback helps our editorial desk improve future publications.</p>
                </div>
            </div>
            <div class="article-reactions-actions">
                <button type="button" 
                        class="btn-reaction btn-reaction-like <?= ($reactionsData['user_reaction'] === 'like') ? 'is-active' : '' ?>" 
                        id="btnLike" 
                        data-article-id="<?= (int)$article['id'] ?>" 
                        data-type="like"
                        aria-label="Mark as helpful">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                    </svg>
                    <span>Helpful</span>
                    <span class="reaction-counter" id="likeCounter"><?= (int)$reactionsData['likes'] ?></span>
                </button>

                <button type="button" 
                        class="btn-reaction btn-reaction-dislike <?= ($reactionsData['user_reaction'] === 'dislike') ? 'is-active' : '' ?>" 
                        id="btnDislike" 
                        data-article-id="<?= (int)$article['id'] ?>" 
                        data-type="dislike"
                        aria-label="Mark as unhelpful">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h3a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-3"></path>
                    </svg>
                    <span>Dislike</span>
                    <span class="reaction-counter" id="dislikeCounter"><?= (int)$reactionsData['dislikes'] ?></span>
                </button>

                <span class="reaction-feedback-msg" id="reactionFeedback" style="display:none;"></span>
            </div>
        </div>

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
            <strong style="color:#ffffff;">Editorial Transparency & Policy Note:</strong> At <?= SITE_NAME ?>, our mission is to deliver truthful, carefully vetted career insights. All job postings and guides are verified against official company releases. For additional questions or to report out-of-date information, please visit our <a href="<?= BASE_URL ?>/contact">Contact Page</a>.
        </div>

        <?php if (is_section_visible('article_comments')): ?>
        <?php
        // Pre-fill author info if logged in as admin for smooth user testing
        $defaultCommName = '';
        $defaultCommEmail = '';
        if (is_admin_logged_in() && !empty($_SESSION['admin_name'])) {
            $defaultCommName = $_SESSION['admin_name'];
            $defaultCommEmail = $_SESSION['admin_email'] ?? '';
        }
        ?>
        <!-- ================= Community Comments Section ================= -->
        <section class="article-comments-wrap" id="comments">
            <div class="comments-header">
                <h3 class="comments-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary);">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Discussion & Reader Comments</span>
                    <span class="comments-count-badge" id="sectionCommentsBadge"><?= $commentsCount ?></span>
                </h3>
            </div>

            <!-- Comment Submission Form Card -->
            <div class="comment-form-card">
                <h3>Leave a Comment or Question</h3>
                <p>Share your perspective, ask for advice, or contribute to this career guide. (Your email will remain private).</p>

                <div id="commentFormAlert" style="display:none; margin-bottom:1.25rem;"></div>

                <?php if (!empty($_SESSION['flash_messages'])): ?>
                    <div style="margin-bottom:1.25rem;">
                        <?= display_flash_messages() ?>
                    </div>
                <?php endif; ?>

                <form id="articleCommentForm" method="POST" action="<?= get_article_url($article['slug']) ?>#comments">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                    <!-- Anti-spam Honeypot (Positioned off-screen, autofill-safe) -->
                    <input type="text" name="comments_bot_trap" value="" tabindex="-1" autocomplete="new-password" style="position:absolute; left:-9999px; opacity:0; pointer-events:none;" aria-hidden="true">

                    <div class="comment-form-grid">
                        <div class="comment-form-group">
                            <label for="comment_user_name">Full Name <span style="color:#e11d48;">*</span></label>
                            <input type="text" id="comment_user_name" name="user_name" value="<?= h($defaultCommName) ?>" class="comment-form-input" placeholder="e.g. Sarah Jenkins" required maxlength="80">
                        </div>
                        <div class="comment-form-group">
                            <label for="comment_user_email">Email Address <span style="font-size:0.8rem; color:var(--text-muted); font-weight:normal;">(optional)</span></label>
                            <input type="email" id="comment_user_email" name="user_email" value="<?= h($defaultCommEmail) ?>" class="comment-form-input" placeholder="e.g. sarah@example.com" maxlength="120">
                        </div>
                    </div>

                    <div class="comment-form-group" style="margin-bottom:1.5rem;">
                        <label for="comment_text">Your Comment <span style="color:#e11d48;">*</span></label>
                        <textarea id="comment_text" name="comment_text" class="comment-form-textarea" placeholder="Share your experience, interview questions, or feedback about this guide..." required maxlength="2500"></textarea>
                    </div>

                    <button type="submit" name="submit_comment" class="comment-btn-submit" id="btnSubmitComment">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        <span>Post Comment</span>
                    </button>
                </form>
            </div>

            <!-- Published Comments List -->
            <div class="comment-items-list" id="commentsList">
                <?php if (empty($articleComments)): ?>
                    <div class="comments-empty-state" id="commentsEmptyState">
                        <span class="comments-empty-icon">💬</span>
                        <h4 style="margin:0 0 0.35rem; font-size:1.1rem; color:var(--secondary); font-weight:700;">No comments yet</h4>
                        <p style="margin:0; font-size:0.9rem;">Be the first reader to share your feedback or ask a question!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($articleComments as $comm): ?>
                        <?php 
                        $initials = '';
                        $parts = preg_split('/\s+/', trim($comm['user_name']));
                        if (!empty($parts[0])) $initials .= mb_substr($parts[0], 0, 1);
                        if (!empty($parts[1])) $initials .= mb_substr($parts[1], 0, 1);
                        if (empty($initials)) $initials = 'U';
                        ?>
                        <div class="comment-single-item" id="comment-<?= (int)$comm['id'] ?>">
                            <div class="comment-avatar-bubble">
                                <?= h(strtoupper($initials)) ?>
                            </div>
                            <div class="comment-item-body">
                                <div class="comment-item-header">
                                    <span class="comment-item-author"><?= h($comm['user_name']) ?></span>
                                    <span class="comment-item-badge">Verified Reader</span>
                                    <span class="comment-item-time"><?= date('M j, Y \a\t g:i A', strtotime($comm['created_at'])) ?></span>
                                </div>
                                <div class="comment-item-content">
                                    <?= nl2br(h($comm['comment_text'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        </article>

        <!-- RIGHT SIDEBAR COLUMN (Picture 1 Match) -->
        <aside class="article-sidebar-rail">

            <!-- 2. RELATED Section -->
            <?php if (!empty($sidebarRelated)): ?>
            <div class="sidebar-widget-block">
                <h3 class="sidebar-widget-title">RELATED</h3>
                <div class="sidebar-related-stack">
                    <?php foreach ($sidebarRelated as $rel): ?>
                    <?php $relThumb = get_article_thumbnail_url($rel); ?>
                    <a href="<?= get_article_url($rel['slug']) ?>" class="sidebar-related-card">
                        <img src="<?= h($relThumb) ?>" alt="<?= h($rel['title']) ?>" class="sidebar-related-img" loading="lazy">
                        <div class="sidebar-related-details">
                            <h4 class="sidebar-related-head"><?= h($rel['title']) ?></h4>
                            <span class="sidebar-related-time"><?= date('M j, Y', strtotime($rel['published_at'])) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 3. TOPICS Section -->
            <?php if (!empty($sidebarTopics)): ?>
            <div class="sidebar-widget-block">
                <h3 class="sidebar-widget-title">TOPICS</h3>
                <div class="sidebar-topics-cloud">
                    <?php foreach ($sidebarTopics as $topic): ?>
                    <a href="<?= get_category_url($topic['slug']) ?>" class="sidebar-topic-pill">
                        <?= h($topic['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
