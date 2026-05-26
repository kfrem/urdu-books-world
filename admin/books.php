<?php
/**
 * Urdu Books World - Admin Books Listing and CSV Bulk Importer
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

// 1. Secure Delete Request (Requires GET CSRF token verification!)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    
    if ($token !== $_SESSION['csrf_token']) {
        $error_msg = 'CSRF security verification failed. Access Denied.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
            $stmt->execute([$del_id]);
            $success_msg = 'Book deleted successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Could not delete book. It might be referenced in an active order.';
        }
    }
}

// 2. CSV Bulk Importer (Extra Credit Feature)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csv_import'])) {
    verify_csrf_token();
    
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['csv_file']['tmp_name'];
        $file_name = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($ext !== 'csv') {
            $error_msg = 'Invalid file format. Please upload a standard comma-separated CSV file.';
        } else {
            $handle = fopen($file_tmp, 'r');
            if ($handle !== false) {
                // Read headers
                $headers = fgetcsv($handle, 1000, ',');
                
                // Expected headers mapping
                $expected = ['sku', 'isbn', 'title_en', 'title_ur', 'author_slug', 'publisher_slug', 'category_slug', 'language', 'price_gbp', 'discount_percent', 'stock_quantity'];
                
                $valid_header = true;
                foreach ($expected as $exp) {
                    if (!in_array($exp, $headers)) {
                        $valid_header = false;
                    }
                }
                
                if (!$valid_header) {
                    $error_msg = 'Missing required CSV headers. Ensure your columns contain: ' . implode(', ', $expected);
                } else {
                    $header_map = array_flip($headers);
                    $imported = 0;
                    $skipped = 0;
                    
                    try {
                        $pdo->beginTransaction();
                        
                        while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                            // Check row alignment
                            if (count($row) < count($headers)) {
                                $skipped++;
                                continue;
                            }
                            
                            $sku = trim($row[$header_map['sku']]);
                            $isbn = trim($row[$header_map['isbn']]);
                            $title_en = trim($row[$header_map['title_en']]);
                            $title_ur = trim($row[$header_map['title_ur']]);
                            $author_slug = trim($row[$header_map['author_slug']]);
                            $publisher_slug = trim($row[$header_map['publisher_slug']]);
                            $category_slug = trim($row[$header_map['category_slug']]);
                            $language = trim($row[$header_map['language']] ?: 'Urdu');
                            $price_gbp = (float)trim($row[$header_map['price_gbp']]);
                            $discount_pct = (int)trim($row[$header_map['discount_percent']]);
                            $stock_qty = (int)trim($row[$header_map['stock_quantity']]);
                            
                            if (empty($sku) || empty($title_en) || empty($title_ur)) {
                                $skipped++;
                                continue;
                            }
                            
                            // A. Get or Create Author
                            $stmt = $pdo->prepare("SELECT id FROM authors WHERE slug = ?");
                            $stmt->execute([$author_slug]);
                            $author_id = $stmt->fetchColumn();
                            if (!$author_id) {
                                $author_name = str_replace('-', ' ', ucwords($author_slug));
                                $stmt_ins = $pdo->prepare("INSERT INTO authors (slug, name_en, name_ur) VALUES (?, ?, ?)");
                                $stmt_ins->execute([$author_slug, $author_name, $author_name]);
                                $author_id = $pdo->lastInsertId();
                            }
                            
                            // B. Get or Create Publisher
                            $stmt = $pdo->prepare("SELECT id FROM publishers WHERE slug = ?");
                            $stmt->execute([$publisher_slug]);
                            $publisher_id = $stmt->fetchColumn();
                            if (!$publisher_id) {
                                $pub_name = str_replace('-', ' ', ucwords($publisher_slug));
                                $stmt_ins = $pdo->prepare("INSERT INTO publishers (slug, name_en, name_ur) VALUES (?, ?, ?)");
                                $stmt_ins->execute([$publisher_slug, $pub_name, $pub_name]);
                                $publisher_id = $pdo->lastInsertId();
                            }
                            
                            // C. Get or Create Category
                            $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                            $stmt->execute([$category_slug]);
                            $category_id = $stmt->fetchColumn();
                            if (!$category_id) {
                                $cat_name = str_replace('-', ' ', ucwords($category_slug));
                                $stmt_ins = $pdo->prepare("INSERT INTO categories (slug, name_en, name_ur) VALUES (?, ?, ?)");
                                $stmt_ins->execute([$category_slug, $cat_name, $cat_name]);
                                $category_id = $pdo->lastInsertId();
                            }
                            
                            // D. Insert or Update Book by SKU
                            $stmt = $pdo->prepare("SELECT id FROM books WHERE sku = ?");
                            $stmt->execute([$sku]);
                            $existing_id = $stmt->fetchColumn();
                            
                            $slug_final = slugify($title_en);
                            
                            if ($existing_id) {
                                // Update
                                $stmt_upd = $pdo->prepare("UPDATE books SET 
                                    isbn = ?, title_en = ?, title_ur = ?, author_id = ?, publisher_id = ?, category_id = ?, language = ?, price_gbp = ?, discount_percent = ?, stock_quantity = ?
                                    WHERE id = ?");
                                $stmt_upd->execute([$isbn, $title_en, $title_ur, $author_id, $publisher_id, $category_id, $language, $price_gbp, $discount_pct, $stock_qty, $existing_id]);
                            } else {
                                // Insert new
                                $stmt_new = $pdo->prepare("INSERT INTO books 
                                    (slug, sku, isbn, title_en, title_ur, author_id, publisher_id, category_id, language, price_gbp, discount_percent, stock_quantity, is_active) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                                $stmt_new->execute([$slug_final, $sku, $isbn, $title_en, $title_ur, $author_id, $publisher_id, $category_id, $language, $price_gbp, $discount_pct, $stock_qty]);
                            }
                            $imported++;
                        }
                        
                        $pdo->commit();
                        $success_msg = sprintf("Import Complete! Successfully processed %d books (%d rows skipped).", $imported, $skipped);
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error_msg = "CSV Import Failed: " . $e->getMessage();
                    }
                }
                fclose($handle);
            }
        }
    } else {
        $error_msg = 'Please choose a valid CSV file to upload.';
    }
}

// 3. Search & Pagination Queries
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Count books query
$count_sql = "SELECT COUNT(*) FROM books b JOIN authors a ON b.author_id = a.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $count_sql .= " AND (b.title_en LIKE ? OR b.title_ur LIKE ? OR b.sku LIKE ? OR a.name_en LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

try {
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_books = (int)$stmt->fetchColumn();
    $total_pages = ceil($total_books / $limit);
} catch (PDOException $e) {
    $total_books = 0;
    $total_pages = 1;
}

// Select books query
$select_sql = "SELECT b.*, a.name_en AS author_en, p.name_en AS publisher_en, c.name_en AS category_en
               FROM books b
               JOIN authors a ON b.author_id = a.id
               JOIN publishers p ON b.publisher_id = p.id
               JOIN categories c ON b.category_id = c.id
               WHERE 1=1";

if (!empty($search)) {
    $select_sql .= " AND (b.title_en LIKE ? OR b.title_ur LIKE ? OR b.sku LIKE ? OR a.name_en LIKE ?)";
}

$select_sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";

try {
    $stmt = $pdo->prepare($select_sql);
    $bind_index = 1;
    foreach ($params as $p) {
        $stmt->bindValue($bind_index++, $p);
    }
    $stmt->bindValue($bind_index++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($bind_index++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $books = [];
}
?>

<div class="admin-page-header split-header">
    <div>
        <h1 class="admin-title">Books Inventory</h1>
        <p class="text-muted">Manage your store's catalog catalog, upload cover art, and import items in bulk.</p>
    </div>
    
    <div class="header-action-buttons">
        <a href="book-edit.php" class="btn btn-maroon btn-sm"><i class="fa fa-plus-circle"></i> Add New Book</a>
    </div>
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

<!-- Bulk CSV Import Form Card -->
<div class="admin-card margin-top-lg">
    <div class="admin-card-header">
        <h3 class="admin-card-title"><i class="fa fa-file-csv text-maroon"></i> CSV Bulk Importer</h3>
    </div>
    <div class="padding-md">
        <p class="small-font text-muted">Import or update dozens of books at once. Column headers required in CSV: <strong>sku, isbn, title_en, title_ur, author_slug, publisher_slug, category_slug, language, price_gbp, discount_percent, stock_quantity</strong>.</p>
        <form action="books.php" method="POST" enctype="multipart/form-data" class="margin-top-md inline-upload-form">
            <?php echo csrf_field(); ?>
            <div class="form-row-file-input">
                <input type="file" name="csv_file" accept=".csv" required class="form-control-file">
                <button type="submit" name="csv_import" class="btn btn-maroon btn-sm">Upload &amp; Bulk Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Search & Filters -->
<div class="admin-card margin-top-lg">
    <div class="padding-md">
        <form action="books.php" method="GET" class="admin-search-form">
            <div class="search-input-group">
                <input type="text" name="search" placeholder="Search by title, SKU, or author..." class="search-input" value="<?php echo e($search); ?>">
                <button type="submit" class="search-submit-btn"><i class="fa fa-search"></i> Search</button>
            </div>
            <?php if (!empty($search)): ?>
                <div class="margin-top-xs text-right"><a href="books.php" class="small-font text-maroon">Clear Search</a></div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Table Feed -->
    <div class="table-responsive">
        <table class="admin-table text-left">
            <thead>
                <tr>
                    <th>Cover</th>
                    <th>Book Details</th>
                    <th>Author / Publisher</th>
                    <th>Category</th>
                    <th class="text-right">Price</th>
                    <th class="text-center">Discount</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($books)): ?>
                    <?php foreach ($books as $b): ?>
                        <tr>
                            <!-- Cover -->
                            <td style="width: 55px;">
                                <img src="<?php echo get_book_cover($b); ?>" alt="Cover" class="admin-item-cover-thumb" style="width: 40px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #E0E0E0;">
                            </td>
                            
                            <!-- Detail -->
                            <td>
                                <div class="font-bold text-dark text-medium"><?php echo e($b['title_en']); ?></div>
                                <div class="text-maroon small-font font-bold"><?php echo e($b['sku']); ?></div>
                                <div class="text-muted small-font">ISBN: <?php echo e($b['isbn'] ?: 'N/A'); ?></div>
                            </td>
                            
                            <!-- Relations -->
                            <td>
                                <div class="small-font">A: <?php echo e($b['author_en']); ?></div>
                                <div class="small-font text-muted">P: <?php echo e($b['publisher_en']); ?></div>
                            </td>
                            
                            <!-- Category -->
                            <td><span class="badge badge-secondary"><?php echo e($b['category_en']); ?></span></td>
                            
                            <!-- Price -->
                            <td class="text-right font-bold text-maroon"><?php echo money($b['price_gbp']); ?></td>
                            
                            <!-- Discount -->
                            <td class="text-center font-medium"><?php echo $b['discount_percent']; ?>%</td>
                            
                            <!-- Stock -->
                            <td class="text-center">
                                <?php if ($b['stock_quantity'] <= 5): ?>
                                    <span class="badge badge-danger" title="Low Stock!"><?php echo $b['stock_quantity']; ?></span>
                                <?php else: ?>
                                    <span class="badge badge-success"><?php echo $b['stock_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Status -->
                            <td class="text-center">
                                <?php if ($b['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Actions -->
                            <td class="text-right whitespace-nowrap">
                                <a href="book-edit.php?id=<?php echo $b['id']; ?>" class="action-btn-icon text-maroon" title="Edit book"><i class="fa fa-pen"></i></a>
                                <a href="books.php?action=delete&id=<?php echo $b['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-icon text-danger" title="Delete book" onclick="return confirm('Are you sure you want to delete this book? This action is irreversible.')"><i class="fa fa-trash-can"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted padding-lg">No books found in the catalog database.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="admin-card-footer padding-md">
            <div class="pagination-container" style="justify-content: flex-end;">
                <ul class="pagination-list">
                    <?php if ($page > 1): ?>
                        <li><a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="pagination-btn"><i class="fa fa-chevron-left"></i></a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li><a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li><a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="pagination-btn"><i class="fa fa-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
