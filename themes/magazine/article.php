<?php
/**
 * CareerNexaa - Theme 2: Tech Magazine Article Detail Template
 * High-impact, fast-paced editorial layout with reading progress, bold typography, and interactive tech sidebar.
 */

$isPreview = !empty($_SESSION['site_preview_theme']) && $_SESSION['site_preview_theme'] === 'magazine';

include __DIR__ . '/../../includes/header.php';
?>

<!-- Reading Progress Bar -->
<div class="mag-reading-progress-bar" id="magProgressBar"></div>

<?php if ($isPreview): ?>
<div style="background:#2563eb; color:#ffffff; padding:0.6rem 1rem; text-align:center; font-size:0.85rem; font-weight:700; position:sticky; top:0; z-index:9999; display:flex; align-items:center; justify-content:center; gap:1rem;">
    <span>⚡ You are currently previewing <strong>Tech Magazine Theme</strong></span>
    <a href="<?= ADMIN_URL ?>/themes.php" style="background:#ffffff; color:#2563eb; padding:0.25rem 0.75rem; border-radius:999px; text-decoration:none; font-size:0.8rem;">Activate in Admin</a>
    <a href="?preview_theme=reset" style="color:#ffffff; text-decoration:underline; font-size:0.8rem;">Exit Preview</a>
</div>
<?php endif; ?>

