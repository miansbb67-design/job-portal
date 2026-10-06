<?php
/**
 * Admin Panel - Delete Article Handler
 * CSRF-protected deletion with automatic featured image cleanup.
 */

require_once __DIR__ . '/includes/auth-check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Invalid security token.');
        header('Location: ' . ADMIN_URL . '/articles.php');
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $db = getDBConnection();

        // Fetch image path and post type for check
        $stmt = $db->prepare("SELECT featured_image, title, is_job_posting FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch();

        if ($article) {
            $isJob = !empty($article['is_job_posting']);
            if ($isJob) {
                require_permission('jobs.delete');
            } else {
                require_permission('posts.delete');
            }

            // Delete physical image file if stored in uploads/articles
            if (!empty($article['featured_image']) && strpos($article['featured_image'], 'uploads/articles/') === 0) {
                $imagePath = dirname(__DIR__) . '/' . $article['featured_image'];
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            // Delete database row
            $delStmt = $db->prepare("DELETE FROM articles WHERE id = ?");
            $delStmt->execute([$id]);

            $moduleName = $isJob ? 'jobs' : 'posts';
            log_activity('delete_article', $moduleName, "Permanently deleted {$moduleName} '{$article['title']}' (ID: {$id})", null);

            set_flash('success', "Article '{$article['title']}' was permanently deleted.");
        } else {
            set_flash('error', 'Article not found.');
        }
    }
}

$ref = $_POST['ref'] ?? '';
$redirectUrl = ($ref === 'jobs') ? ADMIN_URL . '/jobs/index.php' : (($ref === 'posts') ? ADMIN_URL . '/posts/index.php' : ADMIN_URL . '/articles.php');
header('Location: ' . $redirectUrl);
exit;
