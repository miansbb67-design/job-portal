<?php
/**
 * CareerNexaa - Theme 3: Clean Minimalist Article Detail Template
 * Distraction-free, typography-focused reading experience modeled after Medium and Substack.
 */

$isPreview = !empty($_SESSION['site_preview_theme']) && $_SESSION['site_preview_theme'] === 'minimalist';

include __DIR__ . '/../../includes/header.php';
?>

<!-- Google Fonts for Minimalist Serif typography -->
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
    <div class="min-reading-container">
        <!-- Breadcrumb Navigation -->
        <nav style="display:flex; align-items:center; gap:0.5rem; font-size:0.825rem; color:#777777; margin-bottom:2rem;" aria-label="Breadcrumb">
            <a href="<?= BASE_URL ?>/" style="color:#111111; text-decoration:none;">Home</a>
            <span>/</span>
            <a href="<?= get_category_url($article['category_slug']) ?>" style="color:#777777; text-decoration:none;"><?= h($article['category_name']) ?></a>
        </nav>

        <!-- Article Header -->
        <header class="min-article-header">
            <a href="<?= get_category_url($article['category_slug']) ?>" class="min-article-cat">
                <?= h($article['category_name']) ?>
            </a>
            <h1 class="min-article-title"><?= h($article['title']) ?></h1>
            <?php if (!empty($article['excerpt'])): ?>
                <p class="min-article-subhead"><?= h($article['excerpt']) ?></p>
            <?php endif; ?>

            <!-- Author & Meta Row -->
            <div class="min-author-card">
                <div class="min-author-avatar-big">
                    <?= $authorInitials ?>
                </div>
                <div>
                    <div class="min-author-name"><?= h($article['author_name']) ?></div>
                    <div class="min-author-details">
                        <?= date('M j, Y', strtotime($article['published_at'])) ?> &bull; <?= $readTime ?> min read &bull; <?= (int)$article['views'] ?> views
                    </div>
                </div>
            </div>
        </header>

        <!-- Featured Image -->
        <?php 
        $singleFeaturedImg = get_article_featured_image_url($article);
        if (!empty($singleFeaturedImg)): 
        ?>
            <div class="min-article-hero-wrap">
                <img src="<?= h($singleFeaturedImg) ?>" alt="<?= h($article['title']) ?>" class="min-article-hero-img" loading="eager" fetchpriority="high">
            </div>
        <?php endif; ?>

        <!-- Job Posting Card (Minimalist High-Clarity Styling) -->
        <?php if (!empty($article['is_job_posting']) && !empty($article['job_company'])): ?>
            <div style="background:#fafafa; border:1px solid #e5e5e5; border-radius:8px; padding:2rem; margin-bottom:3rem;">
                <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.06em; color:#1a8917; font-weight:700; margin-bottom:0.35rem;">
                    Official Career Posting
                </div>
                <h2 style="font-family:var(--min-font-serif); font-size:1.65rem; font-weight:700; color:#111111; margin:0 0 1.25rem 0;">
                    <?= h($article['job_company']) ?>
                </h2>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:1.25rem; padding-bottom:1.25rem; border-bottom:1px solid #eeeeee; margin-bottom:1.25rem;">
                    <?php if (!empty($article['job_location'])): ?>
                        <div>
                            <div style="font-size:0.75rem; color:#888888; text-transform:uppercase;">Location</div>
                            <div style="font-weight:600; color:#111111; font-size:0.95rem;"><?= h($article['job_location']) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($article['job_salary'])): ?>
                        <div>
                            <div style="font-size:0.75rem; color:#888888; text-transform:uppercase;">Compensation</div>
                            <div style="font-weight:600; color:#1a8917; font-size:0.95rem;"><?= h($article['job_salary']) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($article['job_deadline'])): ?>
                        <div>
                            <div style="font-size:0.75rem; color:#888888; text-transform:uppercase;">Deadline</div>
                            <div style="font-weight:600; color:#d93025; font-size:0.95rem;"><?= format_date($article['job_deadline']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <p style="font-size:0.85rem; color:#666666; margin:0; line-height:1.5;">
                    Verify all employment details with the employer before sharing sensitive documentation.
                </p>
            </div>
        <?php endif; ?>

        <!-- Main Editorial Prose -->
        <article class="min-prose">
            <?php 
            $cleanedArticleHtml = sanitize_article_html($article['content']);
            if (function_exists('inject_in_article_ad')) {
                echo inject_in_article_ad($cleanedArticleHtml);
            } else {
                echo $cleanedArticleHtml;
            }
            ?>
        </article>

        <!-- Monetization Slot: Below Content Ad -->
        <?php if (function_exists('render_monetization_ad')) render_monetization_ad('below_content_ad'); ?>

        <!-- Clean Feedback Action Bar -->
        <div class="min-action-bar">
            <div style="display:flex; align-items:center; gap:1rem;">
                <button type="button" 
                        class="min-clap-btn btn-reaction btn-reaction-like <?= ($reactionsData['user_reaction'] === 'like') ? 'is-active' : '' ?>" 
                        id="btnLike" 
                        data-article-id="<?= (int)$article['id'] ?>" 
                        data-type="like">
                    <span>👏 Helpful</span>
                    <span class="reaction-counter" id="likeCounter"><?= (int)$reactionsData['likes'] ?></span>
                </button>
                <button type="button" 
                        class="min-clap-btn btn-reaction btn-reaction-dislike <?= ($reactionsData['user_reaction'] === 'dislike') ? 'is-active' : '' ?>" 
                        id="btnDislike" 
                        data-article-id="<?= (int)$article['id'] ?>" 
                        data-type="dislike">
                    <span>👎</span>
                    <span class="reaction-counter" id="dislikeCounter"><?= (int)$reactionsData['dislikes'] ?></span>
                </button>
            </div>

            <div style="display:flex; align-items:center; gap:0.75rem;">
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($article['title']) ?>&url=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" style="color:#555555; text-decoration:none; font-size:0.875rem;">Share on X</a>
                <span>&bull;</span>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($canonicalUrl) ?>" target="_blank" rel="noopener noreferrer" style="color:#555555; text-decoration:none; font-size:0.875rem;">LinkedIn</a>
            </div>
        </div>

        <!-- Comments Section -->
        <?php if (is_section_visible('article_comments')): ?>
        <section class="article-comments-wrap" id="comments" style="margin-top:4rem;">
            <h3 style="font-family:var(--min-font-serif); font-size:1.75rem; font-weight:700; color:#111111; margin-bottom:1.5rem;">
                Responses (<?= $commentsCount ?>)
            </h3>

            <!-- Comment Form -->
            <div style="background:#fafafa; border:1px solid #eeeeee; border-radius:8px; padding:1.75rem; margin-bottom:2.5rem;">
                <h4 style="font-weight:700; color:#111111; margin-bottom:0.5rem; font-size:1rem;">Leave a thought</h4>
                <p style="color:#777777; font-size:0.85rem; margin-bottom:1.25rem;">What are your thoughts on this subject?</p>

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
                            <input type="text" name="user_name" placeholder="Your name" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #dddddd; border-radius:4px; font-size:0.9rem;">
                        </div>
                        <div>
                            <input type="email" name="user_email" placeholder="Your email (kept private)" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #dddddd; border-radius:4px; font-size:0.9rem;">
                        </div>
                    </div>
                    <div style="margin-bottom:1rem;">
                        <textarea name="comment_text" rows="4" placeholder="What are your thoughts?" required style="width:100%; padding:0.65rem 0.85rem; border:1px solid #dddddd; border-radius:4px; font-size:0.9rem; font-family:inherit;"></textarea>
                    </div>
                    <button type="submit" name="submit_comment" style="background:#111111; color:#ffffff; font-weight:600; border:none; padding:0.65rem 1.5rem; border-radius:999px; cursor:pointer; font-size:0.85rem;">
                        Respond
                    </button>
                </form>
            </div>

            <!-- Existing comments -->
            <?php if (!empty($articleComments)): ?>
                <div style="display:flex; flex-direction:column; gap:1.5rem;">
                    <?php foreach ($articleComments as $comm): ?>
                        <div style="padding-bottom:1.5rem; border-bottom:1px solid #eeeeee;">
                            <div style="display:flex; align-items:center; gap:0.65rem; margin-bottom:0.5rem;">
                                <span class="min-avatar-dot"><?= strtoupper(substr($comm['user_name'] ?? 'U', 0, 1)) ?></span>
                                <strong style="color:#111111; font-size:0.9rem;"><?= h($comm['user_name']) ?></strong>
                                <span style="font-size:0.75rem; color:#999999;">&bull; <?= format_date($comm['created_at']) ?></span>
                            </div>
                            <p style="color:#333333; font-size:0.95rem; line-height:1.6; margin:0;"><?= nl2br(h($comm['comment_text'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Recommended Reads (Bottom Serene Stream) -->
        <?php if (!empty($sidebarRelated)): ?>
        <section style="margin-top:5rem; padding-top:3rem; border-top:1px solid #e6e6e6;">
            <h3 style="font-family:var(--min-font-serif); font-size:1.5rem; font-weight:700; color:#111111; margin-bottom:2rem;">
                Recommended from <?= SITE_NAME ?>
            </h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:2rem;">
                <?php foreach ($sidebarRelated as $rel): ?>
                    <article>
                        <a href="<?= get_article_url($rel['slug']) ?>" style="display:block; aspect-ratio:16/10; border-radius:4px; overflow:hidden; margin-bottom:0.85rem;">
                            <img src="<?= get_article_featured_image_url($rel) ?>" alt="<?= h($rel['title']) ?>" style="width:100%; height:100%; object-fit:cover;" loading="lazy">
                        </a>
                        <div style="font-size:0.75rem; color:#1a8917; font-weight:600; text-transform:uppercase; margin-bottom:0.35rem;">
                            <?= h($rel['category_name']) ?>
                        </div>
                        <h4 style="font-family:var(--min-font-serif); font-size:1.05rem; font-weight:700; line-height:1.35; margin:0 0 0.5rem 0;">
                            <a href="<?= get_article_url($rel['slug']) ?>" style="color:#111111; text-decoration:none;">
                                <?= h($rel['title']) ?>
                            </a>
                        </h4>
                        <div style="font-size:0.75rem; color:#888888;">
                            <?= date('M j', strtotime($rel['published_at'])) ?> &bull; <?= max(1, ceil(str_word_count(strip_tags($rel['content'] ?? '')) / 200)) ?> min read
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</main>

<?php
include __DIR__ . '/../../includes/footer.php';
