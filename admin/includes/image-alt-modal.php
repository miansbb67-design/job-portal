<?php
/**
 * Image Link, Alt Text & Alignment Modal Component for Summernote WYSIWYG Editor
 * Allows writers to add clickable links to images, write SEO Alt text, center images, and adjust sizes easily.
 */
?>
<style>
.btn-img-choice {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.65rem 0.5rem;
    font-size: 0.85rem;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    outline: none;
}
.btn-img-choice:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}
.btn-img-choice.active {
    background: #ecfdf5 !important;
    border-color: #059669 !important;
    color: #059669 !important;
    box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.25);
    font-weight: 700;
}
.btn-img-choice.active span:last-child {
    color: #059669 !important;
}
.note-popover .popover-content .btn-group .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
}
</style>
<!-- Image Link, Alt Text & Sizing Modal -->
<div class="media-modal-backdrop" id="imageAltModal" style="display:none; z-index:100050;" onclick="closeImageAltModal(event)" role="dialog" aria-modal="true" aria-labelledby="imgModalTitle">
    <div class="media-modal-dialog" style="max-width:560px; border-radius:14px; overflow:hidden;" onclick="event.stopPropagation()">
        <!-- Modal Top Bar -->
        <div class="media-modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:1.1rem 1.35rem; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:0.55rem;">
                <div style="width:34px; height:34px; border-radius:8px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                    </svg>
                </div>
                <div>
                    <h3 id="imgModalTitle" style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0;">Image Link, Alt Text & Position</h3>
                    <p style="font-size:0.75rem; color:#64748b; margin:2px 0 0 0;">Add clickable link, SEO description, and resize or center picture</p>
                </div>
            </div>
            <button type="button" class="media-modal-close-btn" onclick="closeImageAltModal()" aria-label="Close" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <!-- Modal Body (Scrollable for compact screens) -->
        <div style="padding:1.35rem; max-height:calc(85vh - 130px); overflow-y:auto;">
            <!-- Thumbnail preview card with current link status -->
            <div style="text-align:center; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:0.85rem; margin-bottom:1.25rem; position:relative;">
                <img id="imgModalPreview" src="" alt="" style="max-height:110px; max-width:100%; border-radius:6px; object-fit:contain; display:inline-block; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                
                <!-- Live Link Badge if linked -->
                <div id="imgModalLinkStatus" style="display:none; margin-top:0.6rem; align-items:center; justify-content:center; gap:0.4rem; font-size:0.75rem; background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:0.3rem 0.65rem; border-radius:6px;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    <span>Currently linked to:</span>
                    <strong id="imgModalLinkedUrlDisplay" style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block;"></strong>
                    <button type="button" onclick="clearModalImgLink()" style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; font-size:0.7rem; font-weight:700; padding:0.15rem 0.45rem; border-radius:4px; cursor:pointer; margin-left:4px;">Unlink</button>
                </div>

                <!-- 1-Click Set as Article Thumbnail Button -->
                <div style="margin-top:0.65rem; display:flex; justify-content:center;">
                    <button type="button" onclick="setModalImgAsArticleThumbnail()" style="background:#fffbeb; border:1.5px solid #fde68a; color:#b45309; font-size:0.8rem; font-weight:700; padding:0.4rem 0.95rem; border-radius:6px; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 1px 2px rgba(0,0,0,0.05); transition:all 0.15s ease;" onmouseover="this.style.background='#fef3c7'" onmouseout="this.style.background='#fffbeb'">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="#f59e0b" stroke="#d97706" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                        ⭐ Set as Article Thumbnail (Featured Image)
                    </button>
                </div>
            </div>

            <!-- 1. Image Link Input (Clickable Hyperlink Box) -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:1rem; margin-bottom:1.25rem;">
                <label for="imgModalLinkInput" style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; font-weight:800; color:#166534; margin-bottom:0.4rem;">
                    <span style="display:inline-flex; align-items:center; gap:5px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                        Image Link (URL / Web Link)
                    </span>
                    <span style="font-size:0.75rem; color:#047857; font-weight:600;">Clickable in article</span>
                </label>
                
                <div style="position:relative; display:flex; align-items:center;">
                    <span style="position:absolute; left:0.75rem; color:#64748b; font-size:0.85rem; pointer-events:none; display:flex; align-items:center;">
                        🔗
                    </span>
                    <input 
                        type="text" 
                        id="imgModalLinkInput" 
                        class="form-input" 
                        placeholder="e.g. https://example.com/apply-now or /category/jobs"
                        style="width:100%; padding:0.65rem 0.85rem 0.65rem 2.25rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; background:#ffffff;"
                        oninput="updateModalLinkBadge()"
                        onkeydown="if(event.key==='Enter'){event.preventDefault(); saveImageAltModal();}"
                    >
                </div>
                
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-top:0.55rem; font-size:0.8rem;">
                    <label style="display:inline-flex; align-items:center; gap:5px; color:#334155; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="imgModalLinkTarget" checked style="accent-color:#059669; width:15px; height:15px;">
                        <span>Open link in new tab (target="_blank")</span>
                    </label>
                    <label style="display:inline-flex; align-items:center; gap:5px; color:#64748b; font-size:0.75rem; cursor:pointer;" title="Add rel='nofollow' for affiliate or sponsored partner links">
                        <input type="checkbox" id="imgModalLinkNofollow" style="accent-color:#059669; width:14px; height:14px;">
                        <span>rel="nofollow" (Affiliate / External)</span>
                    </label>
                </div>
                <span style="display:block; font-size:0.75rem; color:#475569; margin-top:0.4rem; line-height:1.4;">
                    Jab reader website par is picture par click karega to ye URL open hoga. Khali chhorne par picture par koi link nahi lagega.
                </span>
            </div>

            <!-- 2. Alt Text Input (SEO Image Description) -->
            <div style="margin-bottom:1.25rem;">
                <label for="imgModalAltInput" style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    <span>Alt Text (SEO Description) <span style="color:#e11d48;">*</span></span>
                    <span style="font-size:0.75rem; color:#059669; font-weight:600;">Crucial for Google Ranking</span>
                </label>
                <input 
                    type="text" 
                    id="imgModalAltInput" 
                    class="form-input" 
                    placeholder="e.g. Remote professional working on laptop at modern home workspace"
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;"
                    onkeydown="if(event.key==='Enter'){event.preventDefault(); saveImageAltModal();}"
                >
                <span style="display:block; font-size:0.75rem; color:#64748b; margin-top:0.35rem; line-height:1.4;">
                    Describe what is in the picture. This improves Google Image Search visibility and web accessibility.
                </span>
            </div>

            <!-- 3. Alignment Selection (Center, Left, Right, None) -->
            <div style="margin-bottom:1.25rem;">
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    Image Alignment / Center Position
                </label>
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem;" id="imgModalAlignGroup">
                    <button type="button" class="btn-img-choice" data-align="center" onclick="setModalImgAlign('center')">
                        <span style="font-size:1.1rem; line-height:1; display:block; margin-bottom:2px;">☲</span>
                        <span>Center</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-align="left" onclick="setModalImgAlign('left')">
                        <span style="font-size:1.1rem; line-height:1; display:block; margin-bottom:2px;">☷</span>
                        <span>Left</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-align="right" onclick="setModalImgAlign('right')">
                        <span style="font-size:1.1rem; line-height:1; display:block; margin-bottom:2px;">☵</span>
                        <span>Right</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-align="none" onclick="setModalImgAlign('none')">
                        <span style="font-size:1.1rem; line-height:1; display:block; margin-bottom:2px;">☰</span>
                        <span>None</span>
                    </button>
                </div>
            </div>

            <!-- 4. Size / Scale Option (Custom 100% to 0% Number & Slider) -->
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.45rem;">
                    <label style="font-size:0.85rem; font-weight:700; color:#1e293b; margin:0;">
                        Picture Size (100 to 0) / Width %
                    </label>
                    <span id="imgModalSizeValueBadge" style="background:#ecfdf5; color:#059669; font-weight:800; padding:2px 8px; border-radius:6px; font-size:0.85rem; border:1px solid #a7f3d0;">
                        100%
                    </span>
                </div>

                <!-- Custom Size Box: Number input (0-100) + Interactive Range Slider -->
                <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:10px; padding:0.85rem 1rem; margin-bottom:0.75rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; margin-bottom:0.65rem;">
                        <span style="font-size:0.8rem; font-weight:700; color:#475569;">
                            Size Number (100 to 0):
                        </span>
                        <div style="display:inline-flex; align-items:center; gap:5px;">
                            <input 
                                type="number" 
                                id="imgModalSizeNumberInput" 
                                min="0" 
                                max="100" 
                                step="1"
                                value="100"
                                style="width:72px; padding:0.35rem 0.5rem; text-align:center; font-weight:800; font-size:1rem; color:#0f172a; border:2px solid #059669; border-radius:6px; outline:none; background:#ffffff; box-shadow:0 1px 2px rgba(0,0,0,0.05);"
                                oninput="handleModalImgNumberChange(this.value)"
                                onkeydown="if(event.key==='Enter'){event.preventDefault(); saveImageAltModal();}"
                            >
                            <span style="font-weight:800; color:#059669; font-size:1rem;">%</span>
                        </div>
                    </div>

                    <!-- Range Slider from 0 to 100 -->
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <span style="font-size:0.75rem; font-weight:700; color:#94a3b8; width:26px;">0%</span>
                        <input 
                            type="range" 
                            id="imgModalSizeRangeSlider" 
                            min="0" 
                            max="100" 
                            step="1" 
                            value="100"
                            style="flex:1; accent-color:#059669; cursor:pointer; height:6px;"
                            oninput="handleModalImgRangeChange(this.value)"
                        >
                        <span style="font-size:0.75rem; font-weight:700; color:#059669; width:38px; text-align:right;">100%</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.7rem; color:#94a3b8; margin-top:3px; padding:0 34px;">
                        <span>25%</span>
                        <span>50%</span>
                        <span>75%</span>
                    </div>
                </div>

                <!-- Quick Preset Buttons -->
                <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:0.4rem;" id="imgModalSizeGroup">
                    <button type="button" class="btn-img-choice" data-size="100%" onclick="setModalImgSize('100%')">
                        <span style="font-weight:700; display:block;">100%</span>
                        <span style="font-size:0.65rem; color:#64748b;">Full</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-size="75%" onclick="setModalImgSize('75%')">
                        <span style="font-weight:700; display:block;">75%</span>
                        <span style="font-size:0.65rem; color:#64748b;">Large</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-size="50%" onclick="setModalImgSize('50%')">
                        <span style="font-weight:700; display:block;">50%</span>
                        <span style="font-size:0.65rem; color:#64748b;">Medium</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-size="25%" onclick="setModalImgSize('25%')">
                        <span style="font-weight:700; display:block;">25%</span>
                        <span style="font-size:0.65rem; color:#64748b;">Small</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-size="auto" onclick="setModalImgSize('auto')">
                        <span style="font-weight:700; display:block;">Auto</span>
                        <span style="font-size:0.65rem; color:#64748b;">Reset</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="display:flex; justify-content:space-between; align-items:center; padding:0.85rem 1.35rem; background:#f8fafc; border-top:1px solid #e2e8f0;">
            <button type="button" class="admin-btn" style="background:#fff; border:1px solid #cbd5e1; color:#475569; padding:0.5rem 1rem; border-radius:8px; font-weight:600;" onclick="closeImageAltModal()">
                Cancel
            </button>
            <div style="display:flex; gap:0.5rem;">
                <button type="button" class="admin-btn" style="background:#f1f5f9; border:1px solid #cbd5e1; color:#0f172a; padding:0.5rem 0.9rem; border-radius:8px; font-weight:700;" onclick="setModalImgAlign('center'); setModalImgSize('50%'); saveImageAltModal();" title="Set to 50% width and center align">
                    Small & Center (50%)
                </button>
                <button type="button" class="admin-btn admin-btn-primary" style="background:#059669; color:#fff; border:none; padding:0.5rem 1.35rem; border-radius:8px; font-weight:700;" onclick="saveImageAltModal()">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
