<?php
/**
 * Global Helper & Security Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth_functions.php';

/**
 * Escape HTML output to prevent Cross-Site Scripting (XSS)
 *
 * @param mixed $string
 * @return string
 */
function h($string) {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a URL-friendly clean slug
 *
 * @param string $text
 * @return string
 */
function slugify($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);

    return empty($text) ? 'n-a-' . time() : $text;
}

/**
 * CSRF Token Generator
 *
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token with timing attack resistance
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Render a hidden CSRF input field
 *
 * @return string
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . h($token) . '">';
}

/**
 * Set flash message in session
 *
 * @param string $type success|error|warning|info
 * @param string $message
 */
function set_flash($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => in_array($type, ['success', 'error', 'warning', 'info']) ? $type : 'info',
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash messages from session
 *
 * @param string|null $type success|error|warning|info (or null for all)
 * @return string
 */
function get_flash($type = null) {
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }
    $messages = [];
    foreach ($_SESSION['flash_messages'] as $key => $msg) {
        if ($type === null || $msg['type'] === $type) {
            $messages[] = $msg['message'];
            unset($_SESSION['flash_messages'][$key]);
        }
    }
    $_SESSION['flash_messages'] = array_values($_SESSION['flash_messages']);
    return implode('<br>', $messages);
}

/**
 * Display and clear all flash messages
 *
 * @return string
 */
function display_flash_messages() {
    if (empty($_SESSION['flash_messages'])) {
        return '';
    }

    $icons = [
        'success' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        'error'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        'warning' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        'info'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    ];

    $html = '<div class="flash-messages-container">';
    foreach ($_SESSION['flash_messages'] as $msg) {
        $rawType = $msg['type'] ?? 'info';
        $type = in_array($rawType, ['success', 'error', 'warning', 'info']) ? $rawType : 'info';
        $icon = $icons[$type] ?? $icons['info'];
        $text = h($msg['message']);
        $html .= "<div class=\"alert alert-{$type}\" role=\"alert\">";
        $html .= "<div class=\"alert-content\">";
        $html .= "<span class=\"alert-icon\">{$icon}</span>";
        $html .= "<span class=\"alert-text\">{$text}</span>";
        $html .= "</div>";
        $html .= "<button type=\"button\" class=\"alert-close\" onclick=\"const a=this.closest('.alert'); if(a){ a.style.opacity='0'; setTimeout(()=>a.remove(),200); }\" aria-label=\"Dismiss alert\" title=\"Dismiss\">";
        $html .= "<svg width=\"15\" height=\"15\" viewBox=" . '"0 0 24 24"' . " fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><line x1=\"18\" y1=\"6\" x2=\"6\" y2=\"18\"></line><line x1=\"6\" y1=\"6\" x2=\"18\" y2=\"18\"></line></svg>";
        $html .= "</button>";
        $html .= "</div>";
    }
    $html .= '</div>';

    unset($_SESSION['flash_messages']);
    return $html;
}

/**
 * Check if admin is currently authenticated
 *
 * @return bool
 */
function is_admin_logged_in() {
    return !empty($_SESSION['admin_id']) && is_numeric($_SESSION['admin_id']);
}

/**
 * Require admin authentication or redirect to login
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        set_flash('error', 'Please login to access the admin area.');
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Get current admin session details
 *
 * @return array|null
 */
function get_logged_in_admin() {
    if (!is_admin_logged_in()) {
        return null;
    }
    return [
        'id'          => (int)$_SESSION['admin_id'],
        'name'        => $_SESSION['admin_name'] ?? ($_SESSION['admin_username'] ?? 'Administrator'),
        'username'    => $_SESSION['admin_username'] ?? 'Admin',
        'email'       => $_SESSION['admin_email'] ?? '',
        'role'        => $_SESSION['admin_role'] ?? 'admin',
        'status'      => $_SESSION['admin_status'] ?? 'active',
        'is_super'    => ($_SESSION['admin_role'] ?? 'admin') === 'superadmin'
    ];
}

/**
 * Fetch all categories with optional article count
 *
 * @param bool $withCount
 * @return array
 */
