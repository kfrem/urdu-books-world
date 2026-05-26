<?php
/**
 * Urdu Books World - Admin Authors Management
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

$edit_id = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : null;
$edit_author = null;

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM authors WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_author = $stmt->fetch();
}

// 1. Process Actions (Add / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'save') {
        $name_en = trim($_POST['name_en']);
        $name_ur = trim($_POST['name_ur']);
        $slug_val = trim($_POST['slug']);
        $bio_en = trim($_POST['bio_en']);
        $bio_ur = trim($_POST['bio_ur']);
        
        if (empty($name_en) || empty($name_ur)) {
            $error_msg = 'Both English and Urdu author names are required.';
        } else {
            if (empty($slug_val)) {
                $slug_val = slugify($name_en);
            } else {
                $slug_val = slugify($slug_val);
            }
            
            try {
                if ($edit_id) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE authors SET 
                        slug = ?, name_en = ?, name_ur = ?, bio_en = ?, bio_ur = ?
                        WHERE id = ?");
                    $stmt->execute([$slug_val, $name_en, $name_ur, $bio_en, $bio_ur, $edit_id]);
                    $success_msg = 'Author details updated successfully!';
                    $edit_id = null;
                    $edit_author = null;
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO authors 
                        (slug, name_en, name_ur, bio_en, bio_ur) 
                        VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$slug_val, $name_en, $name_ur, $bio_en, $bio_ur]);
                    $success_msg = 'Author registered successfully!';
                }
                $_POST = [];
            } catch (PDOException $e) {
                $error_msg = 'Failed to save author. Slug might already exist.';
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
            $stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
            $stmt->execute([$del_id]);
            $success_msg = 'Author deleted successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Could not delete author. Books are currently catalogued under this author.';
        }
    }
}

// Fetch all authors
try {
    $all_authors = $pdo->query("
        SELECT a.*, COUNT(b.id) AS book_count 
        FROM authors a
        LEFT JOIN books b ON a.id = b.author_id
        GROUP BY a.id
        ORDER BY a.name_en ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $all_authors = [];
}
?>

<div class="admin-page-header">
    <h1 class="admin-title">Authors Catalog</h1>
    <p class="text-muted">Manage literary authors, customize biographical profiles, and check catalog metrics.</p>
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
                <i class="fa fa-user-pen text-maroon"></i> <?php echo $edit_author ? 'Edit Author Profile' : 'Register Author'; ?>
            </h3>
            <?php if ($edit_author): ?>
                <a href="authors.php" class="btn btn-outline-maroon btn-xs">Cancel Edit</a>
            <?php endif; ?>
        </div>
        <div class="padding-md">
            <form action="authors.php<?php echo $edit_author ? '?edit=' . $edit_author['id'] : ''; ?>" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save">
                
                <div class="form-row-2-cols">
                    <div class="form-group">
                        <label class="form-label">Full Name (English) *</label>
                        <input type="text" name="name_en" class="form-control" required placeholder="e.g. Bano Qudsia" value="<?php echo $edit_author ? e($edit_author['name_en']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Full Name (Urdu) *</label>
                        <input type="text" name="name_ur" class="form-control lang-ur-font" required placeholder="بانو قدسیہ" value="<?php echo $edit_author ? e($edit_author['name_ur']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Slug URL (Optional)</label>
                    <input type="text" name="slug" class="form-control" placeholder="bano-qudsia" value="<?php echo $edit_author ? e($edit_author['slug']) : ''; ?>">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Biography (English)</label>
                    <textarea name="bio_en" class="form-control" rows="3"><?php echo $edit_author ? e($edit_author['bio_en']) : ''; ?></textarea>
                </div>
                <div class="form-group margin-top-md">
                    <label class="form-label">Biography (Urdu)</label>
                    <textarea name="bio_ur" class="form-control lang-ur-font" rows="3" style="line-height: 2.0;"><?php echo $edit_author ? e($edit_author['bio_ur']) : ''; ?></textarea>
                </div>
                
                <div class="margin-top-lg">
                    <button type="submit" class="btn btn-maroon btn-full-width">Save Author Details</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Right Column: Current authors list -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fa fa-users text-maroon"></i> Registered Authors</h3>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Author Name</th>
                        <th>Biographical Snippet</th>
                        <th class="text-center">Books</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_authors)): ?>
                        <?php foreach ($all_authors as $a): ?>
                            <tr>
                                <td>
                                    <div class="font-bold text-dark"><?php echo e($a['name_en']); ?></div>
                                    <div class="text-maroon small-font font-bold"><?php echo e($a['name_ur']); ?></div>
                                    <div class="text-muted small-font">Slug: <?php echo e($a['slug']); ?></div>
                                </td>
                                <td>
                                    <div class="small-font text-muted" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo e($a['bio_en'] ?: 'No biography added.'); ?>
                                    </div>
                                </td>
                                <td class="text-center font-bold text-maroon"><?php echo $a['book_count']; ?></td>
                                <td class="text-right whitespace-nowrap">
                                    <a href="?edit=<?php echo $a['id']; ?>" class="action-btn-icon text-maroon" title="Edit"><i class="fa fa-pen"></i></a>
                                    <a href="?delete=<?php echo $a['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-icon text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this author?')"><i class="fa fa-trash-can"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No authors created yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
