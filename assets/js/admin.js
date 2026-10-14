/**
 * CareerPulse - Executive SaaS Admin JavaScript (Vanilla JS)
 * Matches PineCastTV Dashboard Interaction Standards
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Sidebar Toggle Drawer
    const adminToggleBtn = document.getElementById('adminToggleBtn');
    const adminSidebar = document.getElementById('adminSidebar') || document.querySelector('.admin-sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    window.toggleSidebarDrawer = function() {
        if (adminSidebar) {
            const isOpen = adminSidebar.classList.toggle('sidebar-open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active', isOpen);
            }
        }
    };

    // Close on clicking outside
    document.addEventListener('click', (e) => {
        if (adminSidebar && adminSidebar.classList.contains('sidebar-open')) {
            if (!adminSidebar.contains(e.target) && (!adminToggleBtn || !adminToggleBtn.contains(e.target))) {
                adminSidebar.classList.remove('sidebar-open');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.remove('active');
                }
            }
        }

        // Close table row dropdowns on clicking outside
        if (!e.target.closest('.more-dropdown-wrap')) {
            document.querySelectorAll('.row-dropdown-menu.active').forEach(menu => {
                menu.classList.remove('active');
            });
        }
    });

    // 2. Auto-generate Slug from Title
    const titleInput = document.getElementById('articleTitle') || document.getElementById('categoryName');
    const slugInput = document.getElementById('articleSlug') || document.getElementById('categorySlug');

    if (titleInput && slugInput) {
        let isSlugManuallyEdited = Boolean(slugInput.value && slugInput.value.trim() !== '');

        slugInput.addEventListener('input', () => {
            isSlugManuallyEdited = true;
        });

        titleInput.addEventListener('input', () => {
            if (!isSlugManuallyEdited || slugInput.value.trim() === '') {
                const slug = titleInput.value
                    .toLowerCase()
                    .trim()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.value = slug;
            }
        });
    }

    // 3. Article Composer: Word & Char Metrics Counter
    const contentTextarea = document.getElementById('articleContent');
    const wordCounter = document.getElementById('wordCounter');
    const charCounter = document.getElementById('charCounter');
    const readTimeCounter = document.getElementById('readTimeCounter');

    function updateComposerMetrics() {
        if (!contentTextarea || !wordCounter) return;
        let contentVal = '';
        if (window.jQuery && typeof $.fn.summernote === 'function' && $('#articleContent').next('.note-editor').length) {
            contentVal = $('#articleContent').summernote('code') || '';
        } else {
            contentVal = contentTextarea.value || '';
        }
        const raw = contentVal.replace(/<[^>]*>/g, ' ').trim();
        const words = raw.length > 0 ? raw.split(/\s+/).filter(Boolean).length : 0;
        const chars = raw.length;
        const readTime = Math.max(1, Math.ceil(words / 200));

        wordCounter.textContent = words.toLocaleString();
        if (charCounter) charCounter.textContent = chars.toLocaleString();
        if (readTimeCounter) readTimeCounter.textContent = readTime;
    }

    if (contentTextarea) {
        contentTextarea.addEventListener('input', updateComposerMetrics);
        updateComposerMetrics();
    }

    // 4. Summernote WYSIWYG Visual Editor Integration
    if (window.jQuery && typeof $.fn.summernote === 'function' && $('#articleContent').length) {
        let currentActiveEditorImg = null;

        // Track currently selected/clicked image in editor
        $(document).on('mousedown click', '.note-editable img', function() {
            currentActiveEditorImg = this;
        });

        // Double click an image to directly open the Alt Text & Position modal
        $(document).on('dblclick', '.note-editable img', function(e) {
            e.preventDefault();
            currentActiveEditorImg = this;
            if (typeof window.openImageAltModal === 'function') {
                window.openImageAltModal($(this));
            }
        });

        function getActiveSummernoteImage(context) {
            if (context && context.layoutInfo && context.layoutInfo.editor) {
                const handleTarget = $(context.layoutInfo.editor).find('.note-control-selection').data('target');
                if (handleTarget && $(handleTarget).length) {
                    return $(handleTarget);
                }
            }
            if (currentActiveEditorImg && document.body.contains(currentActiveEditorImg)) {
                return $(currentActiveEditorImg);
            }
            const $lastClicked = $('.note-editable img:focus, .note-editable img.selected');
            if ($lastClicked.length) return $lastClicked.first();
            return null;
        }

        window.syncSummernoteVal = function() {
            if ($('#articleContent').length) {
                const code = $('#articleContent').summernote('code');
                $('#articleContent').val(code);
                if (typeof updateComposerMetrics === 'function') {
                    updateComposerMetrics();
                }
            }
        };

        // 1. Custom Media Library Button for Summernote toolbar
        const MediaLibraryButton = function(context) {
            const ui = $.summernote.ui;
            const button = ui.button({
                contents: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:3px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg> Media',
                tooltip: 'Select from Media Library',
                click: function() {
                    if (typeof openMediaLibraryModal === 'function') {
                        openMediaLibraryModal({
                            target: 'content',
                            onSelect: function(imgUrl, imgName) {
                                $('#articleContent').summernote('insertImage', imgUrl, function($img) {
                                    $img.attr('alt', imgName || 'Article illustration');
                                    $img.addClass('article-body-img');
                                    $img.css({ 'max-width': '100%', 'height': 'auto', 'border-radius': '8px', 'margin': '1rem 0' });
                                });
                                showToast('✓ Image inserted from Media Library!');
                                updateComposerMetrics();
                            }
                        });
                    }
                }
            });
            return button.render();
        };

        // 2. Custom Center Alignment Button for Image Popover ("chota box")
        const ImageCenterButton = function(context) {
            const ui = $.summernote.ui;
            const button = ui.button({
                contents: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; display:inline-block;"><line x1="3" y1="6" x2="21" y2="6"></line><line x1="7" y1="12" x2="17" y2="12"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>',
                tooltip: 'Center Align Image (margin: auto)',
                click: function() {
                    const $img = getActiveSummernoteImage(context);
                    if ($img && $img.length) {
                        $img.removeClass('note-float-left note-float-right note-float-none');
                        $img.addClass('img-align-center');
                        $img.css({
                            'display': 'block',
                            'margin-left': 'auto',
                            'margin-right': 'auto',
                            'float': 'none',
                            'clear': 'both'
                        });
                        $img.attr('data-align', 'center');
                        const $parent = $img.parent('p, div');
                        if ($parent.length && $parent.text().trim() === '') {
                            $parent.css('text-align', 'center');
                        }
                        window.syncSummernoteVal();
                        showToast('✓ Image centered!');
                    }
                }
            });
            return button.render();
        };

        // 3. Custom Small (50%) & Center Button for Image Popover ("pic ko chota krr center me")
        const ImageSmallCenterButton = function(context) {
            const ui = $.summernote.ui;
            const button = ui.button({
                contents: '<span style="font-size:11px; font-weight:700; color:#059669; display:inline-flex; align-items:center; gap:2px;"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="5" y="5" width="14" height="14" rx="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg> 50% Center</span>',
                tooltip: 'Make Small (50%) & Center Align',
                click: function() {
                    const $img = getActiveSummernoteImage(context);
                    if ($img && $img.length) {
                        $img.removeClass('note-float-left note-float-right note-float-none');
                        $img.addClass('img-align-center');
                        $img.css({
                            'width': '50%',
                            'max-width': '100%',
                            'height': 'auto',
                            'display': 'block',
                            'margin-left': 'auto',
                            'margin-right': 'auto',
                            'float': 'none',
                            'clear': 'both'
                        });
                        $img.attr('data-align', 'center');
                        $img.attr('data-size', '50%');
                        const $parent = $img.parent('p, div');
                        if ($parent.length && $parent.text().trim() === '') {
                            $parent.css('text-align', 'center');
                        }
                        window.syncSummernoteVal();
                        showToast('✓ Picture resized to 50% and centered!');
                    }
                }
            });
            return button.render();
        };

        // 4. Custom Alt Text Button for Image Popover ("alt text likhane ka bi add kro")
        const ImageAltButton = function(context) {
            const ui = $.summernote.ui;
            const button = ui.button({
                contents: '<span style="font-size:11px; font-weight:700; color:#2563eb; display:inline-flex; align-items:center; gap:3px;"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg> Alt Text</span>',
                tooltip: 'Edit Alt Text & Position (SEO)',
                click: function() {
                    const $img = getActiveSummernoteImage(context);
                    if ($img && $img.length) {
                        if (typeof window.openImageAltModal === 'function') {
                            window.openImageAltModal($img);
                        }
                    } else {
                        showToast('Please click an image to edit Alt text.');
                    }
                }
            });
            return button.render();
        };

        $('#articleContent').summernote({
            placeholder: 'Compose high quality, original content here...',
            height: 480,
            minHeight: 320,
            dialogsInBody: true,
            buttons: {
                mediaLibrary: MediaLibraryButton,
                imageCenter: ImageCenterButton,
                imageSmallCenter: ImageSmallCenterButton,
                imageAlt: ImageAltButton
            },
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video', 'hr', 'mediaLibrary']],
                ['view', ['fullscreen', 'codeview', 'help']],
                ['history', ['undo', 'redo']]
            ],
            popover: {
                image: [
                    ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter', 'resizeNone']],
                    ['float', ['floatLeft', 'imageCenter', 'floatRight', 'floatNone']],
                    ['custom', ['imageSmallCenter', 'imageAlt']],
                    ['remove', ['removeMedia']]
                ],
                link: [
                    ['link', ['linkDialogShow', 'unlink']]
                ],
                table: [
                    ['add', ['addRowDown', 'addRowUp', 'addColLeft', 'addColRight']],
                    ['delete', ['deleteRow', 'deleteCol', 'deleteTable']]
                ],
                air: []
            },
            callbacks: {
                onInit: function() {
                    updateComposerMetrics();
                },
                onChange: function(contents, $editable) {
                    $('#articleContent').val(contents);
                    updateComposerMetrics();
                },
                onImageUpload: function(files) {
                    for (let i = 0; i < files.length; i++) {
                        uploadSummernoteImage(files[i]);
                    }
                }
            }
        });

        function uploadSummernoteImage(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'upload');

            showToast('⏳ Uploading image...');

            fetch((window.ADMIN_URL || '') + '/ajax-media.php?action=upload', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.file && data.file.url) {
                    $('#articleContent').summernote('insertImage', data.file.url, function($image) {
                        $image.attr('alt', data.file.name || 'Article Image');
                        $image.addClass('article-body-img');
                        $image.css({ 'max-width': '100%', 'height': 'auto', 'border-radius': '8px', 'margin': '1rem 0' });
                    });
                    showToast('✓ Image uploaded & inserted successfully!');
                    updateComposerMetrics();
                } else {
                    showToast('❌ Image upload failed: ' + (data.message || 'Unknown error'));
                    alert('Image upload failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Summernote upload error:', err);
                showToast('❌ Server error while uploading image');
                alert('Server error while uploading image. Please check file format and size.');
            });
        }

        // Pre-built templates insertion with Summernote
        const btnJobTemplate = document.getElementById('btnJobTemplate');
        if (btnJobTemplate) {
            btnJobTemplate.addEventListener('click', (e) => {
                e.preventDefault();
                const currentCode = $('#articleContent').summernote('code');
                const hasContent = currentCode && currentCode.replace(/<[^>]*>/g, '').trim().length > 0;
                if (hasContent && !confirm('Insert Job Circular template? This will append the structured template.')) {
                    return;
                }
                const jobTemplateHtml = `<h2>Position Overview</h2>
<p>We are seeking a dedicated, highly organized, and results-driven professional to join our expanding team. This role offers strong career growth potential, a collaborative workplace environment, and competitive compensation.</p>

<h2>Key Responsibilities & Core Duties</h2>
<ul>
  <li>Coordinate day-to-day operations and execute assigned departmental tasks efficiently.</li>
  <li>Collaborate closely with cross-functional team members to achieve operational milestones.</li>
  <li>Maintain comprehensive documentation and adhere strictly to safety and quality standards.</li>
  <li>Identify process improvement opportunities and assist with workflow optimization.</li>
</ul>

<h2>Candidate Qualifications & Skills</h2>
<ul>
  <li>Relevant educational credentials (degree, diploma, or verified practical trade experience).</li>
  <li>Demonstrated track record of reliability, team collaboration, and strong attention to detail.</li>
  <li>Proficiency with standard industry software, communication tools, and job equipment.</li>
  <li>Ability to adapt quickly in a fast-paced, high-performance working environment.</li>
</ul>

<h2>Compensation, Benefits & Perks</h2>
<ul>
  <li>Competitive base salary commensurate with experience plus performance-based incentives.</li>
  <li>Comprehensive healthcare coverage including medical, dental, and vision support.</li>
  <li>Paid time off (PTO), recognized national holidays, and professional development stipends.</li>
  <li>Structured career progression pathways and internal mentorship programs.</li>
</ul>

<h2>How to Apply</h2>
<p>Interested and qualified candidates are encouraged to apply immediately. Please submit your up-to-date resume and a brief introductory cover letter through our designated application link below.</p>`;

                $('#articleContent').summernote('pasteHTML', jobTemplateHtml);
                const isJobToggle = document.getElementById('isJobPosting');
                const jobPostingFields = document.getElementById('jobPostingFields');
                if (isJobToggle && !isJobToggle.checked) {
                    isJobToggle.checked = true;
                    if (jobPostingFields) jobPostingFields.style.display = 'block';
                }
                updateComposerMetrics();
            });
        }

        const btnGuideTemplate = document.getElementById('btnGuideTemplate');
        if (btnGuideTemplate) {
            btnGuideTemplate.addEventListener('click', (e) => {
                e.preventDefault();
                const currentCode = $('#articleContent').summernote('code');
                const hasContent = currentCode && currentCode.replace(/<[^>]*>/g, '').trim().length > 0;
                if (hasContent && !confirm('Insert Career Guide template? This will append the structured template.')) {
                    return;
                }
                const guideTemplateHtml = `<h2>Introduction: Navigating Today's Career Landscape</h2>
<p>Navigating the modern employment landscape demands strategic foresight, verified industry skillsets, and proactive career management. Whether you are entering the workforce or seeking a major promotion, adhering to proven industry standards significantly accelerates your trajectory.</p>

<h2>1. Understanding Market Demand & Emerging Skills</h2>
<p>Employers today look beyond basic credentials. They prioritize professionals who demonstrate technical fluency, data literacy, and cross-functional leadership agility.</p>

<h2>2. Core Competencies That Drive Higher Compensation</h2>
<ul>
  <li><strong>Technical Mastery:</strong> Deep knowledge of foundational principles and modern stacks.</li>
  <li><strong>Effective Communication:</strong> Delivering concise, data-backed insights to executive leadership.</li>
  <li><strong>Process Optimization:</strong> Eliminating workflow bottlenecks to deliver tangible business ROI.</li>
</ul>

<h2>3. Actionable Step-by-Step Career Roadmap</h2>
<ol>
  <li><strong>ATS Resume Optimization:</strong> Format credentials with quantifiable metric achievements.</li>
  <li><strong>Portfolio Verification:</strong> Showcase real-world case studies, certifications, and portfolio projects.</li>
  <li><strong>Strategic Networking:</strong> Engage with active industry communities, alumni groups, and mentors.</li>
</ol>

<blockquote>
  <p>Long-term career success is rarely accidental. It is the cumulative result of continuous deliberate learning, strategic positioning, and unwavering professional execution.</p>
</blockquote>

<h2>Final Takeaways & Action Plan</h2>
<p>Take decisive action today by auditing your current skillset against top listings in your industry. Commit to mastering one high-value capability each quarter to maintain a decisive competitive edge.</p>`;

                $('#articleContent').summernote('pasteHTML', guideTemplateHtml);
                updateComposerMetrics();
            });
        }

        // Ensure textarea has the latest HTML before form submit
        const composerForm = $('#articleContent').closest('form');
        if (composerForm.length) {
            composerForm.on('submit', function() {
                const code = $('#articleContent').summernote('code');
                $('#articleContent').val(code);
            });
        }
    }

    // 5. Job Circular Toggle Switch
    const isJobPostingCheckbox = document.getElementById('isJobPosting');
    const jobPostingFields = document.getElementById('jobPostingFields');
    if (isJobPostingCheckbox && jobPostingFields) {
        isJobPostingCheckbox.addEventListener('change', () => {
            jobPostingFields.style.display = isJobPostingCheckbox.checked ? 'block' : 'none';
        });
    }

    // 6. Keyboard Shortcut (Ctrl+S / Cmd+S to submit article form)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const articleForm = document.getElementById('articleForm');
            if (articleForm) {
                e.preventDefault();
                articleForm.submit();
            }
        }
    });
});

/**
 * Global Helpers
 */

