<?php
/**
 * Admin Panel - Categories Management
 * Pixel-perfect match to PineCast TV layout:
 * - Top header with "+ New Category" indigo button
 * - Clean modern full-width table: NAME (image + title + description), SLUG (/category/{slug}), STATUS (Active badge), POSTS (count), ACTIONS (View, Edit, Delete)
 * - Retains CareerNexaa branding and all existing categories
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires categories.view
require_permission('categories.view');

$db = getDBConnection();

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Invalid security token. Please refresh and try again.');
        header('Location: ' . ADMIN_URL . '/categories.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // ================= 1. ADD NEW CATEGORY =================
    if ($action === 'add') {
        require_permission('categories.create');

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if (empty($name)) {
            set_flash('error', 'Category name is required.');
        } else {
            if (empty($slug)) {
                $slug = slugify($name);
            } else {
                $slug = slugify($slug);
            }

            // Check duplicate slug
            $checkStmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
            $checkStmt->execute([$slug]);
            if ($checkStmt->fetch()) {
                set_flash('error', "A category with the slug '{$slug}' already exists.");
            } else {
                $imagePath = 'assets/images/category-placeholder.svg';

                // Handle Image Upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload = upload_category_image($_FILES['image']);
                    if (!$upload['success']) {
                        set_flash('error', 'Image Upload Failed: ' . $upload['error']);
                        header('Location: ' . ADMIN_URL . '/categories.php');
                        exit;
                    }
                    if (!empty($upload['path'])) {
                        $imagePath = $upload['path'];
                    }
                }

                $insertStmt = $db->prepare("INSERT INTO categories (name, slug, description, status, image, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $insertStmt->execute([$name, $slug, $description, $status, $imagePath]);
                $newCatId = (int)$db->lastInsertId();

                log_activity('create_category', 'categories', "Created category '{$name}' (ID: {$newCatId})", $newCatId);

                set_flash('success', "Category '{$name}' created successfully.");
                header('Location: ' . ADMIN_URL . '/categories.php');
                exit;
            }
        }
    }

    // ================= 2. EDIT CATEGORY =================
    elseif ($action === 'edit') {
        require_permission('categories.edit');

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $status = in_array($_POST['status'] ?? 'active', ['active', 'inactive']) ? $_POST['status'] : 'active';

        if ($id <= 0 || empty($name)) {
            set_flash('error', 'Valid category ID and name are required.');
        } else {
            if (empty($slug)) {
                $slug = slugify($name);
            } else {
                $slug = slugify($slug);
            }

            // Check duplicate slug for other categories
            $checkStmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $checkStmt->execute([$slug, $id]);
            if ($checkStmt->fetch()) {
                set_flash('error', "Another category already uses the slug '{$slug}'.");
            } else {
                // Fetch current category to preserve existing image if no new upload
                $currStmt = $db->prepare("SELECT image FROM categories WHERE id = ?");
                $currStmt->execute([$id]);
                $currCat = $currStmt->fetch();
                $imagePath = $currCat['image'] ?? 'assets/images/category-placeholder.svg';

                // Handle Image Upload if provided
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upload = upload_category_image($_FILES['image']);
                    if (!$upload['success']) {
                        set_flash('error', 'Image Upload Failed: ' . $upload['error']);
                        header('Location: ' . ADMIN_URL . '/categories.php');
                        exit;
                    }
                    if (!empty($upload['path'])) {
                        $imagePath = $upload['path'];
                    }
                }

                $updateStmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, status = ?, image = ? WHERE id = ?");
                $updateStmt->execute([$name, $slug, $description, $status, $imagePath, $id]);

                log_activity('edit_category', 'categories', "Updated category '{$name}' (ID: {$id})", $id);

                set_flash('success', "Category '{$name}' updated successfully.");
                header('Location: ' . ADMIN_URL . '/categories.php');
                exit;
            }
        }
    }

    // ================= 3. DELETE CATEGORY =================
    elseif ($action === 'delete') {
        require_permission('categories.delete');

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Check article count
            $artCountStmt = $db->prepare("SELECT COUNT(*) FROM articles WHERE category_id = ?");
            $artCountStmt->execute([$id]);
            $artCount = (int)$artCountStmt->fetchColumn();

            if ($artCount > 0) {
                set_flash('warning', "Cannot delete category: {$artCount} article(s) are currently attached to it. Please reassign or delete the articles first.");
            } else {
                // Fetch name for activity log
                $nameStmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
                $nameStmt->execute([$id]);
                $catName = $nameStmt->fetchColumn() ?: 'Unknown';

                $delStmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $delStmt->execute([$id]);

                log_activity('delete_category', 'categories', "Deleted category '{$catName}' (ID: {$id})", null);

                set_flash('success', "Category '{$catName}' deleted successfully.");
            }
        }
        header('Location: ' . ADMIN_URL . '/categories.php');
        exit;
    }
}

// Fetch all categories with dynamic article counts
$categories = get_all_categories(true);

$adminPageTitle = "Categories";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="padding: 1.5rem 2rem; max-width: 1400px; margin: 0 auto;">

    <!-- Categories Table Container -->
    <div class="pinecast-categories-container">
        <div style="overflow-x: auto;">
            <table class="pinecast-categories-table">
                <thead>
                    <tr>
                        <th style="width: 44%;">NAME</th>
                        <th style="width: 20%;">SLUG</th>
                        <th style="width: 12%;">STATUS</th>
                        <th style="width: 8%; text-align:center;">POSTS</th>
                        <th style="width: 16%; text-align:right; padding-right:1.75rem;">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): 
                            $imageUrl = get_category_image_url($cat);
                            $catStatus = strtolower($cat['status'] ?? 'active');
                            $cleanSlug = '/category/' . $cat['slug'];
                            $jsonData = [
                                'id' => (int)$cat['id'],
                                'name' => (string)$cat['name'],
                                'slug' => (string)$cat['slug'],
                                'description' => (string)($cat['description'] ?? ''),
                                'status' => (string)($cat['status'] ?? 'active'),
                                'image_url' => $imageUrl
                            ];
                        ?>
                            <tr>
                                <!-- NAME Column: Thumbnail + Title + Description -->
                                <td>
                                    <div class="pcat-name-cell">
                                        <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" class="pcat-thumb" loading="lazy" onerror="this.src='<?= BASE_URL ?>/assets/images/category-placeholder.svg'">
                                        <div class="pcat-info">
                                            <div class="pcat-title"><?= htmlspecialchars($cat['name']) ?></div>
                                            <div class="pcat-desc"><?= htmlspecialchars($cat['description'] ?: 'Curated articles and career insights.') ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- SLUG Column: /category/{slug} in muted monospace -->
                                <td>
                                    <span class="pcat-slug"><?= htmlspecialchars($cleanSlug) ?></span>
                                </td>

                                <!-- STATUS Column: Active Green Pill -->
                                <td>
                                    <span class="pcat-status-badge <?= $catStatus === 'active' ? 'pcat-status-active' : 'pcat-status-inactive' ?>">
                                        <?= ucfirst($catStatus) ?>
                                    </span>
                                </td>

                                <!-- POSTS Column: Total published articles -->
                                <td style="text-align: center;">
                                    <span class="pcat-posts-count"><?= (int)$cat['total_articles'] ?></span>
                                </td>

                                <!-- ACTIONS Column: View, Edit, Delete -->
                                <td style="text-align: right; padding-right:1.75rem;">
                                    <div class="pcat-actions" style="justify-content: flex-end;">
                                        <!-- View Button -->
                                        <a href="<?= BASE_URL ?>/category/<?= urlencode($cat['slug']) ?>" target="_blank" class="btn-pcat-action" title="View category in new tab">
                                            View
                                        </a>

                                        <!-- Edit Button -->
                                        <?php if (is_super_admin() || has_permission('categories.edit')): ?>
                                        <button type="button" class="btn-pcat-action" onclick='openEditCategoryModal(<?= json_encode($jsonData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit category details">
                                            Edit
                                        </button>
                                        <?php endif; ?>

                                        <!-- Delete Button -->
                                        <?php if (is_super_admin() || has_permission('categories.delete')): ?>
                                        <form action="<?= ADMIN_URL ?>/categories.php" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Are you sure you want to delete category \'<?= addslashes(htmlspecialchars($cat['name'])) ?>\'?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                            <button type="submit" class="btn-pcat-action btn-pcat-delete" title="Delete category">
                                                Delete
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem 1.5rem; color: #64748b;">
                                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem; color: #1e293b;">No categories found</div>
                                <p style="font-size: 0.875rem; margin-bottom: 1.25rem;">Click the "+ New Category" button above to add your first category.</p>
                                <button type="button" class="btn-topbar-indigo" onclick="openNewCategoryModal()">+ New Category</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ================= MODAL: ADD NEW CATEGORY ================= -->
<div id="newCategoryModal" class="pcat-modal-backdrop" onclick="handleBackdropClick(event, 'newCategoryModal')">
    <div class="pcat-modal-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.85rem;">
            <h3 style="font-size:1.2rem; font-weight:800; color:#0f172a; margin:0;">
                Add New Category
            </h3>
            <button type="button" onclick="closeNewCategoryModal()" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1;" aria-label="Close modal">
                &times;
            </button>
        </div>

        <form action="<?= ADMIN_URL ?>/categories.php" method="POST" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="add">

            <!-- Category Name -->
            <div style="margin-bottom:1.15rem;">
                <label for="newCatName" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Category Name *
                </label>
                <input 
                    type="text" 
                    id="newCatName" 
                    name="name" 
                    class="admin-form-control" 
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" 
                    placeholder="e.g. Government Jobs" 
                    required 
                    oninput="autoGenerateSlug(this.value, 'newCatSlug')"
                >
            </div>

            <!-- Slug -->
            <div style="margin-bottom:1.15rem;">
                <label for="newCatSlug" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    URL Slug
                </label>
                <div style="position:relative; display:flex; align-items:center;">
                    <span style="position:absolute; left:0.85rem; font-size:0.85rem; color:#94a3b8; font-family:monospace;">/category/</span>
                    <input 
                        type="text" 
                        id="newCatSlug" 
                        name="slug" 
                        class="admin-form-control" 
                        style="width:100%; padding:0.65rem 0.85rem 0.65rem 5.8rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; font-family:monospace;" 
                        placeholder="government-jobs"
                    >
                </div>
                <small style="color:#64748b; font-size:0.775rem; display:block; margin-top:0.25rem;">
                    Leave blank to automatically generate from category name.
                </small>
            </div>

            <!-- Description -->
            <div style="margin-bottom:1.15rem;">
                <label for="newCatDesc" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Description
                </label>
                <textarea 
                    id="newCatDesc" 
                    name="description" 
                    rows="3" 
                    class="admin-form-control" 
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; resize:vertical;" 
                    placeholder="Brief overview of articles and topics in this category..."
                ></textarea>
            </div>

            <!-- Status -->
            <div style="margin-bottom:1.15rem;">
                <label for="newCatStatus" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Status
                </label>
                <select 
                    id="newCatStatus" 
                    name="status" 
                    class="admin-form-control" 
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; background:#ffffff;"
                >
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Category Thumbnail Image -->
            <div style="margin-bottom:1.5rem;">
                <label for="newCatImage" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Thumbnail Image
                </label>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <img id="newCatImgPreview" src="<?= BASE_URL ?>/assets/images/category-placeholder.svg" alt="Preview" style="width:48px; height:48px; border-radius:8px; object-fit:cover; border:1px solid #e2e8f0; background:#f8fafc;">
                    <input 
                        type="file" 
                        id="newCatImage" 
                        name="image" 
                        accept="image/png, image/jpeg, image/webp" 
                        style="flex:1; font-size:0.85rem;"
                        onchange="previewCategoryFile(this, 'newCatImgPreview')"
                    >
                </div>
                <small style="color:#64748b; font-size:0.775rem; display:block; margin-top:0.35rem;">
                    Recommended: Square image (1:1), JPG/PNG/WebP, up to 4MB.
                </small>
            </div>

            <!-- Modal Action Buttons -->
            <div style="display:flex; justify-content:flex-end; gap:0.65rem; border-top:1px solid #f1f5f9; padding-top:1.15rem;">
                <button type="button" onclick="closeNewCategoryModal()" class="btn-pcat-action" style="padding:0.6rem 1.25rem;">
                    Cancel
                </button>
                <button type="submit" class="btn-topbar-indigo" style="padding:0.6rem 1.4rem;">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= MODAL: EDIT CATEGORY ================= -->
<div id="editCategoryModal" class="pcat-modal-backdrop" onclick="handleBackdropClick(event, 'editCategoryModal')">
    <div class="pcat-modal-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; border-bottom:1px solid #f1f5f9; padding-bottom:0.85rem;">
            <h3 style="font-size:1.2rem; font-weight:800; color:#0f172a; margin:0;">
                Edit Category
            </h3>
            <button type="button" onclick="closeEditCategoryModal()" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1;" aria-label="Close modal">
                &times;
            </button>
        </div>

        <form action="<?= ADMIN_URL ?>/categories.php" method="POST" enctype="multipart/form-data">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="editCatId" name="id" value="">

            <!-- Category Name -->
            <div style="margin-bottom:1.15rem;">
                <label for="editCatName" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Category Name *
                </label>
                <input 
                    type="text" 
                    id="editCatName" 
                    name="name" 
                    class="admin-form-control" 
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" 
                    required
                >
            </div>

            <!-- Slug -->
            <div style="margin-bottom:1.15rem;">
                <label for="editCatSlug" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    URL Slug
                </label>
                <div style="position:relative; display:flex; align-items:center;">
                    <span style="position:absolute; left:0.85rem; font-size:0.85rem; color:#94a3b8; font-family:monospace;">/category/</span>
                    <input 
                        type="text" 
                        id="editCatSlug" 
                        name="slug" 
                        class="admin-form-control" 
                        style="width:100%; padding:0.65rem 0.85rem 0.65rem 5.8rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; font-family:monospace;"
                    >
                </div>
            </div>

            <!-- Description -->
            <div style="margin-bottom:1.15rem;">
                <label for="editCatDesc" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Description
                </label>
                <textarea 
                    id="editCatDesc" 
                    name="description" 
                    rows="3" 
                    class="admin-form-control" 
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; resize:vertical;"
                ></textarea>
            </div>

            <!-- Status -->
            <div style="margin-bottom:1.15rem;">
                <label for="editCatStatus" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Status
                </label>
                <select 
                    id="editCatStatus" 
                    name="status" 
                    class="admin-form-control" 
                    style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; background:#ffffff;"
                >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <!-- Category Thumbnail Image -->
            <div style="margin-bottom:1.5rem;">
                <label for="editCatImage" style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.4rem;">
                    Thumbnail Image
                </label>
                <div style="display:flex; align-items:center; gap:1rem;">
                    <img id="editCatImgPreview" src="" alt="Preview" class="pcat-thumb">
                    <div style="flex:1;">
                        <input 
                            type="file" 
                            id="editCatImage" 
                            name="image" 
                            accept="image/png, image/jpeg, image/webp" 
                            style="width:100%; font-size:0.85rem;"
                            onchange="previewCategoryFile(this, 'editCatImgPreview')"
                        >
                        <small style="color:#64748b; font-size:0.775rem; display:block; margin-top:0.25rem;">
                            Leave empty to keep existing category image.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons -->
            <div style="display:flex; justify-content:flex-end; gap:0.65rem; border-top:1px solid #f1f5f9; padding-top:1.15rem;">
                <button type="button" onclick="closeEditCategoryModal()" class="btn-pcat-action" style="padding:0.6rem 1.25rem;">
                    Cancel
                </button>
                <button type="submit" class="btn-topbar-indigo" style="padding:0.6rem 1.4rem;">
                    Update Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function autoGenerateSlug(text, targetId) {
    var target = document.getElementById(targetId);
    if (!target) return;
    var slug = text.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-');
    target.value = slug;
}

function previewCategoryFile(input, previewId) {
    var preview = document.getElementById(previewId);
    if (input.files && input.files[0] && preview) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openNewCategoryModal() {
    var modal = document.getElementById('newCategoryModal');
    if (modal) {
        modal.classList.add('active');
        var nameInput = document.getElementById('newCatName');
        if (nameInput) setTimeout(function() { nameInput.focus(); }, 100);
    }
}

function closeNewCategoryModal() {
    var modal = document.getElementById('newCategoryModal');
    if (modal) modal.classList.remove('active');
}

function openEditCategoryModal(cat) {
    if (!cat) return;
    document.getElementById('editCatId').value = cat.id || '';
    document.getElementById('editCatName').value = cat.name || '';
    document.getElementById('editCatSlug').value = cat.slug || '';
    document.getElementById('editCatDesc').value = cat.description || '';
    document.getElementById('editCatStatus').value = (cat.status === 'inactive') ? 'inactive' : 'active';
    
    var preview = document.getElementById('editCatImgPreview');
    if (preview) {
        preview.src = cat.image_url || '<?= BASE_URL ?>/assets/images/category-placeholder.svg';
    }

    var modal = document.getElementById('editCategoryModal');
    if (modal) {
        modal.classList.add('active');
        var nameInput = document.getElementById('editCatName');
        if (nameInput) setTimeout(function() { nameInput.focus(); }, 100);
    }
}

function closeEditCategoryModal() {
    var modal = document.getElementById('editCategoryModal');
    if (modal) modal.classList.remove('active');
}

function handleBackdropClick(event, modalId) {
    if (event.target && event.target.id === modalId) {
        var modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    }
}

// Close modals on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNewCategoryModal();
        closeEditCategoryModal();
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
