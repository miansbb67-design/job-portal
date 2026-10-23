<?php
/**
 * CareerNexaa - Theme 2: Tech Magazine Homepage Template
 * The Verge / TechCrunch inspired high-impact editorial showcase.
 */

// If previewing, show an elegant notification bar
$isPreview = !empty($_SESSION['site_preview_theme']) && $_SESSION['site_preview_theme'] === 'magazine';

include __DIR__ . '/../../includes/header.php';
?>

<?php if ($isPreview): ?>
<div style="background:#2563eb; color:#ffffff; padding:0.6rem 1rem; text-align:center; font-size:0.85rem; font-weight:700; position:sticky; top:0; z-index:9999; display:flex; align-items:center; justify-content:center; gap:1rem;">
    <span>⚡ You are currently previewing <strong>Tech Magazine Theme</strong></span>
    <a href="<?= ADMIN_URL ?>/themes.php" style="background:#ffffff; color:#2563eb; padding:0.25rem 0.75rem; border-radius:999px; text-decoration:none; font-size:0.8rem;">Activate in Admin</a>
    <a href="?preview_theme=reset" style="color:#ffffff; text-decoration:underline; font-size:0.8rem;">Exit Preview</a>
</div>
<?php endif; ?>

<!-- Top Live News Ticker Tape -->
<?php if (!empty($radarArticles)): ?>
<div class="mag-ticker-wrap">
    <div class="site-container mag-ticker-inner">
        <span class="mag-ticker-badge">TRENDING NOW</span>
        <?php foreach (array_slice($radarArticles, 0, 2) as $tArt): ?>
            <a href="<?= get_article_url($tArt['slug']) ?>" class="mag-ticker-text">
                &bull; <?= h($tArt['title']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- HERO SHOWCASE: Asymmetric 4-Card Magazine Grid -->
<?php if (!empty($spotlight)): ?>
<section class="mag-hero-section">
    <div class="site-container">
        <div class="mag-hero-grid">
            <!-- Left: Big Lead Story (60%) -->
            <?php 
                $leadThumb = get_article_featured_image_url($spotlight);
            ?>
            <a href="<?= get_article_url($spotlight['slug']) ?>" class="mag-lead-card">
                <img src="<?= $leadThumb ?>" alt="<?= h($spotlight['title']) ?>" class="mag-lead-img" loading="eager" fetchpriority="high" decoding="async">
                <div class="mag-lead-overlay"></div>
                <div class="mag-lead-content">
                    <span class="mag-lead-kicker">
                        ★ <?= !empty($spotlight['is_featured']) ? 'Featured Story' : h($spotlight['category_name']) ?>
                    </span>
                    <h1 class="mag-lead-title">
                        <?= h($spotlight['title']) ?>
                    </h1>
                    <p class="mag-lead-excerpt">
                        <?= h($spotlight['excerpt']) ?>
                    </p>
                    <div class="mag-lead-meta">
                        <span>By <?= h($spotlight['author_name']) ?></span>
                        <span>&bull;</span>
                        <span><?= format_date($spotlight['published_at']) ?></span>
                        <span>&bull;</span>
                        <span><?= (int)$spotlight['views'] ?> views</span>
                    </div>
                </div>
            </a>

            <!-- Right: Secondary Stacked Stories (40%) -->
            <div class="mag-sub-stack">
                <?php if (!empty($filteredRadarArticles)): ?>
                    <?php foreach (array_slice($filteredRadarArticles, 0, 3) as $sArt): ?>
                    <?php $subThumb = get_article_thumbnail_url($sArt); ?>
                    <a href="<?= get_article_url($sArt['slug']) ?>" class="mag-sub-card">
                        <img src="<?= $subThumb ?>" alt="<?= h($sArt['title']) ?>" class="mag-sub-thumb" loading="lazy" decoding="async">
                        <div class="mag-sub-body">
                            <span class="mag-sub-cat"><?= h($sArt['category_name']) ?></span>
                            <h3 class="mag-sub-title"><?= h($sArt['title']) ?></h3>
                            <div class="mag-sub-meta">
                                <span><?= format_date($sArt['published_at']) ?></span>
                                <span>&bull;</span>
                                <span><?= (int)$sArt['views'] ?> views</span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Topic Filter Strip -->
<?php if (!empty($categories)): ?>
<div class="site-container">
    <div class="mag-filter-bar">
        <a href="<?= BASE_URL ?>/articles" class="mag-filter-pill active">All Feeds</a>
        <?php foreach (array_slice($categories, 0, 7) as $fCat): ?>
            <a href="<?= get_category_url($fCat['slug']) ?>" class="mag-filter-pill">
                <?= h($fCat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Ranked Leaderboard Bar (#1, #2, #3 Most Read) -->
<?php if (!empty($trendingGuides)): ?>
<div class="site-container">
    <div class="mag-leaderboard-card">
        <div class="mag-leaderboard-header">
            <h3 class="mag-leaderboard-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                MOST READ THIS WEEK
            </h3>
            <a href="<?= BASE_URL ?>/trending" style="color:#94a3b8; font-size:0.8rem; font-weight:700; text-decoration:none;">Full Top List &rarr;</a>
        </div>
        <div class="mag-leaderboard-grid">
            <?php foreach (array_slice($trendingGuides, 0, 3) as $rIdx => $rItem): ?>
            <a href="<?= get_article_url($rItem['slug']) ?>" class="mag-rank-item">
                <span class="mag-rank-num">#<?= $rIdx + 1 ?></span>
                <div>
                    <div class="mag-rank-text"><?= h($rItem['title']) ?></div>
                    <div class="mag-rank-views"><?= (int)$rItem['views'] ?> reads &bull; <?= h($rItem['category_name']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MAIN FEED: 4-Column Modern Magazine Cards Grid -->
<?php if (!empty($latestArticles)): ?>
<section class="site-container" style="margin-bottom:3.5rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <div>
            <h2 style="font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.25rem;">Latest Publications &amp; Circulars</h2>
            <p style="color:#64748b; font-size:0.9rem; margin:0;">Real-time verified opportunities and career insights</p>
        </div>
        <a href="<?= BASE_URL ?>/articles" style="color:#2563eb; font-weight:700; font-size:0.875rem; text-decoration:none;">Browse All Guides &rarr;</a>
    </div>

    <div class="mag-cards-grid">
        <?php foreach ($latestArticles as $mArt): ?>
        <?php $mThumb = get_article_thumbnail_url($mArt); ?>
        <article class="mag-card">
            <div class="mag-card-thumb-wrap">
                <a href="<?= get_article_url($mArt['slug']) ?>">
                    <img src="<?= $mThumb ?>" alt="<?= h($mArt['title']) ?>" class="mag-card-img" loading="lazy" decoding="async">
                </a>
                <span class="mag-card-badge">
                    <?= !empty($mArt['is_job_posting']) ? 'Opening' : h($mArt['category_name']) ?>
                </span>
            </div>
            <div class="mag-card-body">
                <h3 class="mag-card-title">
                    <a href="<?= get_article_url($mArt['slug']) ?>">
                        <?= h($mArt['title']) ?>
                    </a>
                </h3>
                <p class="mag-card-excerpt">
                    <?= h($mArt['excerpt']) ?>
                </p>
                <div class="mag-card-footer">
                    <span><?= format_date($mArt['published_at']) ?></span>
                    <a href="<?= get_article_url($mArt['slug']) ?>" style="color:#2563eb; font-weight:700; text-decoration:none;">Read &rarr;</a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