// 1. Submit Article Form (Called by Topbar 'Save' Button)
function submitArticleForm() {
    const form = document.getElementById('articleForm');
    if (form) {
        form.submit();
    }
}

// 2. Video Radio Pill Selection Handler (Screenshot 3)
function handleVideoOptionChange(radio) {
    document.querySelectorAll('.video-pill').forEach(pill => {
        pill.classList.remove('active');
    });
    if (radio.checked) {
        radio.closest('.video-pill').classList.add('active');
    }
    const container = document.getElementById('videoUrlContainer');
    if (container) {
        container.style.display = (radio.value !== 'none') ? 'block' : 'none';
    }
}

// 3. Settings Tab Switcher (SEO / Social / Advanced - Screenshots 4, 5)
function switchSettingsTab(tabId, btn) {
    document.querySelectorAll('.settings-tabs-header .tab-btn').forEach(b => {
        b.classList.remove('active');
    });
    btn.classList.add('active');

    document.querySelectorAll('.settings-tab-pane').forEach(pane => {
        pane.style.display = 'none';
        pane.classList.remove('active');
    });

    const activePane = document.getElementById(tabId);
    if (activePane) {
        activePane.style.display = 'block';
        activePane.classList.add('active');
    }
}

// 4. Image Preview Handler (Featured Image & Thumbnail)
function previewSelectedImage(input, previewImgId, placeholderId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById(previewImgId);
            if (previewImg) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
            }
            if (placeholderId) {
                const placeholder = document.getElementById(placeholderId);
                if (placeholder) placeholder.style.display = 'none';
            }
            if (previewImgId === 'featuredImagePreview') {
                const rBtn = document.getElementById('btnRemoveFeatured');
                const rHdr = document.getElementById('btnRemoveFeaturedHeader');
                const rFlt = document.getElementById('btnRemoveFeaturedFloat');
                if (rBtn) rBtn.style.display = 'inline-flex';
                if (rHdr) rHdr.style.display = 'inline-block';
                if (rFlt) rFlt.style.display = 'flex';
            } else if (previewImgId === 'thumbMiniPreview') {
                const rBtn = document.getElementById('btnRemoveThumb');
                if (rBtn) rBtn.style.display = 'inline-flex';
            }
        };
        reader.readAsDataURL(file);
    }
}