function get_all_categories($withCount = false) {
    $db = getDBConnection();
    if ($withCount) {
        $sql = "SELECT c.*, COUNT(CASE WHEN a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL) THEN 1 END) as total_articles
                FROM categories c
                LEFT JOIN articles a ON c.id = a.category_id
                GROUP BY c.id
                ORDER BY c.name ASC";
    } else {
        $sql = "SELECT * FROM categories ORDER BY name ASC";
    }
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

/**
 * Sanitize rich article HTML to prevent XSS while allowing safe tags
 *
 * @param string $content
 * @return string
 */
function sanitize_article_html($content) {
    // Allowed HTML tags for rich content (Full Summernote WYSIWYG support)
    $allowed_tags = '<h1><h2><h3><h4><h5><h6><p><br><hr><strong><b><em><i><u><s><strike><del><sub><sup><font><ul><ol><li><a><blockquote><code><pre><table><thead><tbody><tfoot><tr><th><td><div><span><img><figure><figcaption><section><article>';
    
    // Strip unauthorized tags (like script, iframe, object, embed)
    $clean = strip_tags((string)$content, $allowed_tags);
    
    // Remove inline javascript event attributes (onclick, onload, onerror, etc.)
    $clean = preg_replace('/\s*on\w+\s*=\s*(".*?"|\'.*?\'|[^\'">\s]+)/i', '', $clean);
    
    // Remove javascript: pseudo-protocols in href and src
    $clean = preg_replace('/(href|src)\s*=\s*["\']\s*javascript:[^"\']*["\']/i', '$1="#"', $clean);

    return $clean;
}

/**
 * Validate and safely upload an image file
 *
 * @param array $file $_FILES['input_name']
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function upload_featured_image($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE specified in the form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        return ['success' => false, 'path' => null, 'error' => $uploadErrors[$file['error']] ?? 'Unknown upload error.'];
    }

    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'path' => null, 'error' => 'File size exceeds maximum limit of 4MB.'];
    }

    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file extension. Allowed extensions: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    // Verify real MIME type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file format. Please upload a genuine JPG, PNG, or WebP image.'];
    }

    // Verify it is a valid image using getimagesize
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'path' => null, 'error' => 'Uploaded file is not a valid image.'];
    }

    // Ensure upload directory exists
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    // Generate unique safe filename
    $uniqueName = 'article_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destination = UPLOAD_DIR . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to save uploaded image. Check folder permissions.'];
    }

    // Relative web path stored in DB
    $relativePath = 'uploads/articles/' . $uniqueName;
    return ['success' => true, 'path' => $relativePath, 'error' => null];
}

/**
 * Resolve any article image path to a full public URL
 * Handles full URLs, relative paths, uploads/, and uploads/articles/ seamlessly
 *
 * @param string|null $imagePath
 * @param bool $fallback Whether to return default brand image if not found
 * @return string
 */
function get_article_image_url($imagePath, $fallback = true) {
    $defaultImage = BASE_URL . '/assets/images/default-article.jpg';

    if (empty($imagePath)) {
        return $fallback ? $defaultImage : '';
    }

    $imagePath = trim((string)$imagePath);

    // Full URLs (HTTP / HTTPS)
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }

    // Root-relative URL
    if (strpos($imagePath, '/') === 0) {
        return BASE_URL . $imagePath;
    }

    $cleanPath = ltrim($imagePath, '/\\');
    $baseDir = dirname(__DIR__);

    // 1. Direct path relative to site root (e.g. uploads/articles/xxx.jpg)
    if (file_exists($baseDir . '/' . $cleanPath)) {
        return BASE_URL . '/' . $cleanPath;
    }

    // 2. Relative to uploads/
    if (file_exists($baseDir . '/uploads/' . $cleanPath)) {
        return BASE_URL . '/uploads/' . $cleanPath;
    }

    // 3. Relative to uploads/articles/
    if (file_exists($baseDir . '/uploads/articles/' . $cleanPath)) {
        return BASE_URL . '/uploads/articles/' . $cleanPath;
    }

    // If string already begins with uploads/, use it
    if (strpos($cleanPath, 'uploads/') === 0) {
        return BASE_URL . '/' . $cleanPath;
    }

    if ($fallback) {
        return $defaultImage;
    }

    return BASE_URL . '/uploads/' . $cleanPath;
}

/**
 * Resolve article thumbnail URL with fallback hierarchy:
 * 1. thumbnail_image
 * 2. featured_image
 * 3. YouTube video thumbnail (if video attached)
 * 4. Default brand fallback image
 *
 * @param array|null $article
 * @return string
 */
function get_article_thumbnail_url($article) {
    $defaultImage = BASE_URL . '/assets/images/default-article.jpg';
    if (!is_array($article)) {
        return $defaultImage;
    }

    // 1. Check thumbnail_image
    if (!empty($article['thumbnail_image'])) {
        $url = get_article_image_url($article['thumbnail_image'], false);
        if (!empty($url)) return $url;
    }

    // 2. Check featured_image
    if (!empty($article['featured_image'])) {
        $url = get_article_image_url($article['featured_image'], false);
        if (!empty($url)) return $url;
    }

    // 3. Check YouTube Video thumbnail fallback
    if (!empty($article['video_url'])) {
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $article['video_url'], $match)) {
            return 'https://img.youtube.com/vi/' . $match[1] . '/hqdefault.jpg';
        }
    }

    // 4. Fallback image
    return $defaultImage;
}

/**
 * Resolve high-resolution featured image for large hero displays and single article pages
 * Prevents small thumbnail pixelation/blurriness on large containers
 *
 * @param array|null $article
 * @return string Full URL to image
 */
