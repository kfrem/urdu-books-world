<?php
/**
 * Urdu Books World - Admin Publishers Management
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

$edit_id = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_publisher = null;

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM publishers WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_publisher = $stmt->fetch();
}

// 1. Process Actions (Add / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'save') {
        $name_en = trim($_POST['name_en']);
        $name_ur = trim($_POST['name_ur']);
        $slug_val = trim($_POST['slug']);
        $country = trim($_POST['country'] ?: 'Pakistan');
        
        if (empty($name_en) || empty($name_ur)) {
            $error_msg = 'Both English and Urdu publisher names are required.';
        } else {
            if (empty($slug_val)) {
                $slug_val = slugify($name_en);
            } else {
                $slug_val = slugify($slug_val);
            }
            
            try {
                if ($edit_id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE publishers SET 
                        slug = ?, name_en = ?, name_ur = ?, country = ?
                        WHERE id = ?");
                    $stmt->execute([$slug_val, $name_en, $name_ur, $country, $edit_id]);
                    $success_msg = 'Publisher details updated successfully!';
                    $edit_id = null;
                    $edit_publisher = null;
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO publishers 
                        (slug, name_en, name_ur, country) 
                        VALUES (?, ?, ?, ?)");
                    $stmt->execute([$slug_val, $name_en, $name_ur, $country]);
                    $success_msg = 'Publisher registered successfully!';
                }
                $_POST = [];
            } catch (PDOException $e) {
                $error_msg = 'Failed to save publisher. Slug might already exist.';
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
            $stmt = $pdo->prepare("DELETE FROM publishers WHERE id = ?");
            $stmt->execute([$del_id]);
            $success_msg = 'Publisher deleted successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Could not delete publisher. Active books are assigned to it.';
        }
    }
}

// Fetch all publishers
try {
    $all_publishers = $pdo->query("
        SELECT p.*, COUNT(b.id) AS book_count 
        FROM publishers p
        LEFT JOIN books b ON p.id = b.publisher_id
        GROUP BY p.id
        ORDER BY p.name_en ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $all_publishers = [];
}
?>

<div class="admin-page-header">
    <h1 class="admin-title">Publishing Houses</h1>
    <p class="text-muted">Manage distribution partners, source countries, and check total mapped collections.</p>
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
                <i class="fa fa-building-circle-plus text-maroon"></i> <?php echo $edit_publisher ? 'Edit Publisher Details' : 'Register Publisher'; ?>
            </h3>
            <?php if ($edit_publisher): ?>
                <a href="publishers.php" class="btn btn-outline-maroon btn-xs">Cancel Edit</a>
            <?php endif; ?>
        </div>
        <div class="padding-md">
            <form action="publishers.php<?php echo $edit_publisher ? '?edit=' . $edit_publisher['id'] : ''; ?>" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save">
                
                <div class="form-group">
                    <label class="form-label">Publisher Name (English) *</label>
                    <input type="text" name="name_en" class="form-control" required placeholder="e.g. Sang-e-Meel Publications" value="<?php echo $edit_publisher ? e($edit_publisher['name_en']) : ''; ?>">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Publisher Name (Urdu) *</label>
                    <input type="text" name="name_ur" class="form-control lang-ur-font" required placeholder="سنگ میل پبلیکیشنز" value="<?php echo $edit_publisher ? e($edit_publisher['name_ur']) : ''; ?>">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Slug URL (Optional)</label>
                    <input type="text" name="slug" class="form-control" placeholder="sang-e-meel" value="<?php echo $edit_publisher ? e($edit_publisher['slug']) : ''; ?>">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Source Country</label>
                    <input type="text" name="country" class="form-control" placeholder="Pakistan" value="<?php echo $edit_publisher ? e($edit_publisher['country']) : ''; ?>">
                </div>
                
                <div class="margin-top-lg">
                    <button type="submit" class="btn btn-maroon btn-full-width">Save Publisher Details</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Current publishers list -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fa fa-building text-maroon"></i> Registered Partners</h3>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Publisher</th>
                        <th>Country</th>
                        <th class="text-center">Books</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_publishers)): ?>
                        <?php foreach ($all_publishers as $p): ?>
                            <tr>
                                <td>
                                    <div class="font-bold text-dark"><?php echo e($p['name_en']); ?></div>
                                    <div class="text-maroon small-font font-bold"><?php echo e($p['name_ur']); ?></div>
                                    <div class="text-muted small-font">Slug: <?php echo e($p['slug']); ?></div>
                                </td>
                                <td><span class="badge badge-secondary"><?php echo e($p['country']); ?></span></td>
                                <td class="text-center font-bold text-maroon"><?php echo $p['book_count']; ?></td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="?edit=<?php echo $p['id']; ?>" class="action-btn-icon text-maroon" title="Edit"><i class="fa fa-pen"></i></a>
                                    <a href="?delete=<?php echo $p['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this publisher?')"><i class="fa fa-trash-can"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No publishers created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