// Remove Featured Image Handler
function removeFeaturedImage(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const fileInput = document.getElementById('featuredImageInput');
    const hiddenInput = document.getElementById('existingFeaturedImage');
    const preview = document.getElementById('featuredImagePreview');
    const placeholder = document.getElementById('imagePlaceholder');
    const removeBtn = document.getElementById('btnRemoveFeatured');
    const removeBtnHeader = document.getElementById('btnRemoveFeaturedHeader');
    const removeFloat = document.getElementById('btnRemoveFeaturedFloat');

    if (fileInput) fileInput.value = '';
    if (hiddenInput) hiddenInput.value = '';
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    if (placeholder) {
        placeholder.style.display = 'flex';
    }
    if (removeBtn) removeBtn.style.display = 'none';
    if (removeBtnHeader) removeBtnHeader.style.display = 'none';
    if (removeFloat) removeFloat.style.display = 'none';

    showToast('✓ Featured image removed');
}

// Remove Thumbnail Image Handler
function removeThumbnailImage(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const fileInput = document.getElementById('thumbImageInput');
    const hiddenInput = document.getElementById('existingThumbnailImage');
    const preview = document.getElementById('thumbMiniPreview');
    const removeBtn = document.getElementById('btnRemoveThumb');

    if (fileInput) fileInput.value = '';
    if (hiddenInput) hiddenInput.value = '';
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    if (removeBtn) removeBtn.style.display = 'none';

    showToast('✓ Thumbnail removed');
}