function get_article_featured_image_url($article) {
    $defaultImage = BASE_URL . '/assets/images/default-article.jpg';
    if (!is_array($article)) {
        return $defaultImage;
    }

    // 1. High-Resolution Featured Image First (Prevents pixelation)
    if (!empty($article['featured_image'])) {
        $url = get_article_image_url($article['featured_image'], false);
        if (!empty($url)) return $url;
    }

    // 2. Thumbnail image fallback
    if (!empty($article['thumbnail_image'])) {
        $url = get_article_image_url($article['thumbnail_image'], false);
        if (!empty($url)) return $url;
    }

    // 3. YouTube HD thumbnail
    if (!empty($article['video_url'])) {
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $article['video_url'], $match)) {
            return 'https://img.youtube.com/vi/' . $match[1] . '/hqdefault.jpg';
        }
    }

    return $defaultImage;
}

/**
 * Validate and safely upload a category image file
 *
 * @param array $file $_FILES['image']
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function upload_category_image($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE specified in the form.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        return ['success' => false, 'path' => null, 'error' => $uploadErrors[$file['error']] ?? 'Unknown upload error.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'path' => null, 'error' => 'File size exceeds maximum limit of 4MB.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file extension. Allowed extensions: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'path' => null, 'error' => 'Invalid file format. Please upload a genuine JPG, PNG, or WebP image.'];
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'path' => null, 'error' => 'Uploaded file is not a valid image.'];
    }

    $catDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'categories' . DIRECTORY_SEPARATOR;
    if (!is_dir($catDir)) {
        mkdir($catDir, 0755, true);
    }

    $uniqueName = 'cat_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
    $destination = $catDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'path' => null, 'error' => 'Failed to save category image. Check folder permissions.'];
    }

    $relativePath = 'uploads/categories/' . $uniqueName;
    return ['success' => true, 'path' => $relativePath, 'error' => null];
}

/**
 * Resolve category thumbnail/image URL with fallback
 *
 * @param array|string|null $category Category row or image path
 * @return string Full URL to category image
 */
function get_category_image_url($category) {
    $rawImage = is_array($category) ? ($category['image'] ?? '') : (string)$category;
    $rawImage = trim((string)$rawImage);

    if (empty($rawImage)) {
        return BASE_URL . '/assets/images/category-placeholder.svg';
    }

    if (strpos($rawImage, 'http://') === 0 || strpos($rawImage, 'https://') === 0) {
        return $rawImage;
    }

    if (strpos($rawImage, '/') === 0) {
        return BASE_URL . $rawImage;
    }

    $cleanPath = ltrim($rawImage, '/\\');
    $baseDir = dirname(__DIR__);

    if (file_exists($baseDir . '/' . $cleanPath)) {
        return BASE_URL . '/' . $cleanPath;
    }
    if (file_exists($baseDir . '/uploads/categories/' . $cleanPath)) {
        return BASE_URL . '/uploads/categories/' . $cleanPath;
    }
    if (file_exists($baseDir . '/uploads/articles/' . $cleanPath)) {
        return BASE_URL . '/uploads/articles/' . $cleanPath;
    }
    if (file_exists($baseDir . '/uploads/' . $cleanPath)) {
        return BASE_URL . '/uploads/' . $cleanPath;
    }

    return BASE_URL . '/assets/images/category-placeholder.svg';
}


/**
 * Truncate text cleanly to given limit
 *
 * @param string $text
 * @param int $limit
 * @return string
 */
function truncate_words($text, $limit = 140) {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    $truncated = mb_substr($text, 0, $limit);
    // Break at last space
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== false) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return $truncated . '...';
}

/**
 * Format date nicely
 *
 * @param string $datetime
 * @return string
 */
function format_date($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('M j, Y', strtotime($datetime));
}

/**
 * Generate relevant SEO keywords for articles
 *
 * @param array $article
 * @return string
 */
function generate_article_keywords($article) {
    if (!empty($article['keywords'])) {
        return trim($article['keywords']);
    }

    $keywords = [];

    // Category name keywords
    if (!empty($article['category_name'])) {
        $keywords[] = $article['category_name'];
        $keywords[] = $article['category_name'] . ' careers';
        $keywords[] = $article['category_name'] . ' jobs';
    }

    // Job posting specific keywords
    if (!empty($article['is_job_posting'])) {
        if (!empty($article['job_company'])) {
            $keywords[] = $article['job_company'] . ' hiring';
            $keywords[] = $article['job_company'] . ' vacancies';
        }
        if (!empty($article['job_location'])) {
            $keywords[] = 'jobs in ' . $article['job_location'];
        }
        if (!empty($article['job_type'])) {
            $keywords[] = $article['job_type'] . ' jobs';
        }
        $keywords[] = 'hiring alerts 2026';
        $keywords[] = 'employment opportunities';
    } else {
        $keywords[] = 'career guidance';
        $keywords[] = 'professional roadmap';
        $keywords[] = 'interview preparation';
        $keywords[] = 'job search tips';
    }

    // Meaningful keywords from title
    $rawWords = preg_split('/[\s,\-\:\&\|\(\)]+/', $article['title'] ?? '');
    $stopWords = ['a', 'an', 'the', 'in', 'on', 'at', 'for', 'to', 'of', 'and', 'or', 'is', 'are', 'that', 'with', 'from', 'by', 'as', 'how', 'what', 'top', 'best'];
    $meaningfulWords = [];
    foreach ($rawWords as $w) {
        $clean = strtolower(trim($w));
        if (strlen($clean) > 2 && !in_array($clean, $stopWords)) {
            $meaningfulWords[] = ucfirst($clean);
        }
    }

    if (!empty($meaningfulWords)) {
        $keywords[] = implode(' ', array_slice($meaningfulWords, 0, 3));
        foreach (array_slice($meaningfulWords, 0, 4) as $mw) {
            $keywords[] = $mw;
        }
    }

    $keywords[] = SITE_NAME;

    $keywords = array_unique(array_filter($keywords));
    return implode(', ', array_slice($keywords, 0, 10));
}

