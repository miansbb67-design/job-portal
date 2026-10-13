<?php
/**
 * Global Application Configuration
 * -----------------------------------------------------------------------------
 * Holds site constants, paths, URLs, and session initialization.
 * Fully optimized for both Local Development (XAMPP) and Live Production Hosting.
 */

// Step 1: Load optional .env file if it exists in the project root
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (getenv($key) === false) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
            }
        }
    }
}

// Step 2: Detect HTTPS protocol (including Cloudflare, AWS, and reverse proxies)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
           (isset($_SERVER['HTTP_CF_VISITOR']) && strpos($_SERVER['HTTP_CF_VISITOR'], 'https') !== false);

// Step 3: Determine Environment & Configure Error Reporting
$appEnv = getenv('APP_ENV') ?: (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1']) ? 'development' : 'production');
if ($appEnv === 'production') {
    // Production Mode: Suppress fatal traces to visitors and log to server log
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Development Mode: Enable rich diagnostics
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// Step 4: Start session securely if not already active
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path'     => '/',
        'domain'   => $cookieParams['domain'],
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Step 5: Enable output buffering globally to prevent "headers already sent" warnings
if (!ob_get_level()) {
    ob_start();
}

// Step 6: Site Branding Defaults
define('SITE_NAME', 'CareerNexaa');
define('SITE_TAGLINE', 'Your Ultimate Jobs, Careers & Professional Employment Guide');
define('SITE_EMAIL', 'contact@careernexaa.com');

// Step 7: Determine Base URL (Dynamic auto-detection or .env override)
$envBaseUrl = getenv('APP_URL') ?: (getenv('BASE_URL') ?: null);
if (!empty($envBaseUrl) && strpos($envBaseUrl, 'http') === 0) {
    define('BASE_URL', rtrim($envBaseUrl, '/'));
} else {
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Auto-detect root vs subdirectory path
    $appRoot = rtrim(str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__)), '/');
    $docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']), '/') : (strpos($appRoot, '/htdocs') !== false ? substr($appRoot, 0, strpos($appRoot, '/htdocs') + 7) : '');
    $subDir = '';

    if ($docRoot && $appRoot && strpos(strtolower($appRoot), strtolower($docRoot)) === 0) {
        $subDir = substr($appRoot, strlen($docRoot));
    } elseif (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptDir = dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME']));
        if (preg_match('#/(admin|api)(/|$)#', $scriptDir)) {
            $scriptDir = preg_replace('#/(admin|api).*$#', '', $scriptDir);
        }
        $subDir = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') ? '' : $scriptDir;
    }
    
    $subDir = $subDir ? '/' . ltrim($subDir, '/') : '';
    define('BASE_URL', rtrim($protocol . $host . $subDir, '/'));
}
define('SITE_URL', BASE_URL);
define('ADMIN_URL', BASE_URL . '/admin');

// Step 8: File Upload Settings
define('UPLOAD_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'articles' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL', BASE_URL . '/uploads/articles/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB HD Upload Support
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif']);

// Step 9: Default Pagination & Ads
define('ARTICLES_PER_PAGE', 6);
define('ENABLE_ADS', false);