// 5. Toggle Row Dropdown Menu in Articles Table (...)
function toggleRowDropdown(button) {
    const menu = button.nextElementSibling;
    const isCurrentlyActive = menu.classList.contains('active');
    document.querySelectorAll('.row-dropdown-menu.active').forEach(m => m.classList.remove('active'));
    if (!isCurrentlyActive) {
        menu.classList.add('active');
    }
}

// 6. Copy Article URL to Clipboard with Toast Notification
function copyArticleUrl(url) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('✓ Article link copied to clipboard!');
        }).catch(() => {
            fallbackCopyText(url);
        });
    } else {
        fallbackCopyText(url);
    }
}

function fallbackCopyText(text) {
    const tempInput = document.createElement('input');
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);
    showToast('✓ Article link copied to clipboard!');
}

function showToast(message) {
    let toast = document.getElementById('adminToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'adminToast';
        toast.className = 'admin-toast';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 2400);
}

// 7. Show Article Stats Alert/Modal
function showArticleStats(id, title, views, plays) {
    alert(`📊 Article Analytics:\n\nTitle: ${title}\nTotal Pageviews: ${views}\nMedia Plays: ${plays}`);
}

// 8. Sidebar Nav Group Accordion Toggle (Screenshot 2)
function toggleNavGroup(button) {
    const group = button.closest('.admin-nav-group');
    if (group) {
        group.classList.toggle('open');
    }
}

// ==========================================================================
// 9. Modern Notification Center & Bell Dropdown Toggle
// ==========================================================================
function toggleNotificationDropdown(event) {
    if (event) {
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
        event.preventDefault();
    }
    const wrap = document.getElementById('adminBellWrap') || document.querySelector('.admin-bell-wrap');
    const dropdown = document.getElementById('adminNotificationsDropdown');
    const btn = document.getElementById('adminBellBtn') || document.querySelector('.admin-bell-btn');

    if (!dropdown) return;

    const isOpen = dropdown.classList.contains('show') || dropdown.classList.contains('active') || (wrap && wrap.classList.contains('open'));
    if (isOpen) {
        dropdown.classList.remove('show', 'active');
        if (wrap) wrap.classList.remove('open');
        if (btn) btn.setAttribute('aria-expanded', 'false');
    } else {
        dropdown.classList.add('show', 'active');
        if (wrap) wrap.classList.add('open');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    }
}

function closeNotificationDropdown() {
    const wrap = document.getElementById('adminBellWrap') || document.querySelector('.admin-bell-wrap');
    const dropdown = document.getElementById('adminNotificationsDropdown');
    const btn = document.getElementById('adminBellBtn') || document.querySelector('.admin-bell-btn');
    if (dropdown) dropdown.classList.remove('show', 'active');
    if (wrap) wrap.classList.remove('open');
    if (btn) btn.setAttribute('aria-expanded', 'false');
}