/**
 * Clean and truncate meta descriptions to optimal SEO length (140-155 characters)
 * Ensures search engines and SEO extensions (like Detailed SEO) display a green status.
 *
 * @param string $desc
 * @param int $maxLength
 * @return string
 */
function clean_seo_description($desc, $maxLength = 155) {
    $desc = strip_tags((string)$desc);
    $desc = str_replace(["\xc2\xa0", '&nbsp;', "\r\n", "\r", "\n", "\t"], ' ', $desc);
    $desc = trim(preg_replace('/\s+/', ' ', $desc));

    if (empty($desc)) {
        return '';
    }

    if (mb_strlen($desc) <= $maxLength) {
        return $desc;
    }

    $truncated = mb_substr($desc, 0, $maxLength);
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== false && $lastSpace > 120) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return rtrim($truncated, ',.;:- ') . '.';
}

/**
 * Clean and optimize SEO titles to stay within search engine limits (<= 60 chars)
 *
 * @param string $title
 * @param int $maxLength
 * @return string
 */
function clean_seo_title($title, $maxLength = 60) {
    $title = strip_tags(trim((string)$title));
    $title = preg_replace('/\s+/', ' ', $title);
    if (mb_strlen($title) <= $maxLength) {
        return $title;
    }
    $truncated = mb_substr($title, 0, $maxLength);
    $lastSpace = mb_strrpos($truncated, ' ');
    if ($lastSpace !== false && $lastSpace > 45) {
        $truncated = mb_substr($truncated, 0, $lastSpace);
    }
    return rtrim($truncated, ',.;:- |');
}

/**
 * Retrieve a setting value from the database with in-memory cache and fallback
 *
 * @param string $key
 * @param mixed $default
 * @param bool $forceRefresh
 * @return mixed
 */
function get_setting($key, $default = '', $forceRefresh = false) {
    static $settingsCache = null;
    if ($settingsCache === null || $forceRefresh) {
        $settingsCache = [];
        try {
            $db = getDBConnection();
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $e) {}
    }
    return (isset($settingsCache[$key]) && $settingsCache[$key] !== '') ? $settingsCache[$key] : $default;
}

/**
 * Get dynamic website brand name
 *
 * @return string
 */
function get_site_name() {
    return get_setting('site_name', defined('SITE_NAME') ? SITE_NAME : 'CareerNexaa');
}

/**
 * Get dynamic website logo image path or URL
 *
 * @return string
 */
function get_site_logo() {
    return get_setting('site_logo', '');
}

/**
 * Get dynamic website logo full URL
 *
 * @return string
 */
function get_site_logo_url() {
    $logo = get_site_logo();
    if (empty($logo)) return '';
    if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, '//')) {
        return $logo;
    }
    return (defined('BASE_URL') ? BASE_URL : '') . '/' . ltrim($logo, '/');
}

/**
 * Get dynamic website favicon path or URL
 *
 * @return string
 */
function get_site_favicon() {
    return get_setting('site_favicon', '');
}

/**
 * Get dynamic website favicon full URL
 *
 * @return string
 */
function get_site_favicon_url() {
    $fav = get_site_favicon();
    if (empty($fav)) {
        return (defined('BASE_URL') ? BASE_URL : '') . '/favicon.ico';
    }
    if (str_starts_with($fav, 'http://') || str_starts_with($fav, 'https://') || str_starts_with($fav, '//')) {
        return $fav;
    }
    return (defined('BASE_URL') ? BASE_URL : '') . '/' . ltrim($fav, '/');
}

/**
 * Format brand title with accent styling (e.g. CareerNexaa -> Career<span class="brand-green">Nexaa</span>)
 *
 * @param string|null $name
 * @return string
 */
