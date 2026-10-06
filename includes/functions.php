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
        $sql = "SELECT c.*, COUNT(CASE WHEN a.status = 'published' THEN 1 END) as total_articles
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
    // Allowed HTML tags for rich content
    $allowed_tags = '<h1><h2><h3><h4><h5><h6><p><br><hr><strong><b><em><i><u><ul><ol><li><a><blockquote><code><pre><table><thead><tbody><tr><th><td><div><span><img>';
    
    // Strip unauthorized tags
    $clean = strip_tags($content, $allowed_tags);
    
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