function markAllNotificationsRead(event) {
    if (event) {
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
        event.preventDefault();
    }

    // Hide bell red dot
    const bellDot = document.getElementById('bellDot');
    if (bellDot) {
        bellDot.style.display = 'none';
    }

    // Update count badge to "0 new"
    const badge = document.getElementById('notifBadge');
    if (badge) {
        badge.textContent = '0 new';
        badge.classList.add('zero');
    }

    // Mark all visible notification items as read
    document.querySelectorAll('.notif-item.unread').forEach(item => {
        item.classList.remove('unread');
        item.classList.add('read');
        const dot = item.querySelector('.notif-unread-dot');
        if (dot) dot.remove();
    });

    // Save timestamp locally in seconds
    const nowSec = Math.floor(Date.now() / 1000);
    localStorage.setItem('admin_last_read_notif_time', nowSec.toString());

    // Save state on server via AJAX
    try {
        const adminUrl = window.ADMIN_URL || '/job-portal/admin';
        fetch(adminUrl + '/ajax-notifications.php?action=mark_all_read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).catch(e => console.warn('Notification sync notice:', e));
    } catch (e) {
        console.warn('Notification sync failed:', e);
    }
}

// Expose explicitly to global window object
window.toggleNotificationDropdown = toggleNotificationDropdown;
window.closeNotificationDropdown = closeNotificationDropdown;
window.markAllNotificationsRead = markAllNotificationsRead;

// Global click-outside listener to close dropdown
document.addEventListener('click', function (e) {
    const wrap = document.getElementById('adminBellWrap') || document.querySelector('.admin-bell-wrap');
    if (wrap && !wrap.contains(e.target)) {
        closeNotificationDropdown();
    }
});

// Escape key listener to close dropdowns & modals
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeNotificationDropdown();
        closeMediaLibraryModal();
        if (typeof window.closeImageAltModal === 'function') {
            window.closeImageAltModal();
        }
    }
});

// Sync notifications with localStorage on page load
document.addEventListener('DOMContentLoaded', function () {
    const lastRead = parseInt(localStorage.getItem('admin_last_read_notif_time') || '0', 10);
    if (lastRead > 0) {
        let remainingUnread = 0;
        document.querySelectorAll('.notif-item[data-time]').forEach(item => {
            const itemTime = parseInt(item.getAttribute('data-time') || '0', 10);
            if (itemTime <= lastRead) {
                item.classList.remove('unread');
                item.classList.add('read');
                const dot = item.querySelector('.notif-unread-dot');
                if (dot) dot.remove();
            } else {
                remainingUnread++;
            }
        });

        if (remainingUnread === 0) {
            const bellDot = document.getElementById('bellDot');
            if (bellDot) bellDot.style.display = 'none';
            const badge = document.getElementById('notifBadge');
            if (badge) {
                badge.textContent = '0 new';
                badge.classList.add('zero');
            }
        }
    }
});

// ==========================================================================
// 9.5 Global Image Alt Text, Alignment & Size Modal Controller
// ==========================================================================
let currentEditingImg = null;
let currentModalAlign = 'center';
let currentModalSize = '100%';

window.openImageAltModal = function($img) {
    if (!$img || !$img.length) return;
    currentEditingImg = $img;

    const src = $img.attr('src') || '';
    const alt = $img.attr('alt') || '';

    // Determine current alignment
    const styleFloat = ($img.css('float') || '').toLowerCase();
    const styleAttr = $img.attr('style') || '';
    const isMarginCenter = ($img.css('margin-left') === 'auto' && $img.css('margin-right') === 'auto') || 
                           styleAttr.includes('margin: auto') || 
                           styleAttr.includes('margin: 0 auto') || 
                           styleAttr.includes('margin: 1.5rem auto') ||
                           $img.hasClass('img-align-center') ||
                           $img.attr('data-align') === 'center';

    if (styleFloat === 'left' || $img.hasClass('note-float-left')) {
        currentModalAlign = 'left';
    } else if (styleFloat === 'right' || $img.hasClass('note-float-right')) {
        currentModalAlign = 'right';
    } else if (isMarginCenter) {
        currentModalAlign = 'center';
    } else {
        currentModalAlign = 'none';
    }

    // Determine current size
    const widthStyle = ($img[0].style.width || '').trim();
    const dataSize = $img.attr('data-size') || '';
    if (widthStyle === '100%' || dataSize === '100%') {
        currentModalSize = '100%';
    } else if (widthStyle === '50%' || dataSize === '50%') {
        currentModalSize = '50%';
    } else if (widthStyle === '25%' || dataSize === '25%') {
        currentModalSize = '25%';
    } else if (widthStyle === '' || widthStyle === 'auto') {
        currentModalSize = 'auto';
    } else {
        currentModalSize = 'auto';
    }

    const preview = document.getElementById('imgModalPreview');
    const altInput = document.getElementById('imgModalAltInput');
    if (preview) preview.src = src;
    if (altInput) {
        altInput.value = alt;
        setTimeout(() => {
            altInput.focus();
            altInput.select();
        }, 120);
    }

    window.updateModalAlignUI(currentModalAlign);
    window.updateModalSizeUI(currentModalSize);

    const modal = document.getElementById('imageAltModal');
    if (modal) modal.style.display = 'flex';
};

window.closeImageAltModal = function(e) {
    if (e && e.target && e.target.id !== 'imageAltModal' && !e.target.classList.contains('media-modal-close-btn')) {
        return;
    }
    const modal = document.getElementById('imageAltModal');
    if (modal) modal.style.display = 'none';
};

window.setModalImgAlign = function(align) {
    currentModalAlign = align;
    window.updateModalAlignUI(align);
};

window.updateModalAlignUI = function(align) {
    if (window.jQuery) {
        $('#imgModalAlignGroup .btn-img-choice').removeClass('active');
        $(`#imgModalAlignGroup .btn-img-choice[data-align="${align}"]`).addClass('active');
    }
};

window.setModalImgSize = function(size) {
    currentModalSize = size;
    window.updateModalSizeUI(size);
};

window.updateModalSizeUI = function(size) {
    if (window.jQuery) {
        $('#imgModalSizeGroup .btn-img-choice').removeClass('active');
        $(`#imgModalSizeGroup .btn-img-choice[data-size="${size}"]`).addClass('active');
    }
};