function format_brand_name($name = null) {
    $name = $name ?: get_site_name();
    $name = trim($name);
    if (empty($name)) return 'Career<span class="brand-green">Nexaa</span>';

    // If compound name like CareerNexaa
    if (preg_match('/^([A-Z][a-z0-9]+)([A-Z].*)$/', $name, $matches)) {
        return htmlspecialchars($matches[1]) . '<span class="brand-green">' . htmlspecialchars($matches[2]) . '</span>';
    }
    // If two words like "Job Portal"
    if (strpos($name, ' ') !== false) {
        $parts = explode(' ', $name, 2);
        return htmlspecialchars($parts[0]) . ' <span class="brand-green">' . htmlspecialchars($parts[1]) . '</span>';
    }
    // Single word fallback
    $half = max(1, (int)floor(strlen($name) / 2));
    return htmlspecialchars(substr($name, 0, $half)) . '<span class="brand-green">' . htmlspecialchars(substr($name, $half)) . '</span>';
}

/**
 * Get reaction statistics and user reaction state for an article
 *
 * @param int $articleId
 * @return array ['likes' => int, 'dislikes' => int, 'user_reaction' => 'like'|'dislike'|null]
 */
function get_article_reactions_data($articleId) {
    $db = getDBConnection();
    $articleId = (int)$articleId;
    
    // Get counts from articles table
    $stmt = $db->prepare("SELECT likes, dislikes FROM articles WHERE id = ? LIMIT 1");
    $stmt->execute([$articleId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $likes = (int)($row['likes'] ?? 0);
    $dislikes = (int)($row['dislikes'] ?? 0);
    
    // Check if current visitor has reacted
    $ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $userReaction = null;
    
    $rStmt = $db->prepare("SELECT reaction_type FROM article_reactions WHERE article_id = ? AND user_ip = ? LIMIT 1");
    $rStmt->execute([$articleId, $ip]);
    $reactionRow = $rStmt->fetch(PDO::FETCH_ASSOC);
    if ($reactionRow) {
        $userReaction = $reactionRow['reaction_type'];
    }
    
    return [
        'likes' => max(0, $likes),
        'dislikes' => max(0, $dislikes),
        'user_reaction' => $userReaction
    ];
}

/**
 * Toggle or switch an article reaction (Like / Dislike)
 *
 * @param int $articleId
 * @param string $type 'like' or 'dislike'
 * @return array
 */
function toggle_article_reaction($articleId, $type) {
    if (!in_array($type, ['like', 'dislike'], true)) {
        return ['success' => false, 'message' => 'Invalid reaction type.'];
    }
    
    $db = getDBConnection();
    $articleId = (int)$articleId;
    $ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    // Verify article exists
    $check = $db->prepare("SELECT id, likes, dislikes FROM articles WHERE id = ? AND is_trash = 0 LIMIT 1");
    $check->execute([$articleId]);
    $art = $check->fetch(PDO::FETCH_ASSOC);
    if (!$art) {
        return ['success' => false, 'message' => 'Article not found.'];
    }
    
    // Check existing reaction
    $rStmt = $db->prepare("SELECT id, reaction_type FROM article_reactions WHERE article_id = ? AND user_ip = ? LIMIT 1");
    $rStmt->execute([$articleId, $ip]);
    $existing = $rStmt->fetch(PDO::FETCH_ASSOC);
    
    $currentLikes = (int)$art['likes'];
    $currentDislikes = (int)$art['dislikes'];
    $finalUserReaction = null;
    $action = '';
    
    if ($existing) {
        if ($existing['reaction_type'] === $type) {
            // Same reaction clicked again -> Undo / Remove reaction
            $db->prepare("DELETE FROM article_reactions WHERE id = ?")->execute([$existing['id']]);
            if ($type === 'like') {
                $currentLikes = max(0, $currentLikes - 1);
            } else {
                $currentDislikes = max(0, $currentDislikes - 1);
            }
            $finalUserReaction = null;
            $action = 'removed';
        } else {
            // Opposite reaction clicked -> Switch reaction
            $db->prepare("UPDATE article_reactions SET reaction_type = ?, user_agent = ?, created_at = NOW() WHERE id = ?")
               ->execute([$type, $agent, $existing['id']]);
            if ($type === 'like') {
                $currentLikes++;
                $currentDislikes = max(0, $currentDislikes - 1);
            } else {
                $currentDislikes++;
                $currentLikes = max(0, $currentLikes - 1);
            }
            $finalUserReaction = $type;
            $action = 'switched';
        }
    } else {
        // New reaction -> Insert
        $db->prepare("INSERT INTO article_reactions (article_id, reaction_type, user_ip, user_agent) VALUES (?, ?, ?, ?)")
           ->execute([$articleId, $type, $ip, $agent]);
        if ($type === 'like') {
            $currentLikes++;
        } else {
            $currentDislikes++;
        }
        $finalUserReaction = $type;
        $action = 'added';
    }
    
    // Update article counters
    $db->prepare("UPDATE articles SET likes = ?, dislikes = ? WHERE id = ?")
       ->execute([$currentLikes, $currentDislikes, $articleId]);
    
    return [
        'success' => true,
        'action' => $action,
        'user_reaction' => $finalUserReaction,
        'likes' => $currentLikes,
        'dislikes' => $currentDislikes,
        'message' => $action === 'removed' ? 'Reaction removed.' : ($type === 'like' ? 'Thanks for liking this article!' : 'Feedback received.')
    ];
}

/**
 * Get approved comments for an article
 *
 * @param int $articleId
 * @param string $status
 * @param int $limit
 * @return array
 */
function get_article_comments($articleId, $status = 'approved', $limit = 50) {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT id, article_id, user_name, user_email, comment_text, status, created_at
        FROM comments
        WHERE article_id = ? AND status = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, (int)$articleId, PDO::PARAM_INT);
    $stmt->bindValue(2, $status, PDO::PARAM_STR);
    $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get total approved comments count for an article
 *
 * @param int $articleId
 * @param string $status
 * @return int
 */
function get_article_comments_count($articleId, $status = 'approved') {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE article_id = ? AND status = ?");
    $stmt->execute([(int)$articleId, $status]);
    return (int)$stmt->fetchColumn();
}

/**
 * Submit a comment on an article with anti-spam checks
 *
 * @param int $articleId
 * @param string $name
 * @param string $email
 * @param string $commentText
 * @return array
 */
function submit_article_comment($articleId, $name, $email, $commentText) {
    $db = getDBConnection();
    $articleId = (int)$articleId;
    $name = trim(strip_tags((string)$name));
    $email = trim((string)$email);
    $commentText = trim(strip_tags((string)$commentText));
    
    // Validation
    if ($articleId <= 0) {
        return ['success' => false, 'message' => 'Invalid article specified.'];
    }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        return ['success' => false, 'message' => 'Please enter your name (2 to 80 characters).'];
    }
    if (!empty($email) && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120)) {
        return ['success' => false, 'message' => 'Please provide a valid email address.'];
    }
    if (mb_strlen($commentText) < 1 || mb_strlen($commentText) > 2500) {
        return ['success' => false, 'message' => 'Comment cannot be empty (up to 2500 characters).'];
    }
    
    // Fallback email if omitted
    if (empty($email)) {
        $email = 'reader@' . (defined('SITE_DOMAIN') ? SITE_DOMAIN : 'careernexaa.com');
    }
    
    // Verify article exists and is published
    $artStmt = $db->prepare("SELECT id FROM articles WHERE id = ? AND status = 'published' AND is_trash = 0 LIMIT 1");
    $artStmt->execute([$articleId]);
    if (!$artStmt->fetch()) {
        return ['success' => false, 'message' => 'Article does not exist or is not available.'];
    }
    
    $ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    
    // Check if user is authenticated admin (bypass rate limits for testing & moderation)
    $isAdmin = function_exists('is_admin_logged_in') && is_admin_logged_in();
    
    if (!$isAdmin) {
        // Anti-Spam: Rate limit (max 30 comments per IP in 10 minutes)
        $rateCheck = $db->prepare("
            SELECT COUNT(*) FROM comments 
            WHERE user_ip = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)
        ");
        $rateCheck->execute([$ip]);
        if ((int)$rateCheck->fetchColumn() >= 30) {
            return ['success' => false, 'message' => 'You are posting comments too quickly. Please wait a few minutes before trying again.'];
        }
        
        // Duplicate check in last 15 seconds (prevents accidental double clicks)
        $dupCheck = $db->prepare("
            SELECT id FROM comments 
            WHERE article_id = ? AND user_ip = ? AND comment_text = ? AND created_at >= (NOW() - INTERVAL 15 SECOND)
            LIMIT 1
        ");
        $dupCheck->execute([$articleId, $ip, $commentText]);
        if ($dupCheck->fetch()) {
            return ['success' => false, 'message' => 'Duplicate comment detected. Please wait a few seconds before posting again.'];
        }
    }
    
    // Status: Moderated - requires administrator review before appearing publicly
    $status = 'pending';
    
    $stmt = $db->prepare("
        INSERT INTO comments (article_id, user_name, user_email, comment_text, status, user_ip, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$articleId, $name, $email, $commentText, $status, $ip, $agent]);
    $newId = (int)$db->lastInsertId();
    
    return [
        'success' => true,
        'status' => 'pending',
        'message' => 'Thank you! Your comment has been submitted and is awaiting administrator approval.',
        'comment' => [
            'id' => $newId,
            'user_name' => $name,
            'comment_text' => $commentText,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'formatted_date' => 'Just now'
        ]
    ];
}

/**
 * Load all section visibility flags (cached in-memory for high performance)
 *
 * @return array
 */
function get_all_sections_visibility() {
    static $visibilityCache = null;
    if ($visibilityCache !== null) {
        return $visibilityCache;
    }

    $visibilityCache = [];
    try {
        $db = getDBConnection();
        $stmt = $db->query("SELECT section_key, is_enabled FROM section_visibility");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $visibilityCache[$row['section_key']] = (int)$row['is_enabled'];
        }
    } catch (Exception $e) {
        // Fallback: If table is not yet migrated, assume enabled
    }

    return $visibilityCache;
}

