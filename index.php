<?php
/**
 * Public Website - Home Page
 * CareerNexaa - Modern Jobs & Career Guidance Hub
 * Features:
 * - Interactive Hero with Smart Search & Sector Filter Chips
 * - Editorial Spotlight & Career Pulse Radar Split
 * - Interactive Sector Tracks with custom icons
 * - Trending Career Strategies (3-column grid)
 * - Verified Hiring & Job Openings (Dedicated Job Cards)
 * - Career Success Toolkit Banner
 * - Latest Publications Grid
 * - AdSense Policy Compliant placements
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ads-placeholder.php';

$pageTitle = "Jobs & Career Guidance Hub | Verified Openings, Resume Tips & Exam Guides";
$metaDescription = "Discover verified government jobs, tech careers, remote positions, ATS resume guides, and interview frameworks on CareerNexaa.";
$metaKeywords = "jobs 2026, career guidance, government jobs, remote tech jobs, ATS resume format, interview questions, job portal, CareerNexaa";

$db = getDBConnection();

// 1. Fetch Main Spotlight Guide (Editor's pick / trending guide)
$spotlightStmt = $db->query("
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE a.status = 'published' AND a.is_job_posting = 0
    ORDER BY a.views DESC, a.published_at DESC
    LIMIT 1
");
$spotlight = $spotlightStmt->fetch();
$spotlightId = $spotlight ? (int)$spotlight['id'] : 0;

// 2. Fetch Active Job Vacancies (Hiring Alerts)
$jobsStmt = $db->query("
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE a.status = 'published' AND a.is_job_posting = 1
    ORDER BY a.published_at DESC
    LIMIT 3
");
$activeJobs = $jobsStmt->fetchAll();

// 3. Fetch Radar Fast Reads (Articles for the Hero right sidebar)
$radarStmt = $db->prepare("
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE a.status = 'published' AND a.id != :spotlight_id AND a.is_job_posting = 0
    ORDER BY a.published_at DESC
    LIMIT 3
");
$radarStmt->execute([':spotlight_id' => $spotlightId]);
$radarArticles = $radarStmt->fetchAll();

// 4. Fetch Trending Career Guides (3-column grid)
$trendingStmt = $db->prepare("
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE a.status = 'published' AND a.id != :spotlight_id
    ORDER BY a.views DESC
    LIMIT 3
");
$trendingStmt->execute([':spotlight_id' => $spotlightId]);
$trendingGuides = $trendingStmt->fetchAll();

// 5. Fetch Latest Articles (6 cards)
$latestStmt = $db->query("
    SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
    FROM articles a
    JOIN categories c ON a.category_id = c.id
    JOIN admins adm ON a.author_id = adm.id
    WHERE a.status = 'published'
    ORDER BY a.published_at DESC
    LIMIT 6
");
$latestArticles = $latestStmt->fetchAll();

// 6. Fetch Categories with total article count
$categories = get_all_categories(true);

include __DIR__ . '/includes/header.php';
?>

<!-- ================= Main Content Showcase ================= -->
<section class="cp-hero-section">
    <div class="site-container">
        <!-- Hero Feature Split Showcase -->
        <div class="hero-showcase-grid">
            <!-- Left: Editorial Spotlight Guide (58% width) -->
            <?php if ($spotlight): ?>
            <?php 
                $spotThumb = get_article_thumbnail_url($spotlight);
            ?>
            <article class="editorial-spotlight-card">
                <div class="spotlight-media-wrap">
                    <img src="<?= $spotThumb ?>" alt="<?= h($spotlight['title']) ?>" class="spotlight-img" loading="eager">
                    <div class="spotlight-badge-row">
                        <span class="badge-editorial">★ Featured Guide</span>
                        <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($spotlight['category_slug']) ?>" class="badge-cat-tag">
                            <?= h($spotlight['category_name']) ?>
                        </a>
                    </div>
                </div>

                <div class="spotlight-content">
                    <h2 class="spotlight-title">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($spotlight['slug']) ?>">
                            <?= h($spotlight['title']) ?>
                        </a>
                    </h2>

                    <p class="spotlight-excerpt">
                        <?= h($spotlight['excerpt']) ?>
                    </p>

                    <div class="spotlight-footer">
                        <div class="spotlight-author-wrap">
                            <span class="author-avatar-chip"><?= strtoupper(substr($spotlight['author_name'], 0, 1)) ?></span>
                            <div class="author-meta-info">
                                <span class="author-meta-name"><?= h($spotlight['author_name']) ?></span>
                                <span class="author-meta-date"><?= format_date($spotlight['published_at']) ?> &bull; <?= (int)$spotlight['views'] ?> views</span>
                            </div>
                        </div>

                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($spotlight['slug']) ?>" class="read-guide-btn">
                            Read Guide &rarr;
                        </a>
                    </div>
                </div>
            </article>
            <?php endif; ?>

            <!-- Right: Career Pulse Radar & Live Alerts (42% width) -->
            <div class="career-radar-widget">
                <div class="radar-widget-header">
                    <div class="radar-title-group">
                        <span class="live-pulse-dot"></span>
                        <h3 class="radar-heading">Live Job Radar</h3>
                    </div>
                    <a href="<?= BASE_URL ?>/articles.php" class="radar-view-all">All Guides &rarr;</a>
                </div>

                <div class="radar-items-list">
                    <!-- Featured Active Job Role in Radar -->
                    <?php if (!empty($activeJobs)): ?>
                    <?php $radarJob = $activeJobs[0]; ?>
                    <div class="radar-job-item">
                        <div class="radar-job-top">
                            <span class="job-hiring-pill">Hiring Now</span>
                            <?php if (!empty($radarJob['job_salary'])): ?>
                                <span class="job-salary-tag"><?= h($radarJob['job_salary']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h4 class="radar-job-title">
                            <a href="<?= BASE_URL ?>/article.php?slug=<?= h($radarJob['slug']) ?>">
                                <?= h($radarJob['title']) ?>
                            </a>
                        </h4>
                        <div class="radar-job-company">
                            <span><?= h($radarJob['job_company']) ?></span>
                            <?php if (!empty($radarJob['job_location'])): ?>
                                <span>&bull; <?= h($radarJob['job_location']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Trending Quick Reads -->
                    <?php foreach ($radarArticles as $rArt): ?>
                    <?php 
                        $rThumb = get_article_thumbnail_url($rArt);
                    ?>
                    <div class="radar-article-row">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($rArt['slug']) ?>" class="radar-thumb-box">
                            <img src="<?= $rThumb ?>" alt="<?= h($rArt['title']) ?>" loading="lazy">
                        </a>
                        <div class="radar-article-info">
                            <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($rArt['category_slug']) ?>" class="radar-cat-tag">
                                <?= h($rArt['category_name']) ?>
                            </a>
                            <h4 class="radar-row-title">
                                <a href="<?= BASE_URL ?>/article.php?slug=<?= h($rArt['slug']) ?>">
                                    <?= h($rArt['title']) ?>
                                </a>
                            </h4>
                            <span class="radar-meta-text"><?= format_date($rArt['published_at']) ?> &bull; <?= (int)$rArt['views'] ?> views</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= Section 1: Explore by Career Track (6 Cards) ================= -->
<section class="page-section">
    <div class="site-container">
        <div class="section-header-row">
            <div>
                <h2>Browse Top Categories</h2>
                <p class="section-header-sub">Find the right career path, exam preparation guides, and job opportunities.</p>
            </div>
            <a href="<?= BASE_URL ?>/categories.php" class="section-action-btn">All Categories &rarr;</a>
        </div>

        <div class="sector-tracks-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/categories.php?slug=<?= h($cat['slug']) ?>" class="sector-card">
                    <div class="sector-icon-box">
                        <?php if (strpos($cat['slug'], 'govt') !== false || strpos($cat['slug'], 'government') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 10v11M9 10v11M15 10v11M19 10v11M12 2L2 7h20L12 2z"></path></svg>
                        <?php elseif (strpos($cat['slug'], 'tech') !== false || strpos($cat['slug'], 'private') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                        <?php elseif (strpos($cat['slug'], 'remote') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                        <?php elseif (strpos($cat['slug'], 'resume') !== false || strpos($cat['slug'], 'interview') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                        <?php elseif (strpos($cat['slug'], 'intern') !== false): ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        <?php else: ?>
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon></svg>
                        <?php endif; ?>
                    </div>
                    <span class="sector-card-name"><?= h($cat['name']) ?></span>
                    <span class="sector-card-count"><?= (int)$cat['total_articles'] ?> Guides &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================= Section 2: Trending Career Strategies (3-Column Grid) ================= -->
<?php if (!empty($trendingGuides)): ?>
<section class="page-section page-section-alt">
    <div class="site-container">
        <div class="section-header-row">
            <div>
                <h2>Trending Career Guides</h2>
                <p class="section-header-sub">Top interview tips, ATS resume frameworks, and career growth roadmaps.</p>
            </div>
            <a href="<?= BASE_URL ?>/articles.php?trending=1" class="section-action-btn">View All Guides &rarr;</a>
        </div>

        <div class="cp-card-grid-3">
            <?php foreach ($trendingGuides as $article): ?>
            <?php 
                $thumbPath = get_article_thumbnail_url($article);
            ?>
            <article class="cp-article-card">
                <div class="cp-card-thumb-wrap">
                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>">
                        <img src="<?= $thumbPath ?>" alt="<?= h($article['title']) ?>" class="cp-card-thumb" loading="lazy">
                    </a>
                    <span class="cp-badge-floating">
                        Trending
                    </span>
                </div>

                <div class="cp-card-body">
                    <div class="cp-card-meta-top">
                        <span class="cp-card-cat-name"><?= h($article['category_name']) ?></span>
                        <span>&bull;</span>
                        <span><?= format_date($article['published_at']) ?></span>
                    </div>

                    <h3 class="cp-card-title">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>">
                            <?= h($article['title']) ?>
                        </a>
                    </h3>

                    <p class="cp-card-excerpt">
                        <?= h($article['excerpt']) ?>
                    </p>

                    <div class="cp-card-footer">
                        <div class="cp-author-chip">
                            <span class="cp-author-avatar"><?= strtoupper(substr($article['author_name'], 0, 1)) ?></span>
                            <span><?= h($article['author_name']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>" class="cp-card-read-link">
                            Read Guide &rarr;
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= Section 3: Verified Job Openings & Hiring Alerts ================= -->
<?php if (!empty($activeJobs)): ?>
<section class="page-section">
    <div class="site-container">
        <div class="section-header-row">
            <div>
                <h2>Latest Job Openings</h2>
                <p class="section-header-sub">Verified government, corporate, and tech jobs with direct application details.</p>
            </div>
            <a href="<?= BASE_URL ?>/articles.php" class="section-action-btn">View All Jobs &rarr;</a>
        </div>

        <div class="job-postings-strip">
            <?php foreach ($activeJobs as $job): ?>
            <div class="job-vacancy-card">
                <div class="job-vacancy-card-header">
                    <span class="job-type-pill"><?= !empty($job['job_type']) ? h($job['job_type']) : 'Direct Opening' ?></span>
                    <?php if (!empty($job['job_salary'])): ?>
                        <span class="job-salary-badge"><?= h($job['job_salary']) ?></span>
                    <?php endif; ?>
                </div>

                <h3 class="job-vacancy-title">
                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($job['slug']) ?>">
                        <?= h($job['title']) ?>
                    </a>
                </h3>

                <div class="job-company-row">
                    <span><?= h($job['job_company']) ?></span>
                    <?php if (!empty($job['job_location'])): ?>
                        <span>&bull;</span>
                        <span class="job-location-text"><?= h($job['job_location']) ?></span>
                    <?php endif; ?>
                </div>

                <p class="job-vacancy-excerpt">
                    <?= h($job['excerpt']) ?>
                </p>

                <div class="job-vacancy-action-bar">
                    <span style="font-size:0.775rem; color:var(--text-muted);">Posted <?= format_date($job['published_at']) ?></span>
                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($job['slug']) ?>" class="job-apply-link">
                        View Job & Details &rarr;
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================= Section 4: Career Success Toolkit Banner ================= -->
<div class="site-container">
    <section class="career-toolkit-section">
        <div class="site-container">
            <div style="text-align:center; max-width:680px; margin:0 auto 3rem;">
                <h2 style="font-size:2.2rem; color:#ffffff; margin-bottom:0.75rem;">Career Success Toolkit</h2>
                <p style="color:#d1fae5; font-size:1.05rem;">Free roadmaps, templates, and frameworks to accelerate your job search.</p>
            </div>

            <div class="career-toolkit-grid">
                <div class="toolkit-box">
                    <div class="toolkit-box-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    </div>
                    <h3 class="toolkit-box-title">ATS Resume Blueprint</h3>
                    <p class="toolkit-box-desc">Proven formatting tips and keywords to pass ATS automated filters easily.</p>
                    <a href="<?= BASE_URL ?>/article.php?slug=how-to-write-an-ats-friendly-resume" class="toolkit-box-link">Read Blueprint &rarr;</a>
                </div>

                <div class="toolkit-box">
                    <div class="toolkit-box-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    </div>
                    <h3 class="toolkit-box-title">STAR Interview Method</h3>
                    <p class="toolkit-box-desc">Master behavioral interview questions with structured and confident answers.</p>
                    <a href="<?= BASE_URL ?>/article.php?slug=mastering-behavioral-interview-star-method" class="toolkit-box-link">View Method &rarr;</a>
                </div>

                <div class="toolkit-box">
                    <div class="toolkit-box-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                    </div>
                    <h3 class="toolkit-box-title">Remote Job Guide 2026</h3>
                    <p class="toolkit-box-desc">Find high-paying international remote positions and freelance opportunities.</p>
                    <a href="<?= BASE_URL ?>/article.php?slug=top-10-high-paying-remote-jobs-2026" class="toolkit-box-link">Explore Jobs &rarr;</a>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- ================= Section 5: Latest Articles & Guides ================= -->
<section class="page-section">
    <div class="site-container">
        <div class="section-header-row">
            <div>
                <h2>Latest Articles & News</h2>
                <p class="section-header-sub">Recent career advice, exam schedules, and recruitment updates.</p>
            </div>
            <a href="<?= BASE_URL ?>/articles.php" class="section-action-btn">View All Articles &rarr;</a>
        </div>

        <div class="cp-card-grid-3">
            <?php foreach ($latestArticles as $article): ?>
            <?php 
                $thumbPath = get_article_thumbnail_url($article);
            ?>
            <article class="cp-article-card">
                <div class="cp-card-thumb-wrap">
                    <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>">
                        <img src="<?= $thumbPath ?>" alt="<?= h($article['title']) ?>" class="cp-card-thumb" loading="lazy">
                    </a>
                    <span class="cp-badge-floating <?= $article['is_job_posting'] ? 'job-badge' : '' ?>">
                        <?= $article['is_job_posting'] ? 'Job Opening' : h($article['category_name']) ?>
                    </span>
                </div>

                <div class="cp-card-body">
                    <div class="cp-card-meta-top">
                        <span class="cp-card-cat-name"><?= h($article['category_name']) ?></span>
                        <span>&bull;</span>
                        <span><?= format_date($article['published_at']) ?></span>
                    </div>

                    <h3 class="cp-card-title">
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>">
                            <?= h($article['title']) ?>
                        </a>
                    </h3>

                    <p class="cp-card-excerpt">
                        <?= h($article['excerpt']) ?>
                    </p>

                    <?php if ($article['is_job_posting'] && !empty($article['job_company'])): ?>
                    <div class="cp-job-chips-box">
                        <span><?= h($article['job_company']) ?></span>
                        <?php if (!empty($article['job_salary'])): ?>
                            <span>&bull; <?= h($article['job_salary']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="cp-card-footer">
                        <div class="cp-author-chip">
                            <span class="cp-author-avatar"><?= strtoupper(substr($article['author_name'], 0, 1)) ?></span>
                            <span><?= h($article['author_name']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= h($article['slug']) ?>" class="cp-card-read-link">
                            Read Guide &rarr;
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