window.saveImageAltModal = function() {
    if (!currentEditingImg || !currentEditingImg.length) {
        window.closeImageAltModal();
        return;
    }
    const altInput = document.getElementById('imgModalAltInput');
    const newAlt = altInput ? altInput.value.trim() : '';

    // 1. Update Alt attribute
    currentEditingImg.attr('alt', newAlt);

    // 2. Update Alignment
    currentEditingImg.removeClass('note-float-left note-float-right note-float-none img-align-center');
    if (currentModalAlign === 'center') {
        currentEditingImg.addClass('img-align-center');
        currentEditingImg.css({
            'display': 'block',
            'margin-left': 'auto',
            'margin-right': 'auto',
            'float': 'none',
            'clear': 'both'
        });
        currentEditingImg.attr('data-align', 'center');
        const $parent = currentEditingImg.parent('p, div');
        if ($parent.length && $parent.text().trim() === '') {
            $parent.css('text-align', 'center');
        }
    } else if (currentModalAlign === 'left') {
        currentEditingImg.addClass('note-float-left');
        currentEditingImg.css({
            'display': 'inline-block',
            'float': 'left',
            'margin': '0.5rem 1.5rem 1.25rem 0',
            'clear': 'none'
        });
        currentEditingImg.attr('data-align', 'left');
    } else if (currentModalAlign === 'right') {
        currentEditingImg.addClass('note-float-right');
        currentEditingImg.css({
            'display': 'inline-block',
            'float': 'right',
            'margin': '0.5rem 0 1.25rem 1.5rem',
            'clear': 'none'
        });
        currentEditingImg.attr('data-align', 'right');
    } else {
        currentEditingImg.css({
            'display': 'inline-block',
            'float': 'none',
            'margin': '0.75rem 0',
            'clear': 'both'
        });
        currentEditingImg.removeAttr('data-align');
    }

    // 3. Update Size
    if (currentModalSize === '100%') {
        currentEditingImg.css({ 'width': '100%', 'height': 'auto' });
        currentEditingImg.attr('data-size', '100%');
    } else if (currentModalSize === '50%') {
        currentEditingImg.css({ 'width': '50%', 'max-width': '100%', 'height': 'auto' });
        currentEditingImg.attr('data-size', '50%');
    } else if (currentModalSize === '25%') {
        currentEditingImg.css({ 'width': '25%', 'max-width': '100%', 'height': 'auto' });
        currentEditingImg.attr('data-size', '25%');
    } else if (currentModalSize === 'auto') {
        currentEditingImg.css({ 'width': '', 'height': 'auto' });
        currentEditingImg.removeAttr('data-size');
    }

    window.closeImageAltModal();
    if (typeof window.syncSummernoteVal === 'function') {
        window.syncSummernoteVal();
    } else if (window.jQuery && $('#articleContent').length) {
        $('#articleContent').val($('#articleContent').summernote('code'));
        if (typeof updateComposerMetrics === 'function') updateComposerMetrics();
    }
    showToast('✓ Image Alt text & formatting updated successfully!');
};

// ==========================================================================
// 10. Global Media Library Modal & Computer File Upload System (Screenshots 2 & 3)
// ==========================================================================
let allMediaFiles = [];
let filteredMediaFiles = [];
let mediaCurrentPage = 1;
const mediaPageSize = 24; // 24 items per page (6x4 grid matching Screenshot 2)
let currentMediaCallback = null;

function openMediaLibraryModal(options = {}) {
    const modal = document.getElementById('mediaLibraryModal');
    if (!modal) return;

    currentMediaCallback = options.onSelect || null;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Reset search and filter
    const searchInput = document.getElementById('mediaModalSearchInput');
    if (searchInput) searchInput.value = '';
    const filterSelect = document.getElementById('mediaModalFilterSelect');
    if (filterSelect) filterSelect.value = 'all';

    // Fetch live media assets
    loadMediaLibraryFiles();
}

function closeMediaLibraryModal(e) {
    if (e && e.target && e.target.id !== 'mediaLibraryModal' && !e.target.classList.contains('media-modal-close-btn')) {
        return;
    }
    const modal = document.getElementById('mediaLibraryModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
    currentMediaCallback = null;
}

function loadMediaLibraryFiles() {
    const grid = document.getElementById('mediaLibraryGrid');
    const empty = document.getElementById('mediaModalEmpty');
    if (grid) {
        grid.innerHTML = '<div style="grid-column: 1 / -1; padding: 2.5rem; text-align: center; color: #64748b;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="margin-bottom:0.5rem; animation: pulseDot 1.5s infinite;"><circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle><path d="M12 2a10 10 0 0 1 10 10"></path></svg><p style="margin:0; font-size:0.85rem; font-weight:600;">Loading Media Library Assets...</p></div>';
    }
    if (empty) empty.style.display = 'none';

    const adminUrl = window.ADMIN_URL || '/job-portal/admin';
    fetch(adminUrl + '/ajax-media.php?action=list', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && Array.isArray(data.files)) {
            allMediaFiles = data.files;
            filteredMediaFiles = [...allMediaFiles];
            mediaCurrentPage = 1;
            renderMediaGrid();
        } else {
            if (grid) grid.innerHTML = '<div style="grid-column:1/-1; padding:2rem; text-align:center; color:#ef4444;">Failed to load media assets.</div>';
        }
    })
    .catch(err => {
        console.error('Failed to load media:', err);
        if (grid) grid.innerHTML = '<div style="grid-column:1/-1; padding:2rem; text-align:center; color:#ef4444;">Error loading media library.</div>';
    });
}

