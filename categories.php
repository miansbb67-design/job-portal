<?php
/**
 * Public Website - Categories Explorer & Category Articles Page
 * Shows list of all categories OR all articles under a specific category.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ads-placeholder.php';

$db = getDBConnection();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (!empty($slug)) {
    // 1. Specific Category View
    $catStmt = $db->prepare("SELECT * FROM categories WHERE slug = ? LIMIT 1");
    $catStmt->execute([$slug]);
    $category = $catStmt->fetch();

    if (!$category) {
        set_flash('error', 'The requested category does not exist.');
        header('Location: ' . BASE_URL . '/categories');
        exit;
    }

    $pageTitle = "{$category['name']} - Career Articles & Job Guides";
    $metaDescription = !empty($category['description']) ? $category['description'] : "Explore all guides and employment opportunities in {$category['name']}.";
    $metaKeywords = "{$category['name']}, {$category['name']} jobs, {$category['name']} career advice, exam schedules, CareerNexaa";
    $canonicalUrl = BASE_URL . '/category/' . $category['slug'];

    // Pagination for Category
    $currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) $currentPage = 1;
    if ($currentPage > 1) {
        $canonicalUrl .= '?page=' . $currentPage;
    }
    $perPageSetting = (int)get_setting('items_per_page', defined('ARTICLES_PER_PAGE') ? ARTICLES_PER_PAGE : 8);
    $perPage = ($perPageSetting >= 8) ? $perPageSetting : (defined('ARTICLES_PER_PAGE') && ARTICLES_PER_PAGE >= 8 ? ARTICLES_PER_PAGE : 8);
    $offset = ($currentPage - 1) * $perPage;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ? AND status = 'published' AND (is_trash = 0 OR is_trash IS NULL)");
    $countStmt->execute([$category['id']]);
    $totalArticles = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalArticles / $perPage);

    $articlesStmt = $db->prepare("
        SELECT a.*, c.name as category_name, c.slug as category_slug, adm.username as author_name
        FROM articles a
        JOIN categories c ON a.category_id = c.id
        JOIN admins adm ON a.author_id = adm.id
        WHERE a.category_id = ? AND a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)
        ORDER BY a.published_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $articlesStmt->execute([$category['id']]);
    $articles = $articlesStmt->fetchAll();

    // High-Priority SEO Structured Data (Breadcrumbs & CollectionPage)
    $catSchema = [
        "@context" => "https://schema.org",
        "@graph" => [
            [
                "@type" => "BreadcrumbList",
                "itemListElement" => [
                    [
                        "@type" => "ListItem",
                        "position" => 1,
                        "name" => "Home",
                        "item" => BASE_URL . "/"
                    ],
                    [
                        "@type" => "ListItem",
                        "position" => 2,
                        "name" => "Categories",
                        "item" => BASE_URL . "/categories"
                    ],
                    [
                        "@type" => "ListItem",
                        "position" => 3,
                        "name" => $category['name'],
                        "item" => $canonicalUrl
                    ]
                ]
            ],
            [
                "@type" => "CollectionPage",
                "@id" => $canonicalUrl,
                "url" => $canonicalUrl,
                "name" => $pageTitle,
                "description" => $metaDescription,
                "isPartOf" => [
                    "@type" => "WebSite",
                    "name" => "CareerNexaa",
                    "url" => BASE_URL . "/"
                ]
            ]
        ]
    ];
    $extraHead = '<script type="application/ld+json">' . json_encode($catSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';

    include __DIR__ . '/includes/header.php';
    ?>
    <section class="page-banner">
        <div class="site-container">
            <nav class="article-breadcrumbs" style="justify-content:center; color:#94a3b8; margin-bottom:0.75rem;">
                <a href="<?= BASE_URL ?>/" style="color:#cbd5e1;">Home</a>
                <span>&rsaquo;</span>
                <a href="<?= BASE_URL ?>/categories" style="color:#cbd5e1;">Categories</a>
                <span>&rsaquo;</span>
                <span style="color:#ffffff; font-weight:600;"><?= h($category['name']) ?></span>
            </nav>
            <h1><?= h($category['name']) ?></h1>
            <p><?= h($category['description']) ?></p>
        </div>
    </section>

    <section class="page-section">
        <div class="site-container">
            <div class="section-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:2rem; padding-bottom:1rem; border-bottom:1px solid var(--border-color);">
                <div class="section-title-wrap">
                    <h2 style="font-size:1.65rem; font-weight:800; margin:0 0 0.35rem 0;"><?= h($category['name']) ?> Publications</h2>
                    <p class="section-subtitle" style="color:var(--text-muted); font-size:0.9rem; margin:0;">
                        <?= count($articles) > 0 ? "Showing " . count($articles) . " of " . $totalArticles . " published guides" : "0 publications currently &bull; Updates coming soon" ?>
                    </p>
                </div>
                <a href="<?= BASE_URL ?>/categories" class="view-all-link" style="font-weight:700; color:var(--primary); text-decoration:none; display:inline-flex; align-items:center; gap:0.35rem;">
                    &larr; All Categories
                </a>
            </div>

            <?php if (!empty($articles)): ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <article class="article-card">
                            <div class="article-thumb-wrap">
                                <?php 
                                $thumbPath = get_article_thumbnail_url($article);
                                ?>
                                <img src="<?= $thumbPath ?>" alt="<?= h($article['title']) ?>" class="article-thumb" loading="lazy" decoding="async">
                                <span class="article-badge <?= $article['is_job_posting'] ? 'job-badge' : '' ?>">
                                    <?= $article['is_job_posting'] ? 'Job Opening' : h($article['category_name']) ?>
                                </span>
                            </div>

                            <div class="article-content">
                                <div class="article-meta">
                                    <span><?= format_date($article['published_at']) ?></span>
                                    <span class="meta-dot">&bull;</span>
                                    <span><?= (int)$article['views'] ?> views</span>
                                </div>

                                <h3 class="article-title">
                                    <a href="<?= get_article_url($article['slug']) ?>">
                                        <?= h($article['title']) ?>
                                    </a>
                                </h3>

                                <p class="article-excerpt">
                                    <?= h($article['excerpt']) ?>
                                </p>

                                <?php if ($article['is_job_posting'] && !empty($article['job_company'])): ?>
                                    <div class="job-card-specs">
                                        <span class="job-spec-item"><strong>Company:</strong> <?= h($article['job_company']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="article-footer">
                                    <span style="font-weight:600; color:var(--text-muted); font-size:0.825rem;">
                                        <?= h($article['category_name']) ?>
                                    </span>
                                    <a href="<?= get_article_url($article['slug']) ?>" class="read-more-link">
                                        Read Guide &rarr;
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-nav">
                        <div class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="<?= get_category_url($slug) ?>?page=<?= $i ?>" class="page-btn <?= ($i === $currentPage) ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state-card" style="padding: 3rem 2rem; text-align: center; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-lg); margin-top: 1rem;">
                    <div style="font-size: 2.75rem; margin-bottom: 0.75rem;">📁</div>
                    <h3 style="font-size: 1.35rem; font-weight: 800; color: var(--text-primary); margin-bottom: 0.5rem;">No Published Articles in this Sector Yet</h3>
                    <p style="color: var(--text-muted); max-width: 520px; margin: 0 auto 1.5rem; font-size: 0.95rem; line-height: 1.55;">New career guides and hiring circulars are currently being verified for <?= h($category['name']) ?>. In the meantime, explore our active publications across other sectors:</p>
                    <a href="<?= BASE_URL ?>/articles" class="btn btn-primary" style="padding: 0.65rem 1.75rem;">Browse All Active Articles</a>
                </div>

                <?php
                // Helpful fallback grid so page is never barren
                $fallbackStmt = $db->query("
                    SELECT a.*, c.name as category_name, c.slug as category_slug
                    FROM articles a
                    JOIN categories c ON a.category_id = c.id
                    WHERE a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL)
                    ORDER BY a.published_at DESC
                    LIMIT 4
                ");
                $fallbackArticles = $fallbackStmt->fetchAll();
                ?>
                <?php if (!empty($fallbackArticles)): ?>
                    <div style="margin-top: 3.5rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-primary); margin-bottom: 1.25rem;">Trending Guides in Other Sectors</h3>
                        <div class="articles-grid">
                            <?php foreach ($fallbackArticles as $fArticle): ?>
                                <article class="article-card">
                                    <div class="article-thumb-wrap">
                                        <img src="<?= get_article_thumbnail_url($fArticle) ?>" alt="<?= h($fArticle['title']) ?>" class="article-thumb" loading="lazy">
                                        <span class="article-badge"><?= h($fArticle['category_name']) ?></span>
                                    </div>
                                    <div class="article-content">
                                        <h4 class="article-title" style="font-size:1.05rem; font-weight:700;">
                                            <a href="<?= get_article_url($fArticle['slug']) ?>"><?= h($fArticle['title']) ?></a>
                                        </h4>
                                        <p class="article-excerpt"><?= h($fArticle['excerpt']) ?></p>
                                        <div class="article-footer">
                                            <a href="<?= get_article_url($fArticle['slug']) ?>" class="read-more-link">Read Guide &rarr;</a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </section>

    <?php
} else {
    // 2. All Categories Overview Page (Rich Category Boxes with Photos & Descriptions)
    $pageTitle = "All Categories & Career Tracks | " . SITE_NAME;
    $metaDescription = "Browse all career and industry categories on CareerNexaa. Explore curated guides, interview frameworks, exam roadmaps, and latest vacancies.";
    $canonicalUrl = BASE_URL . '/categories';

    $categories = get_all_categories(true);
    $totalCategories = count($categories);
    $totalArticlesAcross = array_sum(array_column($categories, 'total_articles'));

    include __DIR__ . '/includes/header.php';
    ?>

    <!-- Page Banner / Hero -->
    <section class="page-banner" style="padding: 3rem 0 2.5rem; background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #064e3b 100%); position: relative; overflow: hidden;">
        <div class="site-container" style="position: relative; z-index: 2; text-align: center; max-width: 860px; margin: 0 auto;">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(5, 150, 105, 0.2); border: 1px solid rgba(16, 185, 129, 0.35); padding: 0.35rem 0.95rem; border-radius: 999px; margin-bottom: 1.25rem; font-size: 0.8rem; font-weight: 700; color: #6ee7b7; text-transform: uppercase; letter-spacing: 0.06em;">
                <span>🧭 Career Tracks & Topic Hubs</span>
            </div>
            <h1 style="font-size: 2.5rem; font-weight: 800; color: #ffffff; margin-bottom: 0.75rem; line-height: 1.2; letter-spacing: -0.02em;">
                All Categories
            </h1>
            <p style="font-size: 1.05rem; color: #cbd5e1; line-height: 1.6; margin-bottom: 1.5rem; max-width: 680px; margin-left: auto; margin-right: auto;">
                Explore all curated career tracks, exam blueprints, tech tutorials, and verified hiring guides categorized for fast discovery.
            </p>
            
            <!-- Quick Category Search & Live Filter Bar -->
            <div style="position: relative; max-width: 520px; margin: 0 auto;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position: absolute; left: 1.1rem; top: 50%; transform: translateY(-50%); pointer-events: none;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input 
                    type="search" 
                    id="catFilterInput" 
                    placeholder="Search categories (e.g. Remote, Tech, Finance)..." 
                    aria-label="Filter categories by name or keyword"
                    style="width: 100%; padding: 0.85rem 1rem 0.85rem 3rem; background: #ffffff; border: 2px solid rgba(255, 255, 255, 0.15); border-radius: 999px; font-size: 0.95rem; color: #0f172a; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25); outline: none; transition: border-color 0.2s, box-shadow 0.2s;"
                    oninput="filterCategoryCards(this.value)"
                >
            </div>
        </div>
    </section>

    <!-- Main Categories Grid Section -->
    <section class="page-section" style="padding: 3rem 0 4rem; background: #f8fafc;">
        <div class="site-container">
            
            <!-- Header Meta Bar -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem;">
                <div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0;">
                        Available Sectors (<span id="catCounter"><?= $totalCategories ?></span>)
                    </h2>
                    <p style="font-size: 0.875rem; color: #64748b; margin: 0.25rem 0 0;">
                        Total published articles across categories: <strong><?= $totalArticlesAcross ?></strong>
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <span style="font-size: 0.825rem; font-weight: 600; color: #059669; background: #ecfdf5; padding: 0.35rem 0.8rem; border-radius: 6px; border: 1px solid #a7f3d0;">
                        ✓ Click any box to view articles
                    </span>
                </div>
            </div>

            <!-- Categories Boxes Grid -->
            <div class="category-boxes-grid" id="categoryBoxesGrid">
                <?php foreach ($categories as $cat): ?>
                    <?php 
                    $catImage = get_category_image_url($cat);
                    $articleCount = (int)$cat['total_articles'];
                    $catDesc = trim((string)($cat['description'] ?? ''));
                    if (empty($catDesc)) {
                        $catDesc = "Comprehensive updates, roadmaps, and latest guides in " . $cat['name'] . ".";
                    }
                    ?>
                    <article class="category-box-item" data-title="<?= strtolower(h($cat['name'])) ?>" data-desc="<?= strtolower(h($catDesc)) ?>">
                        <a href="<?= get_category_url($cat['slug']) ?>" class="cat-box-card" aria-label="Explore <?= h($cat['name']) ?> Category">
                            <!-- Card Image Banner -->
                            <div class="cat-box-media-wrap">
                                <img 
                                    src="<?= htmlspecialchars($catImage) ?>" 
                                    alt="<?= h($cat['name']) ?>" 
                                    class="cat-box-image" 
                                    loading="lazy" 
                                    decoding="async" 
                                    onerror="this.src='<?= BASE_URL ?>/assets/images/category-placeholder.svg'"
                                >
                                <div class="cat-box-gradient-overlay"></div>
                                
                                <!-- Article Count Tag -->
                                <span class="cat-box-count-pill">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:4px;">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    <?= $articleCount ?> <?= ($articleCount === 1) ? 'Guide' : 'Guides' ?>
                                </span>

                                <!-- Sector Pill -->
                                <span class="cat-box-track-pill">
                                    Track
                                </span>
                            </div>

                            <!-- Card Body with Title & Description -->
                            <div class="cat-box-body">
                                <h3 class="cat-box-title">
                                    <?= h($cat['name']) ?>
                                </h3>
                                
                                <p class="cat-box-description">
                                    <?= h($catDesc) ?>
                                </p>

                                <div class="cat-box-footer">
                                    <span class="cat-box-link-text">
                                        Browse Articles &rarr;
                                    </span>
                                    <span class="cat-box-slug">
                                        /<?= h($cat['slug']) ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Empty Search Filter State -->
            <div id="noCatsFound" style="display: none; text-align: center; padding: 4rem 1.5rem; background: #ffffff; border-radius: 16px; border: 1px dashed #cbd5e1; margin-top: 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔍</div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem;">No matching categories found</h3>
                <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem;">Try typing a different keyword or clear the search to view all sectors.</p>
                <button type="button" onclick="clearCategoryFilter()" class="btn btn-primary" style="font-size: 0.85rem; padding: 0.5rem 1.25rem;">
                    Clear Search
                </button>
            </div>

            <!-- Monetization: Between Content Ad Slot -->
            <?php if (function_exists('render_monetization_ad')): ?>
                <div style="margin-top: 3.5rem;">
                    <?php render_monetization_ad('between_content_ad'); ?>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Category Boxes Stylesheet -->
    <style>
    .category-boxes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
        gap: 1.75rem;
    }

    .category-box-item {
        display: flex;
        flex-direction: column;
    }

    .cat-box-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        overflow: hidden;
        text-decoration: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04), 0 2px 4px -2px rgba(0, 0, 0, 0.04);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
        position: relative;
    }

    .cat-box-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
        border-color: #a7f3d0;
    }

    .cat-box-media-wrap {
        position: relative;
        height: 195px;
        overflow: hidden;
        background: #0f172a;
    }

    .cat-box-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .cat-box-card:hover .cat-box-image {
        transform: scale(1.07);
    }

    .cat-box-gradient-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.1) 0%, rgba(15, 23, 42, 0.45) 100%);
        pointer-events: none;
    }

    .cat-box-count-pill {
        position: absolute;
        top: 0.85rem;
        right: 0.85rem;
        display: inline-flex;
        align-items: center;
        background: rgba(15, 23, 42, 0.82);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #ffffff;
        font-size: 0.775rem;
        font-weight: 700;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.25);
        z-index: 2;
    }

    .cat-box-track-pill {
        position: absolute;
        bottom: 0.85rem;
        left: 0.85rem;
        display: inline-flex;
        align-items: center;
        background: #059669;
        color: #ffffff;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.2rem 0.55rem;
        border-radius: 4px;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .cat-box-body {
        padding: 1.5rem 1.35rem 1.25rem;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .cat-box-title {
        font-size: 1.2rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 0.55rem;
        line-height: 1.3;
        transition: color 0.15s ease;
    }

    .cat-box-card:hover .cat-box-title {
        color: #059669;
    }

    .cat-box-description {
        font-size: 0.875rem;
        color: #475569;
        line-height: 1.6;
        margin: 0 0 1.25rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        flex: 1;
    }

    .cat-box-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f1f5f9;
        padding-top: 0.95rem;
        margin-top: auto;
    }

    .cat-box-link-text {
        font-size: 0.85rem;
        font-weight: 700;
        color: #059669;
        display: inline-flex;
        align-items: center;
        transition: transform 0.2s ease, color 0.2s ease;
    }

    .cat-box-card:hover .cat-box-link-text {
        transform: translateX(4px);
        color: #047857;
    }

    .cat-box-slug {
        font-size: 0.75rem;
        font-family: monospace;
        color: #94a3b8;
    }

    @media (max-width: 768px) {
        .category-boxes-grid {
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
        .cat-box-media-wrap {
            height: 180px;
        }
    }
    </style>

    <!-- Live Category Filter Script -->
    <script>
    function filterCategoryCards(query) {
        var cleanQ = (query || '').toLowerCase().trim();
        var items = document.querySelectorAll('.category-box-item');
        var visibleCount = 0;

        items.forEach(function(item) {
            var title = item.getAttribute('data-title') || '';
            var desc = item.getAttribute('data-desc') || '';
            if (!cleanQ || title.indexOf(cleanQ) !== -1 || desc.indexOf(cleanQ) !== -1) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        var counterEl = document.getElementById('catCounter');
        if (counterEl) {
            counterEl.textContent = visibleCount;
        }

        var noCats = document.getElementById('noCatsFound');
        if (noCats) {
            noCats.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    function clearCategoryFilter() {
        var input = document.getElementById('catFilterInput');
        if (input) {
            input.value = '';
            filterCategoryCards('');
            input.focus();
        }
    }
    </script>
    <?php
}

include __DIR__ . '/includes/footer.php';
?>
