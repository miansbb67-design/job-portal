<?php
/**
 * CareerNexaa - Theme 3: Clean Minimalist Homepage Template
 * Medium / Substack inspired quiet, distraction-free reading experience.
 */

$isPreview = !empty($_SESSION['site_preview_theme']) && $_SESSION['site_preview_theme'] === 'minimalist';

include __DIR__ . '/../../includes/header.php';
?>

<!-- Google Fonts for Minimalist Serif reading experience -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Merriweather:ital,wght@0,300;0,400;0,700;1,300&display=swap" rel="stylesheet">

<?php if ($isPreview): ?>
<div style="background:#111111; color:#ffffff; padding:0.65rem 1rem; text-align:center; font-size:0.85rem; font-weight:600; position:sticky; top:0; z-index:9999; display:flex; align-items:center; justify-content:center; gap:1.25rem;">
    <span>🌿 You are currently previewing <strong>Clean Minimalist Theme</strong></span>
    <a href="<?= ADMIN_URL ?>/themes.php" style="background:#ffffff; color:#111111; padding:0.25rem 0.85rem; border-radius:999px; text-decoration:none; font-size:0.8rem; font-weight:700;">Activate in Admin</a>
    <a href="?preview_theme=reset" style="color:#aaaaaa; text-decoration:underline; font-size:0.8rem;">Exit Preview</a>
</div>
<?php endif; ?>

<main class="min-page-wrap" id="main-content">
    <div class="min-container">
        <!-- Serene Header & Tagline -->
        <header class="min-home-header">
            <h1 class="min-home-title">Thoughts, Guides & Career Clarity</h1>
            <p class="min-home-tagline">
                Curated insights, proven frameworks, and verified career avenues — without distraction or noise.
            </p>
            <nav class="min-category-bar" aria-label="Explore Topics">
                <?php foreach (array_slice($categories, 0, 7) as $cat): ?>
                    <a href="<?= get_category_url($cat['slug']) ?>" class="min-cat-chip">
                        <?= h($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </header>

        <!-- Main Stream Grid: Chronological Essay Stream (Left) + Staff Picks (Right) -->
        <div class="min-stream-grid">
            <!-- Left Stream -->
            <div class="min-stream-left">
                <?php 
                // Combine spotlight with featured grid articles into a continuous elegant stream
                $allStream = [];
                if (!empty($spotlight)) {
                    $allStream[] = $spotlight;
                }
                if (!empty($featuredGridArticles)) {
                    foreach ($featuredGridArticles as $fArt) {
                        if (empty($spotlight) || $fArt['id'] != $spotlight['id']) {
                            $allStream[] = $fArt;
                        }
                    }
                }
                ?>

                <?php if (!empty($allStream)): ?>
                    <?php foreach ($allStream as $art): ?>
                        <article class="min-feed-item">
                            <div>
                                <div class="min-feed-author-row">
                                    <span class="min-avatar-dot"><?= strtoupper(substr($art['author_name'] ?? 'E', 0, 1)) ?></span>
                                    <span style="font-weight:600;"><?= h($art['author_name']) ?></span>
                                    <span style="color:#999999;">in</span>
                                    <a href="<?= get_category_url($art['category_slug']) ?>" style="color:#111111; text-decoration:none; font-weight:500;">
                                        <?= h($art['category_name']) ?>
                                    </a>
                                </div>

                                <h2 class="min-feed-title">
                                    <a href="<?= get_article_url($art['slug']) ?>">
                                        <?= h($art['title']) ?>
                                    </a>
                                </h2>

                                <p class="min-feed-snippet">
                                    <?= h($art['excerpt']) ?>
                                </p>

                                <div class="min-feed-meta-row">
                                    <span><?= date('M j', strtotime($art['published_at'])) ?></span>
                                    <span>&bull;</span>
                                    <span><?= max(1, ceil(str_word_count(strip_tags($art['content'] ?? '')) / 200)) ?> min read</span>
                                    <?php if (!empty($art['is_featured'])): ?>
                                        <span>&bull;</span>
                                        <span class="min-feed-pill">Staff Pick</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <a href="<?= get_article_url($art['slug']) ?>" class="min-feed-thumb-wrap" tabindex="-1" aria-hidden="true">
                                <img src="<?= get_article_featured_image_url($art) ?>" alt="<?= h($art['title']) ?>" class="min-feed-thumb" loading="lazy">
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color:var(--min-muted); padding:3rem 0;">No articles published yet.</p>
                <?php endif; ?>
            </div>

            <!-- Right: Staff Picks & Topics -->
            <aside class="min-sidebar-section">
                <div class="min-sidebar-heading">Trending on <?= SITE_NAME ?></div>
                <div class="min-sidebar-list">
                    <?php 
                    $sideItems = !empty($radarArticles) ? $radarArticles : array_slice($allStream, 0, 4);
                    $numIdx = 1;
                    foreach ($sideItems as $sItem):
                    ?>
                        <a href="<?= get_article_url($sItem['slug']) ?>" class="min-sidebar-num-item">
                            <span class="min-sidebar-num">0<?= $numIdx++ ?></span>
                            <div>
                                <div style="font-size:0.775rem; color:#666666; margin-bottom:0.25rem;">
                                    <?= h($sItem['author_name']) ?>
                                </div>
                                <div class="min-sidebar-item-title"><?= h($sItem['title']) ?></div>
                                <div style="font-size:0.75rem; color:#999999; margin-top:0.35rem;">
                                    <?= date('M j', strtotime($sItem['published_at'])) ?> &bull; <?= (int)$sItem['views'] ?> views
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Verified Hiring Kiosk (Minimalist Callout) -->
                <?php if (!empty($activeJobs)): ?>
                <div style="background:#fafafa; border:1px solid #eeeeee; border-radius:6px; padding:1.25rem; margin-top:2rem;">
                    <div style="font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#1a8917; margin-bottom:0.75rem;">
                        Verified Job Openings
                    </div>
                    <?php foreach ($activeJobs as $job): ?>
                        <div style="margin-bottom:0.85rem; padding-bottom:0.85rem; border-bottom:1px solid #f0f0f0;">
                            <a href="<?= get_article_url($job['slug']) ?>" style="font-weight:600; font-size:0.875rem; color:#111111; text-decoration:none; display:block;">
                                <?= h($job['title']) ?>
                            </a>
                            <span style="font-size:0.75rem; color:#777777;">
                                <?= h($job['job_company']) ?> &bull; <?= h($job['job_location']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>

<?php
include __DIR__ . '/../../includes/footer.php';
