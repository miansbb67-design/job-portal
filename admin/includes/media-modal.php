<?php
/**
 * Modern Media Library Modal Component
 * Matches Screenshot 2 & 3: Search, filter, upload, grid cards, and pagination.
 */
?>
<!-- Media Library Modal (Screenshots 2 & 3) -->
<div class="media-modal-backdrop" id="mediaLibraryModal" onclick="closeMediaLibraryModal(event)" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="mediaModalTitle">
    <div class="media-modal-dialog" onclick="event.stopPropagation()">
        <!-- Modal Top Bar -->
        <div class="media-modal-header">
            <div class="media-modal-header-left">
                <h3 class="media-modal-title" id="mediaModalTitle">Media Library</h3>
            </div>
            
            <div class="media-modal-header-controls">
                <!-- Search input -->
                <div class="media-search-wrap">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" id="mediaModalSearchInput" class="media-modal-search" placeholder="Search..." oninput="handleMediaModalSearch(this.value)">
                </div>

                <!-- Filter dropdown -->
                <select id="mediaModalFilterSelect" class="media-modal-select" onchange="handleMediaModalFilter(this.value)">
                    <option value="all">Images</option>
                    <option value="recent">Recently Uploaded</option>
                </select>

                <!-- Upload button (Screenshot 3 triggers computer file picker) -->
                <button type="button" class="btn-media-modal-upload" id="btnMediaModalUpload" onclick="triggerMediaComputerUpload()" title="Upload image from computer">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    <span id="mediaUploadBtnLabel">Upload</span>
                </button>
                <input type="file" id="mediaModalComputerFileInput" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" style="display:none;" onchange="uploadMediaFileDirectly(this)">

                <!-- Close button -->
                <button type="button" class="media-modal-close-btn" onclick="closeMediaLibraryModal()" aria-label="Close media library">&times;</button>
            </div>
        </div>

        <!-- Modal Body (Grid of image thumbnails) -->
        <div class="media-modal-body" id="mediaModalBody">
            <div class="media-grid" id="mediaLibraryGrid">
                <!-- Dynamically rendered via JS -->
            </div>
            <div class="media-empty-state" id="mediaModalEmpty" style="display:none;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                <p>No images found matching your search.</p>
            </div>
        </div>

        <!-- Modal Footer with Pagination -->
        <div class="media-modal-footer">
            <button type="button" class="media-pagi-btn" id="mediaPagiPrev" onclick="mediaModalChangePage(-1)">
                ← Prev
            </button>
            <span class="media-pagi-info" id="mediaPagiInfo">Page 1 of 1</span>
            <button type="button" class="media-pagi-btn" id="mediaPagiNext" onclick="mediaModalChangePage(1)">
                Next →
            </button>
        </div>
    </div>
</div>
