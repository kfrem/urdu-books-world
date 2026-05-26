<?php
/**
 * Urdu Books World - Admin Categories Management
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

$edit_id = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_category = null;

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

// 1. Process Actions (Add / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'save') {
        $name_en = trim($_POST['name_en']);
        $name_ur = trim($_POST['name_ur']);
        $slug_val = trim($_POST['slug']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $sort_order = (int)$_POST['sort_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name_en) || empty($name_ur)) {
            $error_msg = 'Both English and Urdu category names are required.';
        } else {
            if (empty($slug_val)) {
                $slug_val = slugify($name_en);
            } else {
                $slug_val = slugify($slug_val);
            }
            
            try {
                if ($edit_id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE categories SET 
                        slug = ?, name_en = ?, name_ur = ?, parent_id = ?, sort_order = ?, is_active = ?
                        WHERE id = ?");
                    $stmt->execute([$slug_val, $name_en, $name_ur, $parent_id, $sort_order, $is_active, $edit_id]);
                    $success_msg = 'Category updated successfully!';
                    $edit_id = null; // Exit edit mode
                    $edit_category = null;
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO categories 
                        (slug, name_en, name_ur, parent_id, sort_order, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$slug_val, $name_en, $name_ur, $parent_id, $sort_order, $is_active]);
                    $success_msg = 'Category created successfully!';
                }
                $_POST = [];
            } catch (PDOException $e) {
                $error_msg = 'Failed to save category. Slug might already exist.';
            }
        }
    }
}

// 2. Handle Delete Action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    
    if ($token !== $_SESSION['csrf_token']) {
        $error_msg = 'CSRF Security token verification failed.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$del_id]);
            $success_msg = 'Category deleted successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Could not delete category. Books are currently assigned to it.';
        }
    }
}

// Fetch all categories for list
try {
    $list_stmt = $pdo->prepare("
        SELECT c.*, p.name_en AS parent_name, COUNT(b.id) AS book_count 
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        LEFT JOIN books b ON c.id = b.category_id
        GROUP BY c.id
        ORDER BY c.sort_order ASC, c.id ASC
    ");
    $list_stmt->execute();
    $all_categories = $list_stmt->fetchAll();
    
    // Fetch parent categories for dropdown list (exclude active edit category to prevent cycles!)
    if ($edit_id) {
        $parents_stmt = $pdo->prepare("SELECT id, name_en FROM categories WHERE parent_id IS NULL AND id != ? ORDER BY name_en ASC");
        $parents_stmt->execute([$edit_id]);
    } else {
        $parents_stmt = $pdo->query("SELECT id, name_en FROM categories WHERE parent_id IS NULL ORDER BY name_en ASC");
    }
    $parent_categories = $parents_stmt->fetchAll();
} catch (PDOException $e) {
    $all_categories = [];
    $parent_categories = [];
}
?>

<div class="admin-page-header">
    <h1 class="admin-title">Taxonomy Categories</h1>
    <p class="text-muted">Manage product directories, parent hierarchies, sorting values, and active filters.</p>
</div>

<!-- Alerts -->
<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger margin-top-md">
        <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success margin-top-md">
        <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
    </div>
<?php endif; ?>

<div class="admin-content-split-2 margin-top-lg">
    <!-- Left Column: Add / Edit Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title">
                <i class="fa fa-folder-plus text-maroon"></i> <?php echo $edit_category ? 'Edit Category' : 'Create Category'; ?>
            </h3>
            <?php if ($edit_category): ?>
                <a href="categories.php" class="btn btn-outline-maroon btn-xs">Cancel Edit</a>
            <?php endif; ?>
        </div>
        <div class="padding-md">
            <form action="categories.php<?php echo $edit_category ? '?edit=' . $edit_category['id'] : ''; ?>" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save">
                
                <div class="form-group">
                    <label class="form-label">Category Name (English) *</label>
                    <input type="text" name="name_en" class="form-control" required placeholder="e.g. Novels" value="<?php echo $edit_category ? e($edit_category['name_en']) : ''; ?>">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Category Name (Urdu) *</label>
                    <input type="text" name="name_ur" class="form-control lang-ur-font" required placeholder="ناول" value="<?php echo $edit_category ? e($edit_category['name_ur']) : ''; ?>">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Slug URL (Optional)</label>
                    <input type="text" name="slug" class="form-control" placeholder="novels (auto-generated if blank)" value="<?php echo $edit_category ? e($edit_category['slug']) : ''; ?>">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Parent Category</label>
                    <select name="parent_id" class="form-control">
                        <option value="">-- None (Top Level) --</option>
                        <?php foreach ($parent_categories as $pc): ?>
                            <option value="<?php echo $pc['id']; ?>" <?php echo $edit_category && $edit_category['parent_id'] == $pc['id'] ? 'selected' : ''; ?>><?php echo e($pc['name_en']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row-2-cols margin-top-md">
                    <div class="form-group">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo $edit_category ? $edit_category['sort_order'] : '0'; ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 8px;">
                        <label class="widget-checkbox-label">
                            <input type="checkbox" name="is_active" value="1" <?php echo (!$edit_category) || ($edit_category && $edit_category['is_active']) ? 'checked' : ''; ?>>
                            <span>Is Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="margin-top-lg">
                    <button type="submit" class="btn btn-maroon btn-full-width">Save Category Details</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Current categories tree list -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fa fa-folder-tree text-maroon"></i> Current Directories</h3>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Details</th>
                        <th>Parent</th>
                        <th class="text-center">Order</th>
                        <th class="text-center">Books</th>
                        <th class="text-center">Active</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_categories)): ?>
                        <?php foreach ($all_categories as $c): ?>
                            <tr>
                                <td>
                                    <div class="font-bold text-dark"><?php echo e($c['name_en']); ?></div>
                                    <div class="text-maroon small-font font-bold"><?php echo e($c['name_ur']); ?></div>
                                    <div class="text-muted small-font">Slug: <?php echo e($c['slug']); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($c['parent_name'])): ?>
                                        <span class="badge badge-secondary"><?php echo e($c['parent_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small-font">Top Level</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $c['sort_order']; ?></td>
                                <td class="text-center font-bold text-maroon"><?php echo $c['book_count']; ?></td>
                                <td class="text-center">
                                    <?php if ($c['is_active']): ?>
                                        <span class="badge badge-success">Yes</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="?edit=<?php echo $c['id']; ?>" class="action-btn-icon text-maroon" title="Edit"><i class="fa fa-pen"></i></a>
                                    <a href="?delete=<?php echo $c['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')"><i class="fa fa-trash-can"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No categories created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
