<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires categories.view
require_permission('categories.view');

$db = getDBConnection();
$editCategory = null;

// Handle Form Submissions (Add / Edit / Delete)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Invalid security token. Please refresh and try again.');
        header('Location: ' . ADMIN_URL . '/categories.php');
        exit;
    }

    $action = $_POST['action'] ?? 'add';

    if ($action === 'add') {
        require_permission('categories.create');

        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');

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
                $insertStmt = $db->prepare("INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
                $insertStmt->execute([$name, $slug, $description]);
                $newCatId = (int)$db->lastInsertId();

                log_activity('create_category', 'categories', "Created category '{$name}' (ID: {$newCatId})", $newCatId);

                set_flash('success', "Category '{$name}' created successfully.");
                header('Location: ' . ADMIN_URL . '/categories.php');
                exit;
            }
        }
    } elseif ($action === 'edit') {
        require_permission('categories.edit');

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');

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
                $updateStmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                $updateStmt->execute([$name, $slug, $description, $id]);

                log_activity('edit_category', 'categories', "Updated category '{$name}' (ID: {$id})", $id);

                set_flash('success', "Category '{$name}' updated successfully.");
                header('Location: ' . ADMIN_URL . '/categories.php');
                exit;
            }
        }
    } elseif ($action === 'delete') {
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
                $delStmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $delStmt->execute([$id]);

                log_activity('delete_category', 'categories', "Permanently deleted category (ID: {$id})", null);

                set_flash('success', 'Category deleted successfully.');
            }
        }
        header('Location: ' . ADMIN_URL . '/categories.php');
        exit;
    }
}

// Check if Editing a Category
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editStmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $editStmt->execute([$editId]);
    $editCategory = $editStmt->fetch();
}

// Fetch all categories with article counts
$categories = get_all_categories(true);

$adminPageTitle = "Manage Categories";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-categories-split-grid">
    
    <!-- Left Column: Add / Edit Form Card -->
    <div class="admin-card card-padded">
        <h2 class="admin-card-title" style="margin-bottom: 1.25rem;">
            <?= $editCategory ? 'Edit Category' : 'Add New Category' ?>
        </h2>

        <form action="<?= ADMIN_URL ?>/categories.php" method="POST">
            <?= csrf_field(); ?>
            <input type="hidden" name="action" value="<?= $editCategory ? 'edit' : 'add' ?>">
            <?php if ($editCategory): ?>
                <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="categoryName" class="form-label">Category Name *</label>
                <input type="text" id="categoryName" name="name" class="form-control" required value="<?= $editCategory ? h($editCategory['name']) : '' ?>" placeholder="e.g. Remote & Work From Home">
            </div>

            <div class="form-group">
                <label for="categorySlug" class="form-label">URL Slug</label>
                <input type="text" id="categorySlug" name="slug" class="form-control" value="<?= $editCategory ? h($editCategory['slug']) : '' ?>" placeholder="e.g. remote-work-from-home">
                <span class="form-text">Leave blank to automatically generate from name.</span>
            </div>

            <div class="form-group">
                <label for="categoryDesc" class="form-label">Description</label>
                <textarea id="categoryDesc" name="description" class="form-control" rows="3" placeholder="Brief summary of articles in this sector..."><?= $editCategory ? h($editCategory['description']) : '' ?></textarea>
            </div>

            <div style="display:flex; gap:0.5rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;">
                    <?= $editCategory ? 'Update Category' : 'Save Category' ?>
                </button>
                <?php if ($editCategory): ?>
                    <a href="<?= ADMIN_URL ?>/categories.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Right Column: Categories List Table -->
    <div class="admin-card has-table">
        <div class="admin-card-header">
            <h2 class="admin-card-title">Existing Categories (<?= count($categories) ?>)</h2>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Articles</th>
                        <th>Created</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:700; color:var(--admin-text);"><?= h($cat['name']) ?></div>
                                    <?php if (!empty($cat['description'])): ?>
                                        <div style="font-size:0.8rem; color:var(--admin-text-muted); max-width:260px;">
                                            <?= truncate_words($cat['description'], 70) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code style="background:#f1f5f9; padding:0.2rem 0.4rem; border-radius:4px; font-size:0.8rem;">
                                        <?= h($cat['slug']) ?>
                                    </code>
                                </td>
                                <td>
                                    <span class="status-badge badge-category">
                                        <?= (int)$cat['total_articles'] ?> guides
                                    </span>
                                </td>
                                <td style="font-size:0.8rem; color:var(--admin-text-muted);">
                                    <?= format_date($cat['created_at']) ?>
                                </td>
                                <td style="text-align:right;">
                                    <div class="action-buttons" style="justify-content:flex-end;">
                                        <?php if (is_super_admin() || has_permission('categories.edit')): ?>
                                        <a href="<?= ADMIN_URL ?>/categories.php?edit=<?= $cat['id'] ?>" class="btn-action" title="Edit Category">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </a>
                                        <?php endif; ?>

                                        <?php if (is_super_admin() || has_permission('categories.delete')): ?>
                                        <form action="<?= ADMIN_URL ?>/categories.php" method="POST" style="display:inline;" onsubmit="return confirmDelete('Are you sure you want to delete category \'<?= addslashes(h($cat['name'])) ?>\'?');">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn-action action-delete" title="Delete Category">
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:2rem; color:var(--admin-text-muted);">
                                No categories found. Create one using the form on the left!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