function renderMediaGrid() {
    const grid = document.getElementById('mediaLibraryGrid');
    const empty = document.getElementById('mediaModalEmpty');
    const prevBtn = document.getElementById('mediaPagiPrev');
    const nextBtn = document.getElementById('mediaPagiNext');
    const pageInfo = document.getElementById('mediaPagiInfo');

    if (!grid) return;

    if (filteredMediaFiles.length === 0) {
        grid.innerHTML = '';
        if (empty) empty.style.display = 'block';
        if (pageInfo) pageInfo.textContent = 'Page 0 of 0';
        if (prevBtn) prevBtn.disabled = true;
        if (nextBtn) nextBtn.disabled = true;
        return;
    }

    if (empty) empty.style.display = 'none';

    const totalPages = Math.ceil(filteredMediaFiles.length / mediaPageSize) || 1;
    if (mediaCurrentPage > totalPages) mediaCurrentPage = totalPages;
    if (mediaCurrentPage < 1) mediaCurrentPage = 1;

    const startIndex = (mediaCurrentPage - 1) * mediaPageSize;
    const pageItems = filteredMediaFiles.slice(startIndex, startIndex + mediaPageSize);

    let html = '';
    pageItems.forEach(item => {
        const safeName = (item.name || 'image').replace(/'/g, "\\'");
        const safeUrl = (item.url || '').replace(/'/g, "\\'");
        const safeRel = (item.rel_path || item.url || '').replace(/'/g, "\\'");
        html += `
            <div class="media-card-item" title="${item.name}" onclick="selectMediaLibraryItem('${safeUrl}', '${safeName}', '${safeRel}')">
                <img src="${item.url}" alt="${item.name}" loading="lazy" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'100\\' height=\\'80\\' viewBox=\\'0 0 100 80\\'><rect fill=\\'%23f1f5f9\\' width=\\'100\\' height=\\'80\\'/><text fill=\\'%2394a3b8\\' x=\\'50%\\' y=\\'50%\\' dominant-baseline=\\'middle\\' text-anchor=\\'middle\\' font-size=\\'10\\'>Image</text></svg>'">
                <div class="media-card-overlay">
                    <span class="media-card-insert-badge">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Insert
                    </span>
                </div>
            </div>
        `;
    });

    grid.innerHTML = html;

    if (pageInfo) pageInfo.textContent = `Page ${mediaCurrentPage} of ${totalPages}`;
    if (prevBtn) prevBtn.disabled = (mediaCurrentPage <= 1);
    if (nextBtn) nextBtn.disabled = (mediaCurrentPage >= totalPages);
}

function handleMediaModalSearch(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        filteredMediaFiles = [...allMediaFiles];
    } else {
        filteredMediaFiles = allMediaFiles.filter(item => item.name.toLowerCase().includes(q));
    }
    mediaCurrentPage = 1;
    renderMediaGrid();
}

function handleMediaModalFilter(filterVal) {
    if (filterVal === 'recent') {
        filteredMediaFiles = [...allMediaFiles].slice().sort((a, b) => (b.modified || 0) - (a.modified || 0));
    } else {
        filteredMediaFiles = [...allMediaFiles];
    }
    mediaCurrentPage = 1;
    renderMediaGrid();
}

function mediaModalChangePage(delta) {
    mediaCurrentPage += delta;
    renderMediaGrid();
    const body = document.getElementById('mediaModalBody');
    if (body) body.scrollTop = 0;
}

function selectMediaLibraryItem(url, name, relPath) {
    if (typeof currentMediaCallback === 'function') {
        currentMediaCallback(url, name, relPath);
    }
    closeMediaLibraryModal();
}

function triggerMediaComputerUpload() {
    const fileInput = document.getElementById('mediaModalComputerFileInput');
    if (fileInput) {
        fileInput.value = '';
        fileInput.click(); // Opens Native OS File Picker (Screenshot 3)
    }
}

function uploadMediaFileDirectly(input) {
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];

    const uploadBtn = document.getElementById('btnMediaModalUpload');
    const labelSpan = document.getElementById('mediaUploadBtnLabel');
    const originalText = labelSpan ? labelSpan.textContent : 'Upload';

    if (uploadBtn) uploadBtn.disabled = true;
    if (labelSpan) labelSpan.textContent = 'Uploading...';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'upload');

    const adminUrl = window.ADMIN_URL || '/job-portal/admin';

    fetch(adminUrl + '/ajax-media.php?action=upload', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success' && data.file) {
            showToast('✓ Image uploaded successfully!');
            allMediaFiles.unshift(data.file);
            filteredMediaFiles.unshift(data.file);
            mediaCurrentPage = 1;
            renderMediaGrid();
        } else {
            alert('Upload failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Upload failed: ' + err.message);
    })
    .finally(() => {
        if (uploadBtn) uploadBtn.disabled = false;
        if (labelSpan) labelSpan.textContent = originalText;
    });
}

// Helper to dynamically update Dropify preview from external sources (e.g. Media Library)
function updateDropifyInstance(inputSelector, imgUrl) {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.dropify !== 'undefined') {
        const $input = $(inputSelector);
        if ($input.length) {
            let dr = $input.data('dropify');
            if (dr) {
                dr.resetPreview();
                dr.clearElement();
                dr.settings.defaultFile = imgUrl;
                dr.destroy();
                dr.init();
            }
        }
    }
}

// Helper for right sidebar: Select Featured Image from Media Library
function selectFeaturedFromMedia() {
    openMediaLibraryModal({
        target: 'featured',
        onSelect: function(imgUrl, imgName, relPath) {
            const hiddenInput = document.getElementById('existingFeaturedImage');
            if (hiddenInput) hiddenInput.value = relPath || imgUrl;
            
            // Update Dropify preview
            updateDropifyInstance('#featuredImageInput', imgUrl);

            // Legacy fallback if elements exist
            const preview = document.getElementById('featuredImagePreview');
            const placeholder = document.getElementById('imagePlaceholder');
            if (preview) {
                preview.src = imgUrl;
                preview.style.display = 'block';
            }
            if (placeholder) placeholder.style.display = 'none';

            showToast('✓ Featured image selected from library!');
        }
    });
}

// Helper for right sidebar: Select Thumbnail Image from Media Library
function selectThumbnailFromMedia() {
    openMediaLibraryModal({
        target: 'thumbnail',
        onSelect: function(imgUrl, imgName, relPath) {
            const hiddenInput = document.getElementById('existingThumbnailImage');
            if (hiddenInput) hiddenInput.value = relPath || imgUrl;

            // Update Dropify preview
            updateDropifyInstance('#thumbImageInput', imgUrl);

            // Legacy fallback if elements exist
            const preview = document.getElementById('thumbMiniPreview');
            if (preview) {
                preview.src = imgUrl;
                preview.style.display = 'block';
            }

            showToast('✓ Thumbnail selected from library!');
        }
    });
}

