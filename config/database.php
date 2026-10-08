<?php
/**
 * Database Connection Configuration
 * -----------------------------------------------------------------------------
 * Provides secure PDO database connection with prepared statements and UTF8mb4.
 * Fully optimized for both Local Development (XAMPP) and Live Production Hosting (cPanel / VPS / Cloud).
 *
 * HOW TO CONFIGURE FOR LIVE HOSTING:
 * Option 1 (Recommended): Set credentials in the ".env" file in your site root.
 * Option 2 (Direct): You can also edit the LIVE HOSTING settings below directly.
 */

// Step 1: Load environment variables from .env file if available
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

// =============================================================================
// DATABASE CREDENTIALS CONFIGURATION
// (Values in .env take priority. If no .env is present, fallbacks below are used)
// =============================================================================
$envHost = getenv('DB_HOST') ?: (getenv('DATABASE_HOST') ?: 'localhost');
$envPort = getenv('DB_PORT') ?: '3306';
$envName = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'job_portal_db');
$envUser = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root');
$envPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '');
$envChar = getenv('DB_CHARSET') ?: 'utf8mb4';

// Database Host (Usually 'localhost' or '127.0.0.1' on cPanel/Shared hosts)
if (!defined('DB_HOST'))    define('DB_HOST', $envHost);

// Database Port (Default 3306)
if (!defined('DB_PORT'))    define('DB_PORT', $envPort);

// Database Name (e.g. 'cpaneluser_jobportal' on live hosting, 'job_portal_db' on local)
if (!defined('DB_NAME'))    define('DB_NAME', $envName);

// Database Username (e.g. 'cpaneluser_dbuser' on live hosting, 'root' on local)
if (!defined('DB_USER'))    define('DB_USER', $envUser);

// Database Password (e.g. your strong live password, '' on local XAMPP)
if (!defined('DB_PASS'))    define('DB_PASS', $envPass);

// Database Character Set & Collation
if (!defined('DB_CHARSET')) define('DB_CHARSET', $envChar);

/**
 * Returns a shared PDO database connection instance.
 * Automatically handles persistent pooling, character sets, and connection errors.
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log full error details securely to internal server error log
            error_log("Database Connection Error: " . $e->getMessage());

            $isDev = (getenv('APP_ENV') === 'development' || 
                     (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'])));

            if ($isDev) {
                // In local/development: display actionable error card
                die("<!DOCTYPE html>
                <html><head><title>Database Connection Error</title></head>
                <body style='font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;background:#f8fafc;padding:3rem 1.5rem;display:flex;justify-content:center;'>
                <div style='background:#fff;border:1px solid #fecaca;border-radius:12px;padding:2rem;max-width:560px;width:100%;box-shadow:0 10px 25px -5px rgba(0,0,0,0.05);'>
                    <div style='color:#dc2626;font-size:1.15rem;font-weight:700;margin-bottom:0.75rem;'>Database Connection Error (Local Development)</div>
                    <p style='color:#475569;font-size:0.9rem;line-height:1.5;margin-bottom:1rem;'>Could not connect to the database. Please verify your settings in <code>.env</code> or <code>config/database.php</code>.</p>
                    <div style='background:#fef2f2;border-left:4px solid #ef4444;padding:0.75rem 1rem;font-family:monospace;font-size:0.85rem;color:#991b1b;overflow-x:auto;margin-bottom:1rem;'>" . htmlspecialchars($e->getMessage()) . "</div>
                    <div style='font-size:0.8rem;color:#64748b;'>Database: <strong>" . htmlspecialchars(DB_NAME) . "</strong> | Host: <strong>" . htmlspecialchars(DB_HOST) . "</strong></div>
                </div>
                </body></html>");
            } else {
                // In live production: mask errors to protect server credentials and show clean 503 maintenance page
                http_response_code(503);
                die("<!DOCTYPE html>
                <html lang='en'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Service Unavailable | " . (defined('SITE_NAME') ? SITE_NAME : 'CareerNexaa') . "</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1.5rem; box-sizing: border-box; }
                        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 2.5rem; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
                        h1 { font-size: 1.35rem; color: #0f172a; margin-top: 0; margin-bottom: 0.5rem; }
                        p { font-size: 0.925rem; color: #64748b; line-height: 1.6; margin-bottom: 1.5rem; }
                        .btn { display: inline-block; background: #059669; color: #fff; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; }
                        .btn:hover { background: #047857; }
                    </style>
                </head>
                <body>
                    <div class='card'>
                        <h1>Service Temporarily Unavailable</h1>
                        <p>We are currently performing routine maintenance or database synchronization. Please check back in a few moments.</p>
                        <a href='javascript:location.reload()' class='btn'>Refresh Page</a>
                    </div>
                </body>
                </html>");
            }
        }
    }

    return $pdo;
}
