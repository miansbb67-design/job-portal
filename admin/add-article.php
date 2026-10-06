<?php
/**
 * Admin Panel - New Article Composer
 * Pixel-perfect match for Screenshots 3, 4, 5: PineCastTV Article Creator
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires posts.create or jobs.create
if (!is_super_admin() && !has_permission('posts.create') && !has_permission('jobs.create')) {
    require_permission('posts.create');
}

$db = getDBConnection();
$errors = [];

// Form values
$title = '';
$slug = '';
$excerpt = '';
$content = '';
$video_type = 'none';
$video_url = '';
$category_id = '';
$author_id = $currentUser['id'] ?? 1;
$status = 'draft';
$is_featured = 0;
$is_trending = 0;
$is_recommended = 0;
$tags = '';
$image_alt = '';
$seo_title = '';
$meta_description = '';
$keywords = '';
$canonical_url = '';
$robots_meta = 'Automatic (index when published & not thin)';
$twitter_card = 'summary_large_image';
$og_title = '';
$og_description = '';
$og_image = '';

// Job fields (if job circular)
$is_job_posting = isset($_GET['type']) && $_GET['type'] === 'job' ? 1 : 0;
$job_company = '';
$job_location = '';
$job_type = 'Full-time';
$job_salary = '';
$job_deadline = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = "Security validation failed. Please refresh the page and try again.";
    } else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $video_type = trim($_POST['video_type'] ?? 'none');
        $video_url = trim($_POST['video_url'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $author_id = (int)($_POST['author_id'] ?? $currentUser['id']);
        $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'scheduled']) ? $_POST['status'] : 'draft';
        $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
        $is_trending = !empty($_POST['is_trending']) ? 1 : 0;
        $is_recommended = !empty($_POST['is_recommended']) ? 1 : 0;
        $tags = trim($_POST['tags'] ?? '');
        $image_alt = trim($_POST['image_alt'] ?? '');

        // SEO & Social
        $seo_title = trim($_POST['seo_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');
        $canonical_url = trim($_POST['canonical_url'] ?? '');
        $robots_meta = trim($_POST['robots_meta'] ?? 'Automatic (index when published & not thin)');
        $twitter_card = trim($_POST['twitter_card'] ?? 'summary_large_image');
        $og_title = trim($_POST['og_title'] ?? '');
        $og_description = trim($_POST['og_description'] ?? '');
        $og_image = trim($_POST['og_image'] ?? '');

        // Job details
        $is_job_posting = !empty($_POST['is_job_posting']) ? 1 : 0;
        $job_company = trim($_POST['job_company'] ?? '');
        $job_location = trim($_POST['job_location'] ?? '');
        $job_type = trim($_POST['job_type'] ?? '');
        $job_salary = trim($_POST['job_salary'] ?? '');
        $job_deadline = !empty($_POST['job_deadline']) ? $_POST['job_deadline'] : null;

        // Auto-generate slug if empty
        if (empty($slug) && !empty($title)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        }

        // Validation
        if (empty($title)) {
            $errors[] = "Please provide an article title.";
        }
        if (empty($content)) {
            $errors[] = "Article content cannot be empty.";
        }
        if ($category_id <= 0) {
            $errors[] = "Please select a category.";
        }

        // Check slug uniqueness
        if (!empty($slug)) {
            $slugCheck = $db->prepare("SELECT id FROM articles WHERE slug = ? LIMIT 1");
            $slugCheck->execute([$slug]);
            if ($slugCheck->fetch()) {
                $slug .= '-' . time();
            }
        }

        // Handle Featured Image Upload
        $featured_image = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['featured_image']['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowedMimes)) {
                $ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
                $newName = 'art_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadDir . $newName)) {
                    $featured_image = $newName;
                }
            } else {
                $errors[] = "Invalid image format. Only JPG, PNG, WebP, and GIF are allowed.";
            }
        } elseif (!empty($_POST['existing_featured_image'])) {
            $featured_image = trim($_POST['existing_featured_image']);
        }

        // Handle Thumbnail Image Upload
        $thumbnail_image = null;
        if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === UPLOAD_ERR_OK) {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['thumbnail_image']['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowedMimes)) {
                $ext = pathinfo($_FILES['thumbnail_image']['name'], PATHINFO_EXTENSION);
                $newName = 'thumb_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
                $uploadDir = __DIR__ . '/../uploads/';
                if (move_uploaded_file($_FILES['thumbnail_image']['tmp_name'], $uploadDir . $newName)) {
                    $thumbnail_image = $newName;
                }
            }
        } elseif (!empty($_POST['existing_thumbnail_image'])) {
            $thumbnail_image = trim($_POST['existing_thumbnail_image']);
        }

        if (empty($errors)) {
            $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;

            $insertSql = "
                INSERT INTO articles (
                    title, slug, excerpt, content, featured_image, thumbnail_image, image_alt,
                    video_type, video_url, category_id, author_id, status, is_featured, is_trending, is_recommended,
                    tags, seo_title, meta_description, keywords, canonical_url, robots_meta, twitter_card,
                    og_title, og_description, og_image, is_job_posting, job_company, job_location, job_type, job_salary, job_deadline,
                    published_at, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, NOW(), NOW()
                )
            ";

            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                $title, $slug, $excerpt, $content, $featured_image, $thumbnail_image, $image_alt,
                $video_type, $video_url, $category_id, $author_id, $status, $is_featured, $is_trending, $is_recommended,
                $tags, $seo_title, $meta_description, $keywords, $canonical_url, $robots_meta, $twitter_card,
                $og_title, $og_description, $og_image, $is_job_posting, $job_company, $job_location, $job_type, $job_salary, $job_deadline,
                $published_at
            ]);

            $newArticleId = $db->lastInsertId();
            log_activity('create', 'posts', "Created article '{$title}'", $newArticleId);
            set_flash('success', "Article published successfully!");
            header('Location: ' . ADMIN_URL . '/articles.php');
            exit;
        }
    }
}

// Fetch categories
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch authors
$authors = $db->query("SELECT id, name, username FROM admins ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = "New Article";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="composer-page-wrap">
    <?php if (!empty($errors)): ?>
        <div class="alert-danger-box">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= h($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= ADMIN_URL ?>/add-article.php" method="POST" enctype="multipart/form-data" id="articleForm" class="composer-grid">
        <?= csrf_field(); ?>

        <!-- LEFT COLUMN (~68% width): Main Form Elements (Screenshots 3, 4, 5) -->
        <div class="composer-main-col">
            <!-- CARD 1: Title, Slug & Short Description -->
            <div class="composer-card">
                <div class="form-group-item">
                    <label class="field-label" for="articleTitle">Title</label>
                    <input type="text" name="title" id="articleTitle" class="form-input-lg" placeholder="An honest, descriptive title" value="<?= h($title) ?>" required autocomplete="off">
                </div>

                <!-- Slug with URL Prefix -->
                <div class="slug-preview-row">
                    <span class="slug-prefix"><?= BASE_URL ?>/article/</span>
                    <input type="text" name="slug" id="articleSlug" class="form-input-slug" placeholder="auto-generated" value="<?= h($slug) ?>">
                </div>

                <div class="form-group-item" style="margin-top:1.25rem;">
                    <label class="field-label" for="articleExcerpt">Short description</label>
                    <textarea name="excerpt" id="articleExcerpt" class="form-textarea-short" rows="3" placeholder="One or two sentences shown in listings and search results."><?= h($excerpt) ?></textarea>
                </div>
            </div>

            <!-- CARD 2: Video Selector (Screenshot 3) -->
            <div class="composer-card">
                <label class="field-label" style="margin-bottom:0.75rem;">Video</label>
                <div class="video-options-row">
                    <label class="video-pill <?= ($video_type === 'none') ? 'active' : '' ?>">
                        <input type="radio" name="video_type" value="none" <?= ($video_type === 'none') ? 'checked' : '' ?> onchange="handleVideoOptionChange(this)">
                        <span>No video</span>
                    </label>
                    <label class="video-pill <?= ($video_type === 'youtube') ? 'active' : '' ?>">
                        <input type="radio" name="video_type" value="youtube" <?= ($video_type === 'youtube') ? 'checked' : '' ?> onchange="handleVideoOptionChange(this)">
                        <span>YouTube</span>
                    </label>
                    <label class="video-pill <?= ($video_type === 'vimeo') ? 'active' : '' ?>">
                        <input type="radio" name="video_type" value="vimeo" <?= ($video_type === 'vimeo') ? 'checked' : '' ?> onchange="handleVideoOptionChange(this)">
                        <span>Vimeo</span>
                    </label>
                    <label class="video-pill <?= ($video_type === 'self') ? 'active' : '' ?>">
                        <input type="radio" name="video_type" value="self" <?= ($video_type === 'self') ? 'checked' : '' ?> onchange="handleVideoOptionChange(this)">
                        <span>Self-hosted</span>
                    </label>
                    <label class="video-pill <?= ($video_type === 'embed') ? 'active' : '' ?>">
                        <input type="radio" name="video_type" value="embed" <?= ($video_type === 'embed') ? 'checked' : '' ?> onchange="handleVideoOptionChange(this)">
                        <span>External embed</span>
                    </label>
                </div>

                <!-- Dynamic Video URL Input Container -->
                <div id="videoUrlContainer" style="display: <?= ($video_type !== 'none') ? 'block' : 'none' ?>; margin-top:1rem;">
                    <label class="field-label" for="videoUrlInput">Video URL or Embed Code</label>
                    <input type="text" name="video_url" id="videoUrlInput" class="form-input" placeholder="e.g. https://www.youtube.com/watch?v=... or video embed code" value="<?= h($video_url) ?>">
                </div>
            </div>

            <!-- CARD 3: Article Content with Rich Text Toolbar (Screenshot 3, 4) -->
            <div class="composer-card">
                <div class="content-header-row">
                    <label class="field-label" style="margin-bottom:0.25rem;">Article content</label>
                    <p class="content-subtext">
                        Original, meaningful content matters for readers, search and AdSense review. Thin pages (under 100 words without video) are automatically set to noindex and show no ads.
                    </p>
                </div>

                <!-- Rich Text Toolbar -->
                <div class="rich-editor-toolbar" id="editorToolbar">
                    <!-- Heading Selector -->
                    <select class="toolbar-select" id="toolFormatSelect" onchange="applyBlockFormat(this.value)">
                        <option value="p">Normal</option>
                        <option value="h2">Heading 2</option>
                        <option value="h3">Heading 3</option>
                        <option value="h4">Heading 4</option>
                    </select>

                    <span class="toolbar-sep"></span>

                    <!-- Formatting buttons (Screenshot 1 matching icons) -->
                    <button type="button" class="tool-btn" data-action="bold" title="Bold (Ctrl+B)"><b>B</b></button>
                    <button type="button" class="tool-btn" data-action="italic" title="Italic (Ctrl+I)"><i>I</i></button>
                    <button type="button" class="tool-btn" data-action="underline" title="Underline (Ctrl+U)"><u>U</u></button>
                    <button type="button" class="tool-btn" data-action="strike" title="Strikethrough"><s>S</s></button>

                    <span class="toolbar-sep"></span>

                    <button type="button" class="tool-btn" data-action="quote" title="Quote block">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"></path><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"></path></svg>
                    </button>
                    <button type="button" class="tool-btn" data-action="code" title="Code snippet">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                    </button>

                    <span class="toolbar-sep"></span>

                    <button type="button" class="tool-btn" data-action="ol" title="Ordered list">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="6" x2="21" y2="6"></line><line x1="10" y1="12" x2="21" y2="12"></line><line x1="10" y1="18" x2="21" y2="18"></line><path d="M4 6h1v4"></path><path d="M4 10h2"></path><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"></path></svg>
                    </button>
                    <button type="button" class="tool-btn" data-action="ul" title="Unordered list">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </button>
                    <button type="button" class="tool-btn" data-action="align" title="Align text">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>
                    </button>

                    <span class="toolbar-sep"></span>

                    <button type="button" class="tool-btn" data-action="link" title="Insert link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    </button>
                    <button type="button" class="tool-btn" data-action="image" title="Media Library (Insert Image)">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </button>
                    <button type="button" class="tool-btn" data-action="video" title="Insert Video Embed">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="2" y1="7" x2="7" y2="7"></line><line x1="2" y1="17" x2="7" y2="17"></line><line x1="17" y1="17" x2="22" y2="17"></line><line x1="17" y1="7" x2="22" y2="7"></line></svg>
                    </button>
                    <button type="button" class="tool-btn" data-action="clean" title="Clear formatting">T<sub>x</sub></button>

                    <div style="margin-left:auto; display:flex; gap:0.4rem;">
                        <button type="button" class="tool-btn-template" id="btnJobTemplate" title="Insert pre-structured Job template">+ Job Template</button>
                        <button type="button" class="tool-btn-template" id="btnGuideTemplate" title="Insert pre-structured Guide template">+ Guide Template</button>
                    </div>
                </div>

                <!-- Textarea Editor -->
                <textarea name="content" id="articleContent" class="form-textarea-content" rows="18" placeholder="Compose high quality, original content here..."><?= h($content) ?></textarea>

                <!-- Word counter bar -->
                <div class="composer-word-stats">
                    <span>Words: <strong id="wordCounter">0</strong></span>
                    <span>Characters: <strong id="charCounter">0</strong></span>
                    <span>Estimated Read: <strong id="readTimeCounter">1</strong> min</span>
                </div>
            </div>

            <!-- CARD 4: SEO, Social / Open Graph & Advanced Tabs (Screenshots 4, 5) -->
            <div class="composer-card tabbed-settings-card">
                <!-- Navigation Tabs -->
                <div class="settings-tabs-header">
                    <button type="button" class="tab-btn active" data-tab="tabSeo" onclick="switchSettingsTab('tabSeo', this)">
                        SEO
                    </button>
                    <button type="button" class="tab-btn" data-tab="tabSocial" onclick="switchSettingsTab('tabSocial', this)">
                        Social / Open Graph
                    </button>
                    <button type="button" class="tab-btn" data-tab="tabAdvanced" onclick="switchSettingsTab('tabAdvanced', this)">
                        Advanced
                    </button>
                </div>

                <!-- TAB 1: SEO Pane (Screenshot 4, 5) -->
                <div class="settings-tab-pane active" id="tabSeo">
                    <div class="form-group-item">
                        <label class="field-label" for="seoTitle">SEO title (<span style="color:#64748b; font-weight:400;">&le; 60 chars recommended</span>)</label>
                        <input type="text" name="seo_title" id="seoTitle" class="form-input" placeholder="Defaults to the title" value="<?= h($seo_title) ?>">
                    </div>

                    <div class="form-group-item">
                        <label class="field-label" for="metaDesc">Meta description (<span style="color:#64748b; font-weight:400;">50–160 chars</span>)</label>
                        <textarea name="meta_description" id="metaDesc" class="form-textarea-short" rows="3" placeholder="A compelling summary for search results..."><?= h($meta_description) ?></textarea>
                    </div>

                    <!-- 2-column row: Focus keyword & Canonical URL -->
                    <div class="form-row-2col">
                        <div class="form-group-item">
                            <label class="field-label" for="seoKeywords">Focus keyword</label>
                            <input type="text" name="keywords" id="seoKeywords" class="form-input" placeholder="e.g. Remote Software Engineer" value="<?= h($keywords) ?>">
                        </div>
                        <div class="form-group-item">
                            <label class="field-label" for="canonicalUrl">Canonical URL</label>
                            <input type="text" name="canonical_url" id="canonicalUrl" class="form-input" placeholder="Leave empty for self-canonical" value="<?= h($canonical_url) ?>">
                        </div>
                    </div>

                    <!-- 2-column row: Robots & Twitter card -->
                    <div class="form-row-2col">
                        <div class="form-group-item">
                            <label class="field-label" for="robotsMeta">Robots</label>
                            <select name="robots_meta" id="robotsMeta" class="form-select">
                                <option value="Automatic (index when published & not thin)" <?= ($robots_meta === 'Automatic (index when published & not thin)') ? 'selected' : '' ?>>
                                    Automatic (index when published &amp; not thin)
                                </option>
                                <option value="Index, Follow" <?= ($robots_meta === 'Index, Follow') ? 'selected' : '' ?>>Index, Follow</option>
                                <option value="Noindex, Follow" <?= ($robots_meta === 'Noindex, Follow') ? 'selected' : '' ?>>Noindex, Follow</option>
                                <option value="Noindex, Nofollow" <?= ($robots_meta === 'Noindex, Nofollow') ? 'selected' : '' ?>>Noindex, Nofollow</option>
                            </select>
                        </div>
                        <div class="form-group-item">
                            <label class="field-label" for="twitterCard">Twitter card</label>
                            <select name="twitter_card" id="twitterCard" class="form-select">
                                <option value="summary_large_image" <?= ($twitter_card === 'summary_large_image') ? 'selected' : '' ?>>summary_large_image</option>
                                <option value="summary" <?= ($twitter_card === 'summary') ? 'selected' : '' ?>>summary</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Social / Open Graph Pane -->
                <div class="settings-tab-pane" id="tabSocial" style="display:none;">
                    <div class="form-group-item">
                        <label class="field-label" for="ogTitle">Open Graph Title</label>
                        <input type="text" name="og_title" id="ogTitle" class="form-input" placeholder="Defaults to SEO title or article title" value="<?= h($og_title) ?>">
                    </div>
                    <div class="form-group-item">
                        <label class="field-label" for="ogDesc">Open Graph Description</label>
                        <textarea name="og_description" id="ogDesc" class="form-textarea-short" rows="3" placeholder="Defaults to meta description"><?= h($og_description) ?></textarea>
                    </div>
                    <div class="form-group-item">
                        <label class="field-label" for="ogImage">Open Graph Image URL</label>
                        <input type="text" name="og_image" id="ogImage" class="form-input" placeholder="Leave empty to use featured image" value="<?= h($og_image) ?>">
                    </div>
                </div>

                <!-- TAB 3: Advanced Pane -->
                <div class="settings-tab-pane" id="tabAdvanced" style="display:none;">
                    <div class="form-group-item">
                        <label class="field-label">Schema Structured Data Type</label>
                        <select name="schema_type" class="form-select">
                            <option value="Article">Article</option>
                            <option value="NewsArticle">NewsArticle</option>
                            <option value="JobPosting">JobPosting</option>
                            <option value="TechArticle">TechArticle</option>
                        </select>
                    </div>
                    <div class="form-group-item">
                        <label class="field-label">Custom Head Scripts or Tracking Pixels</label>
                        <textarea name="custom_head_code" class="form-textarea-short" rows="3" placeholder="<!-- Custom script or link tags for this article -->"></textarea>
                    </div>
                </div>
            </div>

            <!-- CARD 5: Optional Job Circular Accordion (Preserves Job Portal Functionality!) -->
            <div class="composer-card">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <strong style="color:var(--admin-text); font-size:0.95rem;">Job Vacancy / Circular Details</strong>
                        <p style="color:var(--admin-text-muted); font-size:0.8rem; margin-top:2px;">Enable if this article represents an official hiring notice.</p>
                    </div>
                    <label class="switch-toggle">
                        <input type="checkbox" name="is_job_posting" id="isJobPosting" value="1" <?= $is_job_posting ? 'checked' : '' ?>>
                        <span class="switch-slider"></span>
                    </label>
                </div>

                <div id="jobPostingFields" style="display: <?= $is_job_posting ? 'block' : 'none' ?>; margin-top:1.25rem; padding-top:1rem; border-top:1px solid #e2e8f0;">
                    <div class="form-row-2col">
                        <div class="form-group-item">
                            <label class="field-label">Hiring Company</label>
                            <input type="text" name="job_company" class="form-input" placeholder="e.g. Acme Corp" value="<?= h($job_company) ?>">
                        </div>
                        <div class="form-group-item">
                            <label class="field-label">Job Location</label>
                            <input type="text" name="job_location" class="form-input" placeholder="e.g. Remote / New York" value="<?= h($job_location) ?>">
                        </div>
                    </div>
                    <div class="form-row-2col">
                        <div class="form-group-item">
                            <label class="field-label">Job Type</label>
                            <select name="job_type" class="form-select">
                                <option value="Full-time" <?= ($job_type === 'Full-time') ? 'selected' : '' ?>>Full-time</option>
                                <option value="Part-time" <?= ($job_type === 'Part-time') ? 'selected' : '' ?>>Part-time</option>
                                <option value="Contract" <?= ($job_type === 'Contract') ? 'selected' : '' ?>>Contract</option>
                                <option value="Remote" <?= ($job_type === 'Remote') ? 'selected' : '' ?>>Remote</option>
                            </select>
                        </div>
                        <div class="form-group-item">
                            <label class="field-label">Estimated Salary / Compensation</label>
                            <input type="text" name="job_salary" class="form-input" placeholder="e.g. $80,000 - $110,000 / yr" value="<?= h($job_salary) ?>">
                        </div>
                    </div>
                    <div class="form-group-item">
                        <label class="field-label">Application Deadline</label>
                        <input type="date" name="job_deadline" class="form-input" value="<?= h($job_deadline ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN (~30% width): Sidebar Metadata (Screenshots 3, 4, 5) -->
        <div class="composer-side-col">
            <!-- CARD 1: Publish (Screenshot 3) -->
            <div class="composer-card">
                <h3 class="side-card-title">Publish</h3>

                <div class="form-group-item">
                    <label class="field-label" for="articleStatus">Status</label>
                    <select name="status" id="articleStatus" class="form-select">
                        <option value="draft" <?= ($status === 'draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= ($status === 'published') ? 'selected' : '' ?>>Published</option>
                        <option value="scheduled" <?= ($status === 'scheduled') ? 'selected' : '' ?>>Scheduled</option>
                    </select>
                </div>

                <div class="form-group-item">
                    <label class="field-label" for="articleAuthor">Author</label>
                    <select name="author_id" id="articleAuthor" class="form-select">
                        <?php foreach ($authors as $aut): ?>
                            <?php 
                                $aName = !empty($aut['name']) && $aut['name'] !== 'Administrator' ? $aut['name'] : $aut['username'];
                                $shortAuthor = explode(' ', trim($aName))[0];
                            ?>
                            <option value="<?= $aut['id'] ?>" <?= ($author_id === (int)$aut['id']) ? 'selected' : '' ?>>
                                <?= h($shortAuthor) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Checkboxes: Featured, Trending, Recommended -->
                <div class="checkbox-group-stack">
                    <label class="custom-checkbox-row">
                        <input type="checkbox" name="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?>>
                        <span>Featured</span>
                    </label>
                    <label class="custom-checkbox-row">
                        <input type="checkbox" name="is_trending" value="1" <?= $is_trending ? 'checked' : '' ?>>
                        <span>Trending</span>
                    </label>
                    <label class="custom-checkbox-row">
                        <input type="checkbox" name="is_recommended" value="1" <?= $is_recommended ? 'checked' : '' ?>>
                        <span>Recommended</span>
                    </label>
                </div>

                <!-- Big Purple Save Button (Screenshot 3) -->
                <button type="submit" class="btn-sidebar-save">
                    Save
                </button>
            </div>

            <!-- CARD 2: Category (Screenshot 3) -->
            <div class="composer-card">
                <h3 class="side-card-title">Category</h3>

                <div class="form-group-item">
                    <select name="category_id" id="articleCategory" class="form-select" required>
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($category_id === (int)$cat['id']) ? 'selected' : '' ?>>
                                <?= h($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group-item">
                    <label class="field-label" style="font-size:0.8rem; color:#64748b;">Subcategory</label>
                    <select name="subcategory_id" class="form-select">
                        <option value="">— None —</option>
                    </select>
                </div>

                <div class="form-group-item" style="margin-top:1rem;">
                    <label class="field-label" for="articleTags">Tags (comma separated)</label>
                    <input type="text" name="tags" id="articleTags" class="form-input" placeholder="travel, budget, guide" value="<?= h($tags) ?>">
                </div>
            </div>

            <!-- CARD 3: Featured image (Screenshot 4, 5) -->
            <div class="composer-card">
                <?php
                $hasFeatured = !empty($featured_image);
                ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h3 class="side-card-title" style="margin:0;">Featured image</h3>
                    <button type="button" id="btnRemoveFeaturedHeader" class="btn-remove-image-text" style="<?= !$hasFeatured ? 'display:none;' : '' ?>" onclick="removeFeaturedImage(event)">
                        Remove
                    </button>
                </div>

                <!-- Image Box / Upload Preview Area -->
                <div class="featured-image-box" id="featuredImageBox">
                    <button type="button" id="btnRemoveFeaturedFloat" class="image-remove-badge" style="<?= !$hasFeatured ? 'display:none;' : '' ?>" onclick="removeFeaturedImage(event)" title="Remove Image">&times;</button>
                    <div id="imagePlaceholder" class="image-upload-empty" onclick="document.getElementById('featuredImageInput').click();">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                    <img id="featuredImagePreview" src="" alt="Preview" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:8px;" onclick="document.getElementById('featuredImageInput').click();">
                </div>

                <!-- Hidden file input & chosen media reference -->
                <input type="file" name="featured_image" id="featuredImageInput" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none;" onchange="previewSelectedImage(this, 'featuredImagePreview', 'imagePlaceholder')">
                <input type="hidden" name="existing_featured_image" id="existingFeaturedImage" value="<?= h($featured_image ?? '') ?>">

                <!-- Choose / Upload Buttons Row -->
                <div style="display:flex; gap:0.5rem; margin-top:0.65rem;">
                    <button type="button" class="btn-choose-upload" style="flex:1;" onclick="document.getElementById('featuredImageInput').click();">
                        Upload
                    </button>
                    <button type="button" class="btn-choose-upload" style="flex:1; color:#059669; border-color:#a7f3d0; background:#ecfdf5; font-weight:600;" onclick="selectFeaturedFromMedia()">
                        Media Library
                    </button>
                    <button type="button" id="btnRemoveFeatured" class="btn-choose-upload btn-remove-image" style="flex:1; <?= !$hasFeatured ? 'display:none;' : '' ?>" onclick="removeFeaturedImage(event)">
                        Remove
                    </button>
                </div>

                <div class="form-group-item" style="margin-top:1rem;">
                    <label class="field-label" for="imageAlt">Alt text</label>
                    <input type="text" name="image_alt" id="imageAlt" class="form-input" placeholder="Describe the image" value="<?= h($image_alt) ?>">
                </div>
            </div>

            <!-- CARD 4: Thumbnail (optional, 16:9) (Screenshot 4, 5) -->
            <div class="composer-card">
                <?php
                $hasThumb = !empty($thumbnail_image);
                ?>
                <h3 class="side-card-title">Thumbnail <span style="font-weight:400; color:#64748b; font-size:0.8rem;">(optional, 16:9)</span></h3>

                <input type="hidden" name="existing_thumbnail_image" id="existingThumbnailImage" value="<?= h($thumbnail_image ?? '') ?>">
                <div class="thumbnail-upload-row">
                    <div class="thumb-mini-box" id="thumbMiniBox" onclick="document.getElementById('thumbImageInput').click();">
                        <img id="thumbMiniPreview" src="" alt="Thumb" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:6px;">
                    </div>
                    <button type="button" class="btn-choose-mini" onclick="document.getElementById('thumbImageInput').click();">
                        Upload
                    </button>
                    <button type="button" class="btn-choose-mini" style="color:#059669; border-color:#a7f3d0; background:#ecfdf5;" onclick="selectThumbnailFromMedia()">
                        Media Library
                    </button>
                    <button type="button" id="btnRemoveThumb" class="btn-choose-mini btn-remove-image" style="<?= !$hasThumb ? 'display:none;' : '' ?>" onclick="removeThumbnailImage(event)">
                        Remove
                    </button>
                    <input type="file" name="thumbnail_image" id="thumbImageInput" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none;" onchange="previewSelectedImage(this, 'thumbMiniPreview')">
                </div>

                <p class="thumb-subnote">
                    Falls back to the featured image, then the YouTube thumbnail.
                </p>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