// Global Dropify Initialization
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.dropify !== 'undefined') {
        // Initialize Featured Image Dropify
        const $featuredInput = $('#featuredImageInput');
        if ($featuredInput.length) {
            const drFeatured = $featuredInput.dropify({
                messages: {
                    'default': 'Drag and drop an image here or click to browse',
                    'replace': 'Drag and drop or click to replace image',
                    'remove':  'Remove',
                    'error':   'Oops, something went wrong with the file.'
                },
                error: {
                    'fileSize': 'The file size is too big ({{ value }} max).',
                    'fileExtension': 'The image format is not allowed ({{ value }} only).'
                }
            });

            drFeatured.on('dropify.afterClear', function() {
                const hiddenInput = document.getElementById('existingFeaturedImage');
                if (hiddenInput) hiddenInput.value = '';
                const altInput = document.getElementById('imageAlt');
                if (altInput) altInput.value = '';
                showToast('✓ Featured image cleared');
            });
        }

        // Initialize Thumbnail Dropify
        const $thumbInput = $('#thumbImageInput');
        if ($thumbInput.length) {
            const drThumb = $thumbInput.dropify({
                messages: {
                    'default': 'Drag and drop thumbnail (16:9) or click',
                    'replace': 'Drag and drop or click to replace thumbnail',
                    'remove':  'Remove',
                    'error':   'Oops, something went wrong with the file.'
                },
                error: {
                    'fileSize': 'The file size is too big ({{ value }} max).',
                    'fileExtension': 'The image format is not allowed ({{ value }} only).'
                }
            });

            drThumb.on('dropify.afterClear', function() {
                const hiddenInput = document.getElementById('existingThumbnailImage');
                if (hiddenInput) hiddenInput.value = '';
                showToast('✓ Thumbnail cleared');
            });
        }

        // Initialize Branding Logo Dropify
        const $logoInput = $('#siteLogoFile');
        if ($logoInput.length) {
            const drLogo = $logoInput.dropify({
                messages: {
                    'default': 'Drag & drop website logo here or click to browse',
                    'replace': 'Drag & drop or click to replace logo',
                    'remove':  'Remove Logo',
                    'error':   'Oops, something went wrong with the file.'
                },
                error: {
                    'fileSize': 'The file size is too big ({{ value }} max).',
                    'fileExtension': 'The image format is not allowed ({{ value }} only).'
                }
            });

            drLogo.on('dropify.afterClear', function() {
                const removeInput = document.getElementById('removeLogoInput');
                if (removeInput) removeInput.value = '1';
                const textInput = document.getElementById('siteLogoText');
                if (textInput) textInput.value = '';
                if (typeof showToast === 'function') showToast('✓ Website logo marked for removal');
            });
        }

        // Initialize Branding Favicon Dropify
        const $favInput = $('#siteFaviconFile');
        if ($favInput.length) {
            const drFav = $favInput.dropify({
                messages: {
                    'default': 'Drag & drop browser favicon or click to browse',
                    'replace': 'Drag & drop or click to replace favicon',
                    'remove':  'Remove Favicon',
                    'error':   'Oops, something went wrong with the file.'
                },
                error: {
                    'fileSize': 'The file size is too big ({{ value }} max).',
                    'fileExtension': 'The image format is not allowed ({{ value }} only).'
                }
            });

            drFav.on('dropify.afterClear', function() {
                const removeInput = document.getElementById('removeFaviconInput');
                if (removeInput) removeInput.value = '1';
                const textInput = document.getElementById('siteFaviconText');
                if (textInput) textInput.value = '';
                if (typeof showToast === 'function') showToast('✓ Favicon marked for removal');
            });
        }

        // Initialize Media Library Dropify
        const $mediaInput = $('#mediaFileInput');
        if ($mediaInput.length && !$mediaInput.data('dropify')) {
            $mediaInput.dropify({
                messages: {
                    'default': 'Drag & drop media asset here or click to browse',
                    'replace': 'Drag & drop or click to replace media',
                    'remove':  'Remove',
                    'error':   'Oops, something went wrong with the file.'
                },
                error: {
                    'fileSize': 'The file size is too big ({{ value }} max).',
                    'fileExtension': 'The image format is not allowed ({{ value }} only).'
                }
            });
        }

        // Generic auto-initializer for any other element with .dropify class
        $('.dropify').each(function() {
            if (!$(this).data('dropify')) {
                $(this).dropify({
                    messages: {
                        'default': 'Drag and drop a file here or click to browse',
                        'replace': 'Drag and drop or click to replace',
                        'remove':  'Remove',
                        'error':   'Oops, something went wrong with the file.'
                    },
                    error: {
                        'fileSize': 'The file size is too big ({{ value }} max).',
                        'fileExtension': 'The file format is not allowed ({{ value }} only).'
                    }
                });
            }
        });
    }
});

// Flash Alert Auto-Dismiss (6s) & Smooth Exit
document.addEventListener('DOMContentLoaded', () => {
    // Delegated click dismissal
    document.addEventListener('click', (e) => {
        const closeBtn = e.target.closest('.alert-close');
        if (closeBtn) {
            e.preventDefault();
            const alertCard = closeBtn.closest('.alert');
            if (alertCard) {
                alertCard.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                alertCard.style.opacity = '0';
                alertCard.style.transform = 'translateY(-6px)';
                setTimeout(() => alertCard.remove(), 260);
            }
        }
    });

    // Auto-dismiss alerts after 6 seconds with hover-pause
    const alerts = document.querySelectorAll('.flash-messages-container .alert');
    alerts.forEach((alertCard) => {
        let dismissTimer = null;

        const startTimer = () => {
            dismissTimer = setTimeout(() => {
                alertCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                alertCard.style.opacity = '0';
                alertCard.style.transform = 'translateY(-6px)';
                setTimeout(() => alertCard.remove(), 320);
            }, 6000);
        };

        const stopTimer = () => {
            if (dismissTimer) clearTimeout(dismissTimer);
        };

        alertCard.addEventListener('mouseenter', stopTimer);
        alertCard.addEventListener('mouseleave', startTimer);
        startTimer();
    });
});



