<?php
/**
 * Image Alt Text & Alignment Modal Component for Summernote WYSIWYG Editor
 * Allows writers to write SEO Alt text, center images, and adjust sizes easily.
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
<!-- Image Alt Text & Sizing Modal -->
<div class="media-modal-backdrop" id="imageAltModal" style="display:none; z-index:100050;" onclick="closeImageAltModal(event)" role="dialog" aria-modal="true" aria-labelledby="imgModalTitle">
    <div class="media-modal-dialog" style="max-width:520px; border-radius:14px; overflow:hidden;" onclick="event.stopPropagation()">
        <!-- Modal Top Bar -->
        <div class="media-modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:1.1rem 1.35rem; display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:0.55rem;">
                <div style="width:32px; height:32px; border-radius:8px; background:#ecfdf5; color:#059669; display:flex; align-items:center; justify-content:center;">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </div>
                <div>
                    <h3 id="imgModalTitle" style="font-size:1.05rem; font-weight:800; color:#0f172a; margin:0;">Image Alt Text & Position</h3>
                    <p style="font-size:0.75rem; color:#64748b; margin:2px 0 0 0;">Add SEO description and center or resize image</p>
                </div>
            </div>
            <button type="button" class="media-modal-close-btn" onclick="closeImageAltModal()" aria-label="Close" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <!-- Modal Body -->
        <div style="padding:1.35rem;">
            <!-- Thumbnail preview card -->
            <div style="text-align:center; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:10px; padding:0.85rem; margin-bottom:1.25rem;">
                <img id="imgModalPreview" src="" alt="" style="max-height:120px; max-width:100%; border-radius:6px; object-fit:contain; display:inline-block; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            </div>

            <!-- 1. Alt Text Input (SEO Image Description) -->
            <div style="margin-bottom:1.35rem;">
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

            <!-- 2. Alignment Selection (Center, Left, Right, None) -->
            <div style="margin-bottom:1.35rem;">
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

            <!-- 3. Size / Scale Option (Make picture smaller / center) -->
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; color:#1e293b; margin-bottom:0.4rem;">
                    Size (Make Picture Smaller / Full)
                </label>
                <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:0.5rem;" id="imgModalSizeGroup">
                    <button type="button" class="btn-img-choice" data-size="100%" onclick="setModalImgSize('100%')">
                        <span style="font-weight:700; display:block;">100%</span>
                        <span style="font-size:0.7rem; color:#64748b;">Full Width</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-size="50%" onclick="setModalImgSize('50%')">
                        <span style="font-weight:700; display:block;">50%</span>
                        <span style="font-size:0.7rem; color:#64748b;">Medium</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-size="25%" onclick="setModalImgSize('25%')">
                        <span style="font-weight:700; display:block;">25%</span>
                        <span style="font-size:0.7rem; color:#64748b;">Small</span>
                    </button>
                    <button type="button" class="btn-img-choice" data-size="auto" onclick="setModalImgSize('auto')">
                        <span style="font-weight:700; display:block;">Auto</span>
                        <span style="font-size:0.7rem; color:#64748b;">Original</span>
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
