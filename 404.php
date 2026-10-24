<?php
/**
 * Public Website - Custom 404 Not Found Page
 * Returns appropriate HTTP 404 status code and guides users to relevant content.
 */

if (!headers_sent()) {
    http_response_code(404);
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Page Not Found (404) | " . SITE_NAME;
$metaDescription = "The page or job guide you were looking for could not be found. Use our search tool to find relevant career articles.";
$metaRobots = 'noindex, nofollow';
$canonicalUrl = BASE_URL . '/404';

include __DIR__ . '/includes/header.php';
?>

<section class="page-section" style="padding: 6rem 0; text-align: center;">
    <div class="site-container" style="max-width: 650px;">
        <div style="font-size: 6rem; font-weight: 900; line-height: 1; color: var(--primary); font-family: var(--font-heading); margin-bottom: 1rem;">
            404
        </div>
        <h1 style="font-size: 2.2rem; color: var(--secondary); margin-bottom: 1rem;">
            Page or Article Not Found
        </h1>
        <p style="color: var(--text-muted); font-size: 1.1rem; line-height: 1.6; margin-bottom: 2.5rem;">
            The link you followed may have expired, or the job posting or article has been relocated. Let's get you back on track!
        </p>

        <!-- Search Form -->
        <div style="margin-bottom: 2.5rem;">
            <form action="<?= BASE_URL ?>/search" method="GET" style="display:flex; gap:0.5rem; max-width:500px; margin:0 auto;" role="search">
                <input type="text" name="q" placeholder="Search by job title or keyword..." required class="form-control" style="padding:0.85rem 1.25rem;">
                <button type="submit" class="btn btn-primary">
                    Search
                </button>
            </form>
        </div>

        <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap;">
            <a href="<?= BASE_URL ?>/" class="btn btn-primary">Go to Homepage</a>
            <a href="<?= BASE_URL ?>/articles" class="btn btn-secondary">Browse All Articles</a>
            <a href="<?= BASE_URL ?>/contact" class="btn btn-secondary">Contact Support</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