<main class="mag-article-wrap" id="main-content">
    <div class="site-container">
        <!-- Breadcrumb Navigation -->
        <nav style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:#64748b; margin-bottom:1.5rem;" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/" style="color:#2563eb; text-decoration:none;">Home</a>
            <span>/</span>
            <a href="<?= get_category_url($article['category_slug']) ?>" style="color:#64748b; text-decoration:none;"><?= h($article['category_name']) ?></a>
            <span>/</span>
            <span style="color:#0f172a; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:320px;"><?= h($article['title']) ?></span>
        </nav>

        <div class="mag-article-grid">
            <!-- Main Content Area (Left) -->
            <article>
                <header class="mag-article-head">
                    <a href="<?= get_category_url($article['category_slug']) ?>" class="mag-badge-cat">
                        <?= h($article['category_name']) ?>
                    </a>
                    <h1 class="mag-article-title"><?= h($article['title']) ?></h1>
                    <?php if (!empty($article['excerpt'])): ?>
                        <p class="mag-article-subtitle"><?= h($article['excerpt']) ?></p>
                    <?php endif; ?>

                    <div class="mag-author-bar">
                        <div class="mag-author-left">
                            <div class="mag-author-avatar">
                                <?= $authorInitials ?>
                            </div>
                            <div>
                                <div class="mag-author-name"><?= h($article['author_name']) ?></div>
                                <div class="mag-meta-sub">
                                    Published <?= date('F j, Y', strtotime($article['published_at'])) ?> &bull; <?= $readTime ?> min read
                                </div>
                            </div>
                        </div>

                        <div style="display:flex; align-items:center; gap:0.75rem; font-size:0.85rem; color:#64748b;">
                            <span>👁️ <?= (int)$article['views'] ?> views</span>
                            <span>&bull;</span>
                            <a href="#comments" style="color:#2563eb; text-decoration:none; font-weight:600;">💬 <?= $commentsCount ?> Comments</a>
                        </div>
                    </div>
                </header>

                <!-- Featured Image -->
                <?php 
                $magFeaturedImg = get_article_featured_image_url($article);
                if (!empty($magFeaturedImg)): 
                ?>
                    <div class="mag-featured-banner">
                        <img src="<?= h($magFeaturedImg) ?>" alt="<?= h($article['title']) ?>" class="mag-featured-img" loading="eager" fetchpriority="high">
                    </div>
                <?php endif; ?>

                <!-- Job Posting Card (If applicable) -->
                <?php if (!empty($article['is_job_posting']) && !empty($article['job_company'])): ?>
                    <div style="background:#0f172a; color:#ffffff; border-radius:12px; padding:1.75rem; margin-bottom:2.5rem; border:1px solid #1e293b; box-shadow:0 10px 25px -5px rgba(15, 23, 42, 0.2);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
                            <div>
                                <span style="font-size:0.75rem; text-transform:uppercase; color:#38bdf8; font-weight:800; letter-spacing:0.05em;">Verified Hiring Opening</span>
                                <h2 style="font-size:1.5rem; font-weight:800; color:#ffffff; margin:0.25rem 0 0 0;"><?= h($article['job_company']) ?></h2>
                            </div>
                            <span style="background:#22c55e; color:#ffffff; font-size:0.75rem; font-weight:800; padding:0.3rem 0.75rem; border-radius:999px;">ACTIVE</span>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:1.25rem;">
                            <?php if (!empty($article['job_location'])): ?>
                                <div style="background:#1e293b; padding:0.75rem 1rem; border-radius:8px;">
                                    <div style="font-size:0.75rem; color:#94a3b8;">Location</div>
                                    <div style="font-weight:700; color:#ffffff; font-size:0.9rem;"><?= h($article['job_location']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($article['job_salary'])): ?>
                                <div style="background:#1e293b; padding:0.75rem 1rem; border-radius:8px;">
                                    <div style="font-size:0.75rem; color:#94a3b8;">Compensation</div>
                                    <div style="font-weight:700; color:#38bdf8; font-size:0.9rem;"><?= h($article['job_salary']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($article['job_deadline'])): ?>
                                <div style="background:#1e293b; padding:0.75rem 1rem; border-radius:8px;">
                                    <div style="font-size:0.75rem; color:#94a3b8;">Deadline</div>
                                    <div style="font-weight:700; color:#f87171; font-size:0.9rem;"><?= format_date($article['job_deadline']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:0.8rem; color:#94a3b8; margin:0; line-height:1.5;">Apply exclusively through official company recruitment kiosks. Never pay any recruitment fee.</p>
                    </div>
                <?php endif; ?>

                <!-- Article Body -->
                <div class="mag-article-body">
                    <?php 
                    $cleanedArticleHtml = sanitize_article_html($article['content']);
                    if (function_exists('inject_in_article_ad')) {
                        echo inject_in_article_ad($cleanedArticleHtml);
                    } else {
                        echo $cleanedArticleHtml;
                    }
                    ?>
                </div>

                <!-- Monetization Slot: Below Content Ad -->
                <?php if (function_exists('render_monetization_ad')) render_monetization_ad('below_content_ad'); ?>

                <!-- Helpful Reaction Bar -->
                <div class="article-reactions-box" id="articleReactions" style="margin-top:3rem; border:1px solid #e2e8f0; border-radius:12px; padding:1.5rem; background:#f8fafc;">
                    <div class="article-reactions-prompt">
                        <div class="article-reactions-prompt-icon">⚡</div>
                        <div class="article-reactions-prompt-text">
                            <h4 style="color:#0f172a; font-weight:800;">Was this tech report useful?</h4>
                            <p style="color:#64748b;">Help our editorial desk prioritize future industry coverage.</p>
                        </div>
                    </div>
                    <div class="article-reactions-actions">
                        <button type="button" 
                                class="btn-reaction btn-reaction-like <?= ($reactionsData['user_reaction'] === 'like') ? 'is-active' : '' ?>" 
                                id="btnLike" 
                                data-article-id="<?= (int)$article['id'] ?>" 
                                data-type="like"
                                style="border-radius:999px;">
                            <span>👍 Useful</span>
                            <span class="reaction-counter" id="likeCounter"><?= (int)$reactionsData['likes'] ?></span>
                        </button>
                        <button type="button" 
                                class="btn-reaction btn-reaction-dislike <?= ($reactionsData['user_reaction'] === 'dislike') ? 'is-active' : '' ?>" 
                                id="btnDislike" 
                                data-article-id="<?= (int)$article['id'] ?>" 
                                data-type="dislike"
                                style="border-radius:999px;">
                            <span>👎 Dislike</span>
                            <span class="reaction-counter" id="dislikeCounter"><?= (int)$reactionsData['dislikes'] ?></span>
                        </button>
                    </div>
                </div>

                <!-- Discussion & Comments -->
                <?php if (is_section_visible('article_comments')): ?>
                <section class="article-comments-wrap" id="comments" style="margin-top:3rem;">
                    <h3 style="font-size:1.4rem; font-weight:800; color:#0f172a; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
                        <span>💬 Community Insights (<?= $commentsCount ?>)</span>
                    </h3>

                    <!-- Comment form -->
                    <div class="comment-form-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1.75rem; box-shadow:0 4px 15px -3px rgba(15,23,42,0.05);">
                        <h4 style="font-weight:800; color:#0f172a; margin-bottom:0.5rem;">Join the Conversation</h4>
                        <p style="color:#64748b; font-size:0.9rem; margin-bottom:1.25rem;">Share your analysis, question, or experience with other tech professionals.</p>

                        <?php if (!empty($_SESSION['flash_messages'])): ?>
                            <div style="margin-bottom:1.25rem;">
                                <?= display_flash_messages() ?>
                            </div>
                        <?php endif; ?>

                        <form id="articleCommentForm" method="POST" action="<?= get_article_url($article['slug']) ?>#comments">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <input type="hidden" name="article_id" value="<?= (int)$article['id'] ?>">
                            <input type="text" name="comments_bot_trap" value="" tabindex="-1" autocomplete="new-password" style="position:absolute; left:-9999px; opacity:0; pointer-events:none;" aria-hidden="true">

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                                <div>
                                    <label style="font-size:0.8rem; font-weight:700; color:#0f172a; display:block; margin-bottom:0.35rem;">Your Name *</label>
                                    <input type="text" name="user_name" class="comment-form-input" placeholder="e.g. Alex Hunter" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; font-weight:700; color:#0f172a; display:block; margin-bottom:0.35rem;">Email Address *</label>
                                    <input type="email" name="user_email" class="comment-form-input" placeholder="alex@tech.com" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;">
                                </div>
                            </div>
                            <div style="margin-bottom:1rem;">
                                <label style="font-size:0.8rem; font-weight:700; color:#0f172a; display:block; margin-bottom:0.35rem;">Comment *</label>
                                <textarea name="comment_text" rows="4" class="comment-form-input" placeholder="Write your thoughts..." required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px;"></textarea>
                            </div>
                            <button type="submit" name="submit_comment" style="background:#2563eb; color:#ffffff; font-weight:700; border:none; padding:0.75rem 1.75rem; border-radius:8px; cursor:pointer; font-size:0.9rem;">
                                Submit Comment
                            </button>
                        </form>
                    </div>

                    <!-- Comment List -->
                    <?php if (!empty($articleComments)): ?>
                        <div style="margin-top:2rem; display:flex; flex-direction:column; gap:1rem;">
                            <?php foreach ($articleComments as $comm): ?>
                                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:1.25rem;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                                        <strong style="color:#0f172a; font-size:0.95rem;"><?= h($comm['user_name']) ?></strong>
                                        <span style="font-size:0.75rem; color:#94a3b8;"><?= format_date($comm['created_at']) ?></span>
                                    </div>
                                    <p style="color:#334155; font-size:0.9rem; line-height:1.6; margin:0;"><?= nl2br(h($comm['comment_text'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                <?php endif; ?>
            </article>

            <!-- Tech Sidebar (Right) -->
            <aside>
                <!-- Editor's Picks / Trending -->
                <div class="mag-sidebar-box mag-sidebar-box-dark">
                    <div class="mag-sidebar-title">
                        <span style="color:#38bdf8;">🔥</span>
                        <span>Latest Dispatches</span>
                    </div>
                    <?php foreach ($sidebarRelated as $sPost): ?>
                        <a href="<?= get_article_url($sPost['slug']) ?>" class="mag-sidebar-post">
                            <img src="<?= get_article_featured_image_url($sPost) ?>" alt="<?= h($sPost['title']) ?>" class="mag-sidebar-thumb" loading="lazy">
                            <div>
                                <span style="font-size:0.7rem; color:#38bdf8; font-weight:700; text-transform:uppercase;"><?= h($sPost['category_name']) ?></span>
                                <div class="mag-sidebar-post-title"><?= h($sPost['title']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Category Cloud -->
                <div class="mag-sidebar-box">
                    <div class="mag-sidebar-title" style="color:#0f172a;">
                        <span>🏷️</span>
                        <span>Tech Desks</span>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                        <?php foreach ($sidebarTopics as $topic): ?>
                            <a href="<?= get_category_url($topic['slug']) ?>" style="background:#f1f5f9; color:#334155; font-size:0.8rem; font-weight:600; padding:0.35rem 0.75rem; border-radius:6px; text-decoration:none; transition:all 0.15s ease;">
                                <?= h($topic['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Newsletter / Alert Kiosk -->
                <div class="mag-sidebar-box" style="background:linear-gradient(135deg, rgba(37,99,235,0.06), rgba(6,182,212,0.06)); border-color:#93c5fd;">
                    <h4 style="font-size:1.05rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">The Verge Pulse</h4>
                    <p style="font-size:0.85rem; color:#64748b; line-height:1.5; margin-bottom:1rem;">Get our daily executive briefing on industry breakthroughs and career moves.</p>
                    <form action="<?= BASE_URL ?>/newsletter" method="POST" style="display:flex; flex-direction:column; gap:0.5rem;">
                        <input type="email" placeholder="Your work email" required style="padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.85rem;">
                        <button type="submit" style="background:#2563eb; color:#ffffff; font-weight:700; border:none; padding:0.65rem; border-radius:6px; font-size:0.85rem; cursor:pointer;">
                            Subscribe Free
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</main>

<script>
// Live Reading Progress Bar on Scroll
window.addEventListener('scroll', function() {
    var winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    var height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    var scrolled = (winScroll / height) * 100;
    var bar = document.getElementById('magProgressBar');
    if (bar) {
        bar.style.width = scrolled + '%';
    }
});
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