/**
 * Check if a specific frontend section is visible (enabled)
 *
 * @param string $sectionKey
 * @return bool
 */
function is_section_visible($sectionKey) {
    $visibility = get_all_sections_visibility();
    // Default to true (1) if key is not defined
    if (!isset($visibility[$sectionKey])) {
        return true;
    }
    return (int)$visibility[$sectionKey] === 1;
}

/**
 * Update section visibility state
 *
 * @param string $sectionKey
 * @param int|bool $isEnabled
 * @return bool
 */
function set_section_visibility($sectionKey, $isEnabled) {
    $db = getDBConnection();
    $enabledVal = $isEnabled ? 1 : 0;
    $stmt = $db->prepare("UPDATE section_visibility SET is_enabled = ? WHERE section_key = ?");
    return $stmt->execute([$enabledVal, $sectionKey]);
}

/**
 * Load full configuration for all homepage sections (title, subtitle, limit, category_id, enabled)
 *
 * @return array
 */
function get_home_sections_config() {
    static $configCache = null;
    if ($configCache !== null) {
        return $configCache;
    }

    $configCache = [];
    try {
        $db = getDBConnection();
        $stmt = $db->query("SELECT * FROM section_visibility WHERE section_key IN ('hero', 'latest', 'categories', 'popular', 'articles', 'recommended', 'newsletter', 'notification') ORDER BY sort_order ASC, id ASC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $configCache[$row['section_key']] = $row;
        }
    } catch (Exception $e) {
        // Fallback default
    }

    return $configCache;
}

