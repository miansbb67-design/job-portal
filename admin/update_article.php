<?php
/**
 * Admin Panel - Update Article Processor
 * Handles POST requests from Summernote Edit Article composer,
 * validates data, sanitizes HTML, saves to MySQL database, and redirects with flash message.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

$db = getDBConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
if ($id <= 0) {
    set_flash('error', 'Invalid article ID provided for update.');
    header('Location: ' . ADMIN_URL . '/articles.php');
    exit;
}

// Fetch existing article
$stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    set_flash('error', 'Article not found.');
    header('Location: ' . ADMIN_URL . '/articles.php');
    exit;
}

// Authorization check: Requires jobs.edit or posts.edit
$isJob = !empty($article['is_job_posting']);
if ($isJob) {
    require_permission('jobs.edit');
} else {
    require_permission('posts.edit');
}

// Handle GET request: If accessed via GET, redirect cleanly to edit page
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . ADMIN_URL . '/edit-article.php?id=' . $id);
    exit;
}

// Validate CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    set_flash('error', 'Security session expired. Please try submitting again.');
    header('Location: ' . ADMIN_URL . '/edit-article.php?id=' . $id);
    exit;
}

// Extract & sanitize POST fields
$title = trim($_POST['title'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
// Summernote rich HTML content (Sanitized to preserve formatting, images, alignments while stripping dangerous scripts)
$rawContent = $_POST['content'] ?? '';
$content = sanitize_article_html(trim($rawContent));

$video_type = trim($_POST['video_type'] ?? 'none');
$video_url = trim($_POST['video_url'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$author_id = (int)($_POST['author_id'] ?? $article['author_id']);
$status = in_array($_POST['status'] ?? '', ['draft', 'published', 'scheduled']) ? $_POST['status'] : 'draft';
$is_featured = !empty($_POST['is_featured']) ? 1 : 0;
$is_trending = !empty($_POST['is_trending']) ? 1 : 0;
$is_recommended = !empty($_POST['is_recommended']) ? 1 : 0;
$tags = trim($_POST['tags'] ?? '');
$image_alt = trim($_POST['image_alt'] ?? '');

// SEO & Social
$seo_title = trim($_POST['seo_title'] ?? '');
$meta_description = trim($_POST['meta_description'] ?? '');
if (!empty($meta_description)) {
    $meta_description = clean_seo_description($meta_description, 155);
} elseif (!empty($excerpt)) {
    $meta_description = clean_seo_description($excerpt, 155);
}
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

// Validation checks
$errors = [];
if (empty($title)) {
    $errors[] = "Please provide an article title.";
}
if (empty($content)) {
    $errors[] = "Article content cannot be empty.";
}
if ($category_id <= 0) {
    $errors[] = "Please select a category.";
}

// Slug validation & uniqueness
if (empty($slug) && !empty($title)) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
}
if (!empty($slug)) {
    $slugCheck = $db->prepare("SELECT id FROM articles WHERE slug = ? AND id != ? LIMIT 1");
    $slugCheck->execute([$slug, $id]);
    if ($slugCheck->fetch()) {
        $slug .= '-' . time();
    }
}

// Preserve existing images or handle new file uploads
$featured_image = $article['featured_image'];
$thumbnail_image = $article['thumbnail_image'];

// Featured Image upload
if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $imgResult = upload_featured_image($_FILES['featured_image']);
    if ($imgResult['success'] && $imgResult['path']) {
        $featured_image = $imgResult['path'];
    } elseif (!$imgResult['success']) {
        $errors[] = "Featured Image: " . $imgResult['error'];
    }
} elseif (isset($_POST['existing_featured_image'])) {
    $featured_image = trim($_POST['existing_featured_image']);
}

// Thumbnail Image upload
if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $thumbResult = upload_featured_image($_FILES['thumbnail_image']);
    if ($thumbResult['success'] && $thumbResult['path']) {
        $thumbnail_image = $thumbResult['path'];
    } elseif (!$thumbResult['success']) {
        $errors[] = "Thumbnail Image: " . $thumbResult['error'];
    }
} elseif (isset($_POST['existing_thumbnail_image'])) {
    $thumbnail_image = trim($_POST['existing_thumbnail_image']);
}

// If validation errors exist, flash error and redirect back to Edit page
if (!empty($errors)) {
    set_flash('error', implode(' ', $errors));
    header('Location: ' . ADMIN_URL . '/edit-article.php?id=' . $id);
    exit;
}

// Published timestamp logic
$publishedSql = "";
if ($status === 'published' && empty($article['published_at'])) {
    $publishedSql = ", published_at = NOW()";
}

// Execute MySQL UPDATE
$updateSql = "
    UPDATE articles SET
        title = ?, slug = ?, excerpt = ?, content = ?,
        featured_image = ?, thumbnail_image = ?, image_alt = ?,
        video_type = ?, video_url = ?, category_id = ?, author_id = ?, status = ?,
        is_featured = ?, is_trending = ?, is_recommended = ?, tags = ?,
        seo_title = ?, meta_description = ?, keywords = ?, canonical_url = ?,
        robots_meta = ?, twitter_card = ?, og_title = ?, og_description = ?, og_image = ?,
        is_job_posting = ?, job_company = ?, job_location = ?, job_type = ?, job_salary = ?, job_deadline = ?,
        updated_at = NOW()
        {$publishedSql}
    WHERE id = ?
";

$updateStmt = $db->prepare($updateSql);
$updateStmt->execute([
    $title, $slug, $excerpt, $content,
    $featured_image, $thumbnail_image, $image_alt,
    $video_type, $video_url, $category_id, $author_id, $status,
    $is_featured, $is_trending, $is_recommended, $tags,
    $seo_title, $meta_description, $keywords, $canonical_url,
    $robots_meta, $twitter_card, $og_title, $og_description, $og_image,
    $is_job_posting, $job_company, $job_location, $job_type, $job_salary, $job_deadline,
    $id
]);

// Log activity and set success message
log_activity('edit', 'posts', "Updated article '{$title}' with Summernote formatted content", $id);
set_flash('success', "Article '{$title}' updated successfully with Summernote formatting!");

// Redirect to articles list (or edit page)
header('Location: ' . ADMIN_URL . '/articles.php');
exit;
