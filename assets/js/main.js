/**
 * CareerNexaa - Main Frontend JavaScript (Vanilla JS)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileNavMenu = document.getElementById('mobileNavMenu');

    if (mobileMenuToggle && mobileNavMenu) {
        mobileMenuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = mobileNavMenu.hasAttribute('hidden');
            if (isHidden) {
                mobileNavMenu.removeAttribute('hidden');
                mobileMenuToggle.setAttribute('aria-expanded', 'true');
            } else {
                mobileNavMenu.setAttribute('hidden', '');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on clicking outside
        document.addEventListener('click', (e) => {
            if (!mobileNavMenu.contains(e.target) && !mobileMenuToggle.contains(e.target) && !mobileNavMenu.hasAttribute('hidden')) {
                mobileNavMenu.setAttribute('hidden', '');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 1b. Mobile Search Toggle
    const mobileSearchBtn = document.getElementById('mobileSearchBtn');
    const mobileSearchBar = document.getElementById('mobileSearchBar');
    if (mobileSearchBtn && mobileSearchBar) {
        mobileSearchBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = mobileSearchBar.hasAttribute('hidden');
            if (isHidden) {
                mobileSearchBar.removeAttribute('hidden');
                const input = mobileSearchBar.querySelector('input');
                if (input) input.focus();
            } else {
                mobileSearchBar.setAttribute('hidden', '');
            }
        });
    }

    // 1c. Topics Dropdown Click (for Touch Devices)
    const topicsMenuBtn = document.getElementById('topicsMenuBtn');
    const topicsMenuDropdown = document.getElementById('topicsMenuDropdown');
    const topicsDropdownGroup = document.querySelector('.pine-dropdown-group');
    if (topicsMenuBtn && topicsMenuDropdown && topicsDropdownGroup) {
        topicsMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = topicsDropdownGroup.classList.toggle('is-open');
            topicsMenuBtn.setAttribute('aria-expanded', isOpen);
        });

        document.addEventListener('click', (e) => {
            if (!topicsDropdownGroup.contains(e.target)) {
                topicsDropdownGroup.classList.remove('is-open');
                topicsMenuBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 1d. Notification Bell Dropdown & Native Push Handler
    const siteBellGroup = document.getElementById('siteBellGroup');
    const siteBellBtn = document.getElementById('siteBellBtn');
    const siteNotifCloseBtn = document.getElementById('siteNotifCloseBtn');
    const siteNotifPushBtn = document.getElementById('siteNotifPushBtn');

    if (siteBellGroup && siteBellBtn) {
        siteBellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = siteBellGroup.classList.toggle('is-open');
            siteBellBtn.setAttribute('aria-expanded', isOpen);
            if (isOpen && topicsDropdownGroup) {
                topicsDropdownGroup.classList.remove('is-open');
            }
        });

        if (siteNotifCloseBtn) {
            siteNotifCloseBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                siteBellGroup.classList.remove('is-open');
                siteBellBtn.setAttribute('aria-expanded', 'false');
            });
        }

        // Close when clicking anywhere outside
        document.addEventListener('click', (e) => {
            if (!siteBellGroup.contains(e.target)) {
                siteBellGroup.classList.remove('is-open');
                siteBellBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && siteBellGroup.classList.contains('is-open')) {
                siteBellGroup.classList.remove('is-open');
                siteBellBtn.setAttribute('aria-expanded', 'false');
            }
        });

        // Check if browser notifications are already enabled
        if ('Notification' in window && siteNotifPushBtn) {
            if (Notification.permission === 'granted') {
                siteNotifPushBtn.textContent = '✓ Active';
                siteNotifPushBtn.classList.add('is-active');
            } else if (Notification.permission === 'denied') {
                siteNotifPushBtn.textContent = 'Blocked';
                siteNotifPushBtn.classList.add('is-active');
            }
        }
    }

    // 2. Back To Top Button
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 350) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    // 3. Search Form Client-Side Validation
    const searchForms = document.querySelectorAll('form[role="search"], .hero-search-box form');
    searchForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const input = form.querySelector('input[name="q"]');
            if (input && input.value.trim() === '') {
                e.preventDefault();
                input.focus();
            }
        });
    });

    // 4. Auto Dismiss Flash Alerts after 6 seconds
    const flashAlerts = document.querySelectorAll('.alert');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 6000);
    });

    // 5. Interactive Article Reactions (Like / Dislike)
    const reactionButtons = document.querySelectorAll('.btn-reaction');
    const reactionFeedback = document.getElementById('reactionFeedback');

    reactionButtons.forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const articleId = btn.getAttribute('data-article-id');
            const reactionType = btn.getAttribute('data-type');
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput ? csrfTokenInput.value : '';

            if (!articleId || !reactionType) return;

            // Immediate UI responsiveness
            btn.style.transform = 'scale(0.92)';
            setTimeout(() => btn.style.transform = '', 150);

            try {
                let apiBase = window.BASE_URL || '';
                try {
                    if (apiBase.startsWith('http')) {
                        const u = new URL(apiBase);
                        apiBase = u.pathname.replace(/\/+$/, '');
                    }
                } catch (e) {}
                apiBase = apiBase.replace(/\/+$/, '');

                const response = await fetch(`${apiBase}/api/react.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        article_id: parseInt(articleId, 10),
                        type: reactionType,
                        csrf_token: csrfToken
                    })
                });

                const data = await response.json();
                if (data && data.success) {
                    // Update counters
                    const likeCounter = document.getElementById('likeCounter');
                    const dislikeCounter = document.getElementById('dislikeCounter');
                    const topLikesCount = document.getElementById('topLikesCount');

                    if (likeCounter) likeCounter.textContent = data.likes;
                    if (dislikeCounter) dislikeCounter.textContent = data.dislikes;
                    if (topLikesCount) topLikesCount.textContent = data.likes;

                    // Update active button state
                    const btnLike = document.getElementById('btnLike');
                    const btnDislike = document.getElementById('btnDislike');

                    if (btnLike && btnDislike) {
                        btnLike.classList.remove('is-active');
                        btnDislike.classList.remove('is-active');

                        if (data.user_reaction === 'like') {
                            btnLike.classList.add('is-active');
                        } else if (data.user_reaction === 'dislike') {
                            btnDislike.classList.add('is-active');
                        }
                    }

                    // Show temporary feedback toast
                    if (reactionFeedback) {
                        reactionFeedback.textContent = data.message || 'Updated';
                        reactionFeedback.style.display = 'inline-block';
                        reactionFeedback.style.opacity = '1';
                        setTimeout(() => {
                            reactionFeedback.style.opacity = '0';
                            setTimeout(() => reactionFeedback.style.display = 'none', 300);
                        }, 2500);
                    }
                } else if (data && data.message) {
                    alert(data.message);
                }
            } catch (err) {
                console.error('Reaction error:', err);
            }
        });
    });

    // 6. Interactive Article Comment Submission via AJAX
    const commentForm = document.getElementById('articleCommentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = document.getElementById('btnSubmitComment');
            const alertBox = document.getElementById('commentFormAlert');
            const nameInput = document.getElementById('comment_user_name');
            const emailInput = document.getElementById('comment_user_email');
            const textInput = document.getElementById('comment_text');
            const commentsList = document.getElementById('commentsList');
            const emptyState = document.getElementById('commentsEmptyState');
            const sectionBadge = document.getElementById('sectionCommentsBadge');
            const topCommentsCount = document.getElementById('topCommentsCount');

            const name = nameInput ? nameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            const commentText = textInput ? textInput.value.trim() : '';
            const csrfTokenInput = commentForm.querySelector('input[name="csrf_token"]');
            const articleIdInput = commentForm.querySelector('input[name="article_id"]');
            const honeypotInput = commentForm.querySelector('input[name="comments_bot_trap"]') || commentForm.querySelector('input[name="website_url"]');

            if (!name || !commentText) {
                if (alertBox) {
                    alertBox.innerHTML = '<div style="background:#fff1f2; color:#be123c; border:1px solid #fecdd3; padding:0.85rem 1.25rem; border-radius:8px; font-size:0.875rem; font-weight:600;">Please fill out your name and comment.</div>';
                    alertBox.style.display = 'block';
                }
                return;
            }

            // Disable button & show spinner
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10"></path></svg>
                    <span>Posting Comment...</span>
                `;
            }

            try {
                let apiBase = window.BASE_URL || '';
                try {
                    if (apiBase.startsWith('http')) {
                        const u = new URL(apiBase);
                        apiBase = u.pathname.replace(/\/+$/, '');
                    }
                } catch (e) {}
                apiBase = apiBase.replace(/\/+$/, '');

                const response = await fetch(`${apiBase}/api/comment.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        article_id: parseInt(articleIdInput ? articleIdInput.value : 0, 10),
                        user_name: name,
                        user_email: email,
                        comment_text: commentText,
                        csrf_token: csrfTokenInput ? csrfTokenInput.value : '',
                        comments_bot_trap: honeypotInput ? honeypotInput.value : ''
                    })
                });

                const data = await response.json();

                // If new CSRF token returned, update form token
                if (data && data.new_csrf_token && csrfTokenInput) {
                    csrfTokenInput.value = data.new_csrf_token;
                }

                if (response.ok && data && data.success) {
                    if (data.status === 'pending') {
                        // Show moderation notice in amber
                        if (alertBox) {
                            alertBox.innerHTML = '<div style="background:#fffbeb; color:#92400e; border:1px solid #fde68a; padding:0.95rem 1.25rem; border-radius:8px; font-size:0.875rem; font-weight:600; display:flex; align-items:center; gap:0.65rem;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:#d97706; flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg><span>' + (data.message || 'Thank you! Your comment has been submitted and is awaiting administrator approval.') + '</span></div>';
                            alertBox.style.display = 'block';
                            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    } else {
                        // Auto-approved path: Show success alert & prepend to public list
                        if (alertBox) {
                            alertBox.innerHTML = '<div style="background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; padding:0.85rem 1.25rem; border-radius:8px; font-size:0.875rem; font-weight:600;">' + (data.message || 'Comment posted successfully!') + '</div>';
                            alertBox.style.display = 'block';
                        }

                        // Remove empty state if present
                        if (emptyState) emptyState.remove();

                        // Compute Initials
                        const nameParts = name.split(/\s+/);
                        let initials = (nameParts[0] ? nameParts[0][0] : '') + (nameParts[1] ? nameParts[1][0] : '');
                        if (!initials) initials = 'U';
                        initials = initials.toUpperCase();

                        // Escape function
                        const escapeHtml = (str) => {
                            const div = document.createElement('div');
                            div.textContent = str;
                            return div.innerHTML;
                        };

                        // Prepend new comment
                        if (commentsList) {
                            const newCommentEl = document.createElement('div');
                            newCommentEl.className = 'comment-single-item';
                            newCommentEl.style.animation = 'fadeIn 0.4s ease';
                            newCommentEl.innerHTML = `
                                <div class="comment-avatar-bubble">${escapeHtml(initials)}</div>
                                <div class="comment-item-body">
                                    <div class="comment-item-header">
                                        <span class="comment-item-author">${escapeHtml(name)}</span>
                                        <span class="comment-item-badge">Verified Reader</span>
                                        <span class="comment-item-time">Just now</span>
                                    </div>
                                    <div class="comment-item-content">${escapeHtml(commentText).replace(/\n/g, '<br>')}</div>
                                </div>
                            `;
                            commentsList.prepend(newCommentEl);
                            newCommentEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }

                        // Increment badge counts
                        if (sectionBadge) {
                            const cur = parseInt(sectionBadge.textContent || '0', 10);
                            sectionBadge.textContent = cur + 1;
                        }
                        if (topCommentsCount) {
                            const cur = parseInt(topCommentsCount.textContent || '0', 10);
                            topCommentsCount.textContent = cur + 1;
                        }
                    }

                    // Reset comment text input
                    if (textInput) textInput.value = '';
                } else {
                    if (alertBox) {
                        alertBox.innerHTML = '<div style="background:#fff1f2; color:#be123c; border:1px solid #fecdd3; padding:0.85rem 1.25rem; border-radius:8px; font-size:0.875rem; font-weight:600;">' + (data.message || 'Failed to post comment.') + '</div>';
                        alertBox.style.display = 'block';
                    }
                }
            } catch (err) {
                console.error('Comment submission error:', err);
                if (alertBox) {
                    alertBox.innerHTML = '<div style="background:#fff1f2; color:#be123c; border:1px solid #fecdd3; padding:0.85rem 1.25rem; border-radius:8px; font-size:0.875rem; font-weight:600;">An unexpected network error occurred. Please try again.</div>';
                    alertBox.style.display = 'block';
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            }
        });
    }
});

/**
 * Global push notification permission request handler
 */
window.requestSitePushNotifications = function(btn) {
    if (!('Notification' in window)) {
        alert('This browser does not support desktop notifications.');
        return;
    }

    if (Notification.permission === 'granted') {
        if (btn) {
            btn.textContent = '✓ Active';
            btn.classList.add('is-active');
        }
        try {
            new Notification('CareerNexaa Notifications Active', {
                body: 'You are set up to receive the latest job vacancies and exam alerts!',
                icon: (window.BASE_URL || '') + '/favicon.svg'
            });
        } catch (e) {}
        return;
    }

    if (Notification.permission === 'denied') {
        alert('Notifications are currently blocked in your browser settings. Please allow notifications for CareerNexaa in your browser address bar.');
        return;
    }

    Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
            if (btn) {
                btn.textContent = '✓ Active';
                btn.classList.add('is-active');
            }
            try {
                new Notification('Instant Alerts Enabled!', {
                    body: 'You will receive verified hiring updates and career guides from CareerNexaa.',
                    icon: (window.BASE_URL || '') + '/favicon.svg'
                });
            } catch (e) {}
        } else {
            if (btn) {
                btn.textContent = 'Disabled';
            }
        }
    });
};

