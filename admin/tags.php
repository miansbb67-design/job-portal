<?php
/**
 * Admin Panel - Tags Management
 * Exact match to user screenshot:
 * - Two-column layout
 * - Left column: Instant Search & Tags Table (TAG, SLUG, POSTS, ACTIONS: View, Edit, Delete)
 * - Right column: "Add tag" card (Name, Slug (optional), Description, Add button)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Authorization check: Requires content view permission
if (!is_super_admin() && !has_permission('posts.view') && !has_permission('categories.view')) {
    require_permission('posts.view');
}

$db = getDBConnection();

$errors = [];
$successMsg = '';

// Handle POST actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        set_flash('error', 'Security token expired. Please refresh and try again.');
        header('Location: ' . ADMIN_URL . '/tags.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // 1. ADD NEW TAG
    if ($action === 'add_tag') {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            set_flash('error', 'Tag name is required.');
        } else {
            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            } else {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
            }

            // Check duplicate name or slug
            $checkStmt = $db->prepare("SELECT id FROM tags WHERE name = ? OR slug = ? LIMIT 1");
            $checkStmt->execute([$name, $slug]);
            if ($checkStmt->fetch()) {
                set_flash('error', 'A tag with this name or slug already exists.');
            } else {
                $ins = $db->prepare("INSERT INTO tags (name, slug, description, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $ins->execute([$name, $slug, $description]);
                log_activity('create', 'tags', "Created tag '{$name}'", $db->lastInsertId());
                set_flash('success', "Tag '{$name}' created successfully!");
            }
        }
        header('Location: ' . ADMIN_URL . '/tags.php');
        exit;
    }

    // 2. EDIT TAG
    elseif ($action === 'edit_tag') {
        $tagId = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($tagId <= 0 || empty($name)) {
            set_flash('error', 'Invalid tag data.');
        } else {
            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
            } else {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $slug), '-'));
            }

            // Check duplicate on other tags
            $checkStmt = $db->prepare("SELECT id FROM tags WHERE (name = ? OR slug = ?) AND id != ? LIMIT 1");
            $checkStmt->execute([$name, $slug, $tagId]);
            if ($checkStmt->fetch()) {
                set_flash('error', 'Another tag with this name or slug already exists.');
            } else {
                $upd = $db->prepare("UPDATE tags SET name = ?, slug = ?, description = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$name, $slug, $description, $tagId]);
                log_activity('update', 'tags', "Updated tag '{$name}' (ID: {$tagId})", $tagId);
                set_flash('success', "Tag '{$name}' updated successfully!");
            }
        }
        header('Location: ' . ADMIN_URL . '/tags.php');
        exit;
    }

    // 3. DELETE TAG
    elseif ($action === 'delete_tag') {
        $tagId = (int)($_POST['id'] ?? 0);
        if ($tagId > 0) {
            $stmt = $db->prepare("SELECT name FROM tags WHERE id = ?");
            $stmt->execute([$tagId]);
            $tagName = $stmt->fetchColumn();

            $del = $db->prepare("DELETE FROM tags WHERE id = ?");
            $del->execute([$tagId]);
            log_activity('delete', 'tags', "Deleted tag '{$tagName}' (ID: {$tagId})", $tagId);
            set_flash('success', "Tag '{$tagName}' deleted successfully!");
        }
        header('Location: ' . ADMIN_URL . '/tags.php');
        exit;
    }
}

// Fetch all tags with post counts
$tagsQuery = "
    SELECT t.*, 
        (
            SELECT COUNT(*) 
            FROM articles a 
            WHERE (a.status = 'published' AND (a.is_trash = 0 OR a.is_trash IS NULL))
              AND (
                a.tags = t.name 
                OR a.tags LIKE CONCAT(t.name, ',%') 
                OR a.tags LIKE CONCAT('%, ', t.name, '%') 
                OR a.tags LIKE CONCAT('%,', t.name, '%')
              )
        ) AS post_count
    FROM tags t
    ORDER BY post_count DESC, t.name ASC
";
$tags = $db->query($tagsQuery)->fetchAll(PDO::FETCH_ASSOC);

$adminPageTitle = "Tags";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-tags-container" style="max-width:1400px; margin:0 auto; padding-bottom:3rem;">
    <!-- Page Title -->
    <div style="margin-bottom:1.5rem;">
        <h1 style="font-family:var(--admin-heading); font-size:1.6rem; font-weight:800; color:#0f172a; margin:0;">
            Tags
        </h1>
    </div>

    <!-- Main Two-Column Layout (Matching Screenshot) -->
    <div style="display:grid; grid-template-columns:1fr 380px; gap:2rem; align-items:start;">
        
        <!-- ================= LEFT COLUMN: SEARCH & TAGS TABLE ================= -->
        <div>
            <!-- Search Bar -->
            <div style="margin-bottom:1.25rem;">
                <input 
                    type="text" 
                    id="searchTagsInput" 
                    placeholder="Search tags..." 
                    style="width:100%; max-width:340px; padding:0.65rem 1rem; border:1px solid #e2e8f0; border-radius:8px; font-size:0.9rem; outline:none; background:#ffffff; transition:border-color 0.2s;"
                    onfocus="this.style.borderColor='#4f46e5'"
                    onblur="this.style.borderColor='#e2e8f0'"
                    oninput="filterTagsTable(this.value)"
                >
            </div>

            <!-- Tags Table Card -->
            <div class="admin-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;" id="tagsTable">
                        <thead>
                            <tr style="border-bottom:1px solid #f1f5f9; background:#fafafa;">
                                <th style="padding:0.9rem 1.25rem; font-weight:700; color:#64748b; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">TAG</th>
                                <th style="padding:0.9rem 1.25rem; font-weight:700; color:#64748b; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em;">SLUG</th>
                                <th style="padding:0.9rem 1.25rem; font-weight:700; color:#64748b; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; text-align:center;">POSTS</th>
                                <th style="padding:0.9rem 1.25rem; font-weight:700; color:#64748b; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; text-align:right;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="tagsTableBody">
                            <?php if (empty($tags)): ?>
                                <tr id="noTagsRow">
                                    <td colspan="4" style="padding:3rem 1.5rem; text-align:center; color:#94a3b8;">
                                        No tags found. Add your first tag using the form on the right.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tags as $t): ?>
                                <tr class="tag-row" style="border-bottom:1px solid #f8fafc; transition:background 0.15s;" data-name="<?= strtolower(h($t['name'])) ?>" data-slug="<?= strtolower(h($t['slug'])) ?>">
                                    <!-- Tag Name -->
                                    <td style="padding:1rem 1.25rem; font-weight:600; color:#0f172a;">
                                        <?= h($t['name']) ?>
                                    </td>

                                    <!-- Slug -->
                                    <td style="padding:1rem 1.25rem; color:#64748b; font-size:0.85rem; font-family:monospace;">
                                        /tag/<?= h($t['slug']) ?>
                                    </td>

                                    <!-- Posts Count -->
                                    <td style="padding:1rem 1.25rem; text-align:center; font-weight:700; color:#0f172a;">
                                        <?= (int)$t['post_count'] ?>
                                    </td>

                                    <!-- Actions: View, Edit, Delete -->
                                    <td style="padding:1rem 1.25rem; text-align:right;">
                                        <div style="display:inline-flex; gap:0.4rem; align-items:center;">
                                            <!-- View Button -->
                                            <a href="<?= BASE_URL ?>/tag/<?= urlencode($t['slug']) ?>" target="_blank" class="tag-action-btn" style="border:1px solid #cbd5e1; background:#ffffff; color:#334155; padding:0.35rem 0.85rem; border-radius:6px; font-weight:600; text-decoration:none; font-size:0.825rem; transition:all 0.15s;">
                                                View
                                            </a>

                                            <!-- Edit Button -->
                                            <button 
                                                type="button" 
                                                class="tag-action-btn" 
                                                style="border:1px solid #cbd5e1; background:#ffffff; color:#334155; padding:0.35rem 0.85rem; border-radius:6px; font-weight:600; font-size:0.825rem; cursor:pointer; transition:all 0.15s;"
                                                onclick='openEditTagModal(<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                            >
                                                Edit
                                            </button>

                                            <!-- Delete Button -->
                                            <form action="" method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Delete tag &quot;<?= addslashes(h($t['name'])) ?>&quot;? This cannot be undone.');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete_tag">
                                                <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                                <button type="submit" style="background:#e11d48; color:#ffffff; border:none; padding:0.35rem 0.85rem; border-radius:6px; font-weight:600; font-size:0.825rem; cursor:pointer; transition:background 0.15s;">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================= RIGHT COLUMN: ADD TAG CARD ================= -->
        <div>
            <div class="admin-card" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:1.75rem; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                <h3 style="font-family:var(--admin-heading); font-size:1.15rem; font-weight:800; color:#0f172a; margin:0 0 1.25rem 0;">
                    Add tag
                </h3>

                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_tag">

                    <!-- Name -->
                    <div style="margin-bottom:1.15rem;">
                        <label style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                            Name
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            class="admin-form-control" 
                            style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" 
                            placeholder="" 
                            required
                        >
                    </div>

                    <!-- Slug (optional) -->
                    <div style="margin-bottom:1.15rem;">
                        <label style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                            Slug (optional)
                        </label>
                        <input 
                            type="text" 
                            name="slug" 
                            class="admin-form-control" 
                            style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" 
                            placeholder=""
                        >
                    </div>

                    <!-- Description -->
                    <div style="margin-bottom:1.5rem;">
                        <label style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                            Description
                        </label>
                        <textarea 
                            name="description" 
                            rows="5" 
                            class="admin-form-control" 
                            style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; resize:vertical;" 
                            placeholder=""
                        ></textarea>
                    </div>

                    <!-- Add Button (Indigo/Purple matching screenshot) -->
                    <button 
                        type="submit" 
                        style="width:100%; background:#4f46e5; color:#ffffff; padding:0.75rem 1.25rem; font-weight:700; font-size:0.95rem; border-radius:8px; border:none; cursor:pointer; transition:background 0.2s;"
                        onmouseover="this.style.background='#4338ca'"
                        onmouseout="this.style.background='#4f46e5'"
                    >
                        Add
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- ================= EDIT TAG MODAL ================= -->
<div id="editTagModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,0.5); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:1.5rem;">
    <div style="background:#ffffff; border-radius:12px; max-width:480px; width:100%; padding:1.75rem; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
            <h3 style="font-family:var(--admin-heading); font-size:1.2rem; font-weight:800; color:#0f172a; margin:0;">
                Edit Tag
            </h3>
            <button type="button" onclick="closeEditTagModal()" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1;">
                &times;
            </button>
        </div>

        <form action="" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit_tag">
            <input type="hidden" name="id" id="editTagId" value="0">

            <div style="margin-bottom:1.15rem;">
                <label style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    Name
                </label>
                <input type="text" name="name" id="editTagName" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;" required>
            </div>

            <div style="margin-bottom:1.15rem;">
                <label style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    Slug
                </label>
                <input type="text" name="slug" id="editTagSlug" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem;">
            </div>

            <div style="margin-bottom:1.5rem;">
                <label style="display:block; font-weight:600; font-size:0.85rem; color:#334155; margin-bottom:0.35rem;">
                    Description
                </label>
                <textarea name="description" id="editTagDesc" rows="4" class="admin-form-control" style="width:100%; padding:0.65rem 0.85rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.9rem; resize:vertical;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:0.75rem;">
                <button type="button" onclick="closeEditTagModal()" class="admin-btn" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:0.6rem 1.25rem; font-weight:700; border-radius:8px;">
                    Cancel
                </button>
                <button type="submit" style="background:#4f46e5; color:#ffffff; padding:0.6rem 1.4rem; font-weight:700; border-radius:8px; border:none; cursor:pointer;">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

<script>
// Real-time JavaScript table search filter
function filterTagsTable(query) {
    const q = query.toLowerCase().trim();
    const rows = document.querySelectorAll('#tagsTableBody .tag-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const slug = row.getAttribute('data-slug') || '';
        if (name.includes(q) || slug.includes(q)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    let noResultsMsg = document.getElementById('noResultsMsgRow');
    if (visibleCount === 0 && rows.length > 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('tr');
            noResultsMsg.id = 'noResultsMsgRow';
            noResultsMsg.innerHTML = '<td colspan="4" style="padding:2.5rem; text-align:center; color:#94a3b8;">No matching tags found for "' + escapeHtml(query) + '".</td>';
            document.getElementById('tagsTableBody').appendChild(noResultsMsg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Edit Tag Modal functions
function openEditTagModal(tag) {
    document.getElementById('editTagId').value = tag.id || 0;
    document.getElementById('editTagName').value = tag.name || '';
    document.getElementById('editTagSlug').value = tag.slug || '';
    document.getElementById('editTagDesc').value = tag.description || '';
    const modal = document.getElementById('editTagModal');
    modal.style.display = 'flex';
}

function closeEditTagModal() {
    document.getElementById('editTagModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('editTagModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditTagModal();
    }
});
</script>