/**
 * Get individual homepage section configuration
 *
 * @param string $key
 * @param string $defaultTitle
 * @param string $defaultSubtitle
 * @param int $defaultLimit
 * @return array
 */
function get_home_section($key, $defaultTitle = '', $defaultSubtitle = '', $defaultLimit = 6) {
    $configs = get_home_sections_config();
    if (isset($configs[$key])) {
        return $configs[$key];
    }
    // Fallback between notification and newsletter
    if ($key === 'notification' && isset($configs['newsletter'])) {
        return $configs['newsletter'];
    }
    if ($key === 'newsletter' && isset($configs['notification'])) {
        return $configs['notification'];
    }
    return [
        'section_key' => $key,
        'is_enabled'  => 1,
        'title'       => $defaultTitle,
        'subtitle'    => $defaultSubtitle,
        'item_limit'  => $defaultLimit,
        'category_id' => null
    ];
}

/**
 * Track real live active visitor session on public requests
 *
 * @param string|null $pageTitle
 */
function track_active_visitor($pageTitle = null) {
    // Never track administrative panel routes
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($reqUri, '/admin') !== false) {
        return;
    }

    $ip = get_client_ip();
    if (empty($ip)) {
        return;
    }

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // Skip common automated bots
    if (preg_match('/(bot|crawl|spider|slurp|facebookexternalhit)/i', $ua)) {
        return;
    }

    $device = 'Desktop';
    if (preg_match('/(mobile|android|iphone|ipad|touch)/i', $ua)) {
        $device = 'Mobile';
    }

    $ref = !empty($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : 'Direct';
    if (empty($ref)) {
        $ref = 'Direct';
    }

    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            INSERT INTO active_visitors (ip_address, current_url, page_title, device_type, country, referrer, last_activity)
            VALUES (?, ?, ?, ?, 'Local', ?, NOW())
            ON DUPLICATE KEY UPDATE 
                current_url = VALUES(current_url),
                page_title = VALUES(page_title),
                device_type = VALUES(device_type),
                referrer = VALUES(referrer),
                last_activity = NOW()
        ");
        $cleanUrl = mb_substr($reqUri, 0, 255);
        $cleanTitle = mb_substr((string)($pageTitle ?? ''), 0, 150);
        $stmt->execute([$ip, $cleanUrl, $cleanTitle, $device, mb_substr($ref, 0, 255)]);

        // Update daily aggregated stats for charts & reports
        $db->exec("
            INSERT INTO daily_traffic_stats (stat_date, page_views, unique_visitors, sessions, created_at, updated_at)
            VALUES (CURDATE(), 1, 1, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                page_views = page_views + 1,
                sessions = sessions + 1,
                updated_at = NOW()
        ");
    } catch (Throwable $e) {
        // Silent failure if table temporarily unavailable
    }
}

/**
 * Get daily traffic chart dataset for admin dashboard
 *
 * @param int $days (7, 14, or 30)
 * @return array
 */
function get_daily_traffic_chart_data($days = 30) {
    try {
        $db = getDBConnection();
        $days = in_array((int)$days, [7, 14, 30]) ? (int)$days : 30;

        $stmt = $db->prepare("
            SELECT stat_date, page_views, unique_visitors, sessions 
            FROM daily_traffic_stats 
            WHERE stat_date >= (CURDATE() - INTERVAL ? DAY)
            ORDER BY stat_date ASC
        ");
        $stmt->execute([$days - 1]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dataMap = [];
        foreach ($rows as $r) {
            $dataMap[$r['stat_date']] = $r;
        }

        $labels = [];
        $displayLabels = [];
        $pageViews = [];
        $uniqueVisitors = [];
        $sessions = [];

        $today = new DateTime('today');
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = (clone $today)->modify("-$i days");
            $dateStr = $d->format('Y-m-d');
            $labels[] = $dateStr;
            $displayLabels[] = $d->format('M j');

            if (isset($dataMap[$dateStr])) {
                $pageViews[] = (int)$dataMap[$dateStr]['page_views'];
                $uniqueVisitors[] = (int)$dataMap[$dateStr]['unique_visitors'];
                $sessions[] = (int)$dataMap[$dateStr]['sessions'];
            } else {
                $pageViews[] = 0;
                $uniqueVisitors[] = 0;
                $sessions[] = 0;
            }
        }

        return [
            'labels' => $labels,
            'display_labels' => $displayLabels,
            'page_views' => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'sessions' => $sessions,
            'total_views' => array_sum($pageViews),
            'total_visitors' => array_sum($uniqueVisitors),
            'total_sessions' => array_sum($sessions),
            'max_views' => !empty($pageViews) ? max($pageViews) : 0,
        ];
    } catch (Throwable $e) {
        return [
            'labels' => [],
            'display_labels' => [],
            'page_views' => [],
            'unique_visitors' => [],
            'sessions' => [],
            'total_views' => 0,
            'total_visitors' => 0,
            'total_sessions' => 0,
            'max_views' => 0,
        ];
    }
}

/**
 * Get real active online visitors count within the last 5 minutes
 *
 * @return int
 */
function get_real_active_visitors_count() {
    try {
        $db = getDBConnection();
        $count = (int)$db->query("
            SELECT COUNT(*) 
            FROM active_visitors 
            WHERE last_activity >= (NOW() - INTERVAL 5 MINUTE)
        ")->fetchColumn();
        return max(0, $count);
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Get real active pages currently being viewed
 *
 * @param int $limit
 * @return array
 */
function get_real_active_pages($limit = 6) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT current_url, page_title, COUNT(*) as visitor_count, MAX(device_type) as device, MAX(referrer) as source
            FROM active_visitors
            WHERE last_activity >= (NOW() - INTERVAL 5 MINUTE)
            GROUP BY current_url, page_title
            ORDER BY visitor_count DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Resolve working live public URL for a static page
 *
 * @param string $slug
 * @return string
 */
function get_page_view_url($slug) {
    $cleanSlug = trim(strtolower((string)$slug));
    
    // Direct canonical script mapping
    $mapping = [
        'about' => 'about.php',
        'about-us' => 'about.php',
        'privacy' => 'privacy-policy.php',
        'privacy-policy' => 'privacy-policy.php',
        'terms' => 'terms.php',
        'terms-and-conditions' => 'terms.php',
        'cookie-policy' => 'cookie-policy.php',
        'cookies' => 'cookie-policy.php',
        'disclaimer' => 'disclaimer.php',
        'contact' => 'contact.php',
        'contact-us' => 'contact.php'
    ];

    if (isset($mapping[$cleanSlug])) {
        return BASE_URL . '/' . $mapping[$cleanSlug];
    }

    $directFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . $cleanSlug . '.php';
    if (file_exists($directFile)) {
        return BASE_URL . '/' . $cleanSlug . '.php';
    }

    return BASE_URL . '/page.php?slug=' . urlencode($cleanSlug);
}

/**
 * Fetch dynamic header navigation items
 *
 * @return array
 */
function get_header_menu() {
    $default = [
        ['id' => '1', 'label' => 'Home', 'url' => '/', 'enabled' => true],
        ['id' => '2', 'label' => 'Vlogs', 'url' => '/articles.php?video=1', 'enabled' => true],
        ['id' => '3', 'label' => 'Articles', 'url' => '/articles.php', 'enabled' => true],
        ['id' => '4', 'label' => 'Trending', 'url' => '/articles.php?trending=1', 'enabled' => true]
    ];
    $raw = get_setting('header_menu_items');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
    }
    return $default;
}

/**
 * Fetch dynamic footer navigation items
 *
 * @return array
 */
function get_footer_menu() {
    $default = [
        ['id' => '1', 'label' => 'Popular', 'url' => '/articles.php?trending=1', 'enabled' => true],
        ['id' => '2', 'label' => 'Search', 'url' => '/search.php', 'enabled' => true]
    ];
    $raw = get_setting('footer_menu_items');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }
    }
    return $default;
}
