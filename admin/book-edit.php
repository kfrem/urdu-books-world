<?php
/**
 * Urdu Books World - Admin Add/Edit Book Form
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

$book_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
$book = null;

// Fetch Book details if in Edit Mode
if ($book_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$book_id]);
        $book = $stmt->fetch();
        if (!$book) {
            redirect('books.php');
        }
    } catch (PDOException $e) {
        $error_msg = 'Error loading book details.';
    }
}

// Fetch lists for dropdowns
try {
    $authors = $pdo->query("SELECT id, name_en FROM authors ORDER BY name_en ASC")->fetchAll();
    $publishers = $pdo->query("SELECT id, name_en FROM publishers ORDER BY name_en ASC")->fetchAll();
    $categories = $pdo->query("SELECT id, name_en FROM categories ORDER BY name_en ASC")->fetchAll();
} catch (PDOException $e) {
    $authors = []; $publishers = []; $categories = [];
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    verify_csrf_token();
    
    $sku = trim($_POST['sku']);
    $isbn = trim($_POST['isbn']);
    $title_en = trim($_POST['title_en']);
    $title_ur = trim($_POST['title_ur']);
    $subtitle_en = trim($_POST['subtitle_en']);
    $subtitle_ur = trim($_POST['subtitle_ur']);
    $author_id = (int)$_POST['author_id'];
    $publisher_id = (int)$_POST['publisher_id'];
    $category_id = (int)$_POST['category_id'];
    $language = trim($_POST['language']);
    $description_en = trim($_POST['description_en']);
    $description_ur = trim($_POST['description_ur']);
    $pages = !empty($_POST['pages']) ? (int)$_POST['pages'] : null;
    $weight = !empty($_POST['weight_grams']) ? (int)$_POST['weight_grams'] : null;
    $binding = trim($_POST['binding']);
    $year = !empty($_POST['year_published']) ? (int)$_POST['year_published'] : null;
    $price = (float)$_POST['price_gbp'];
    $discount = (int)$_POST['discount_percent'];
    $stock = (int)$_POST['stock_quantity'];
    $is_new = isset($_POST['is_new_arrival']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $loc = trim($_POST['loc_classification']);
    $marc = trim($_POST['marc_record']);
    
    if (empty($sku) || empty($title_en) || empty($title_ur) || empty($author_id) || empty($publisher_id) || empty($category_id) || empty($price)) {
        $error_msg = 'Please fill in all required fields (marked with *).';
    } else {
        
        // A. Handle Image Upload
        $cover_image = $book ? $book['cover_image'] : '';
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['cover_file']['tmp_name'];
            $file_name = $_FILES['cover_file']['name'];
            $file_size = $_FILES['cover_file']['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            
            if (!in_array($file_ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
                $error_msg = 'Invalid image file. Only JPG, PNG, and WEBP cover assets are allowed.';
            } else {
                // Generate a unique filename
                $new_filename = $sku . '-' . time() . '.' . $file_ext;
                $upload_dir = dirname(__DIR__) . '/assets/images/covers/';
                
                // Create folder if not exists
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                    $cover_image = $new_filename;
                } else {
                    $error_msg = 'Failed to save cover image asset on Hostinger file system.';
                }
            }
        } elseif (!empty($_POST['cover_url'])) {
            $cover_image = trim($_POST['cover_url']);
        }
        
        if (empty($error_msg)) {
            $slug_gen = $book ? $book['slug'] : slugify($title_en);
            
            try {
                if ($book_id) {
                    // Update
                    $stmt_upd = $pdo->prepare("UPDATE books SET 
                        isbn = ?, title_en = ?, title_ur = ?, subtitle_en = ?, subtitle_ur = ?, author_id = ?, publisher_id = ?, category_id = ?, language = ?, description_en = ?, description_ur = ?, pages = ?, weight_grams = ?, binding = ?, year_published = ?, price_gbp = ?, discount_percent = ?, stock_quantity = ?, is_new_arrival = ?, is_featured = ?, is_active = ?, cover_image = ?, loc_classification = ?, marc_record = ?
                        WHERE id = ?");
                    $stmt_upd->execute([$isbn, $title_en, $title_ur, $subtitle_en, $subtitle_ur, $author_id, $publisher_id, $category_id, $language, $description_en, $description_ur, $pages, $weight, $binding, $year, $price, $discount, $stock, $is_new, $is_featured, $is_active, $cover_image, $loc, $marc, $book_id]);
                    $success_msg = 'Book details updated successfully!';
                    // reload
                    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
                    $stmt->execute([$book_id]);
                    $book = $stmt->fetch();
                } else {
                    // Check SKU uniqueness
                    $stmt = $pdo->prepare("SELECT id FROM books WHERE sku = ?");
                    $stmt->execute([$sku]);
                    if ($stmt->fetch()) {
                        $error_msg = 'This SKU code is already allocated to another book.';
                    } else {
                        // Insert new
                        $stmt_ins = $pdo->prepare("INSERT INTO books 
                            (slug, sku, isbn, title_en, title_ur, subtitle_en, subtitle_ur, author_id, publisher_id, category_id, language, description_en, description_ur, pages, weight_grams, binding, year_published, price_gbp, discount_percent, stock_quantity, is_new_arrival, is_featured, is_active, cover_image, loc_classification, marc_record) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_ins->execute([$slug_gen, $sku, $isbn, $title_en, $title_ur, $subtitle_en, $subtitle_ur, $author_id, $publisher_id, $category_id, $language, $description_en, $description_ur, $pages, $weight, $binding, $year, $price, $discount, $stock, $is_new, $is_featured, $is_active, $cover_image, $loc, $marc]);
                        
                        $success_msg = 'New book successfully added to the catalog!';
                        // Reset forms
                        $_POST = [];
                    }
                }
            } catch (PDOException $e) {
                error_log("Book save error: " . $e->getMessage());
                $error_msg = 'Failed to write book details to MySQL database: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="admin-page-header split-header">
    <div>
        <h1 class="admin-title"><?php echo $book_id ? 'Edit Book Details' : 'Add New Book'; ?></h1>
        <p class="text-muted">Enter metadata in English and Urdu to populate the bilingual search system.</p>
    </div>
    <div class="header-action-buttons">
        <a href="books.php" class="btn btn-outline-maroon btn-sm"><i class="fa fa-arrow-left"></i> Back to Inventory</a>
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

<div class="admin-card margin-top-lg padding-lg">
    <form action="book-edit.php<?php echo $book_id ? '?id=' . $book_id : ''; ?>" method="POST" enctype="multipart/form-data" class="auth-form admin-book-form">
        <?php echo csrf_field(); ?>
        
        <h3 class="auth-box-section-title" style="margin-top: 0;">1. Core Metadata</h3>
        
        <div class="form-row-3-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">SKU Code <span class="text-maroon">*</span></label>
                <input type="text" name="sku" class="form-control" required placeholder="e.g. NOV-RAJ-003" value="<?php echo isset($_POST['sku']) ? e($_POST['sku']) : ($book ? e($book['sku']) : ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">ISBN Number</label>
                <input type="text" name="isbn" class="form-control" placeholder="e.g. 9789693504889" value="<?php echo isset($_POST['isbn']) ? e($_POST['isbn']) : ($book ? e($book['isbn']) : ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Book Language</label>
                <select name="language" class="form-control">
                    <option value="Urdu" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Urdu') || ($book && $book['language'] == 'Urdu') ? 'selected' : ''; ?>>Urdu</option>
                    <option value="English" <?php echo (isset($_POST['language']) && $_POST['language'] == 'English') || ($book && $book['language'] == 'English') ? 'selected' : ''; ?>>English</option>
                    <option value="Arabic" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Arabic') || ($book && $book['language'] == 'Arabic') ? 'selected' : ''; ?>>Arabic</option>
                    <option value="Punjabi" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Punjabi') || ($book && $book['language'] == 'Punjabi') ? 'selected' : ''; ?>>Punjabi</option>
                    <option value="Persian" <?php echo (isset($_POST['language']) && $_POST['language'] == 'Persian') || ($book && $book['language'] == 'Persian') ? 'selected' : ''; ?>>Persian</option>
                </select>
            </div>
        </div>

        <div class="form-row-2-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Title (English) <span class="text-maroon">*</span></label>
                <input type="text" name="title_en" class="form-control" required placeholder="e.g. Raja Gidh" value="<?php echo isset($_POST['title_en']) ? e($_POST['title_en']) : ($book ? e($book['title_en']) : ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Title (Urdu) <span class="text-maroon">*</span></label>
                <input type="text" name="title_ur" class="form-control lang-ur-font" required placeholder="راجہ گدھ" value="<?php echo isset($_POST['title_ur']) ? e($_POST['title_ur']) : ($book ? e($book['title_ur']) : ''); ?>">
            </div>
        </div>

        <div class="form-row-2-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Subtitle (English)</label>
                <input type="text" name="subtitle_en" class="form-control" placeholder="Optional english description text" value="<?php echo isset($_POST['subtitle_en']) ? e($_POST['subtitle_en']) : ($book ? e($book['subtitle_en']) : ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Subtitle (Urdu)</label>
                <input type="text" name="subtitle_ur" class="form-control lang-ur-font" placeholder="اختیاری ذیلی عنوان" value="<?php echo isset($_POST['subtitle_ur']) ? e($_POST['subtitle_ur']) : ($book ? e($book['subtitle_ur']) : ''); ?>">
            </div>
        </div>

        <h3 class="auth-box-section-title margin-top-lg">2. Relations</h3>

        <div class="form-row-3-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Author <span class="text-maroon">*</span></label>
                <select name="author_id" class="form-control" required>
                    <option value="">-- Choose Author --</option>
                    <?php foreach ($authors as $a): ?>
                        <option value="<?php echo $a['id']; ?>" <?php echo (isset($_POST['author_id']) && $_POST['author_id'] == $a['id']) || ($book && $book['author_id'] == $a['id']) ? 'selected' : ''; ?>><?php echo e($a['name_en']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Publisher <span class="text-maroon">*</span></label>
                <select name="publisher_id" class="form-control" required>
                    <option value="">-- Choose Publisher --</option>
                    <?php foreach ($publishers as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo (isset($_POST['publisher_id']) && $_POST['publisher_id'] == $p['id']) || ($book && $book['publisher_id'] == $p['id']) ? 'selected' : ''; ?>><?php echo e($p['name_en']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Category <span class="text-maroon">*</span></label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Choose Category --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $c['id']) || ($book && $book['category_id'] == $c['id']) ? 'selected' : ''; ?>><?php echo e($c['name_en']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <h3 class="auth-box-section-title margin-top-lg">3. Sizing, Pricing, and Stock</h3>

        <div class="form-row-3-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Pages</label>
                <input type="number" name="pages" class="form-control" value="<?php echo isset($_POST['pages']) ? (int)$_POST['pages'] : ($book ? $book['pages'] : ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Weight (Grams)</label>
                <input type="number" name="weight_grams" class="form-control" value="<?php echo isset($_POST['weight_grams']) ? (int)$_POST['weight_grams'] : ($book ? $book['weight_grams'] : ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Year Published</label>
                <input type="number" name="year_published" class="form-control" placeholder="e.g. 2005" value="<?php echo isset($_POST['year_published']) ? (int)$_POST['year_published'] : ($book ? $book['year_published'] : ''); ?>">
            </div>
        </div>

        <div class="form-row-4-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Binding Type</label>
                <select name="binding" class="form-control">
                    <option value="Paperback" <?php echo ($book && $book['binding'] == 'Paperback') ? 'selected' : ''; ?>>Paperback</option>
                    <option value="Hardback" <?php echo ($book && $book['binding'] == 'Hardback') ? 'selected' : ''; ?>>Hardback</option>
                    <option value="Boxset" <?php echo ($book && $book['binding'] == 'Boxset') ? 'selected' : ''; ?>>Boxset</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Retail Price (GBP) *</label>
                <input type="number" step="0.01" name="price_gbp" class="form-control" required value="<?php echo isset($_POST['price_gbp']) ? (float)$_POST['price_gbp'] : ($book ? $book['price_gbp'] : ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Discount (%)</label>
                <input type="number" name="discount_percent" class="form-control" value="<?php echo isset($_POST['discount_percent']) ? (int)$_POST['discount_percent'] : ($book ? $book['discount_percent'] : '0'); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Stock Quantity *</label>
                <input type="number" name="stock_quantity" class="form-control" required value="<?php echo isset($_POST['stock_quantity']) ? (int)$_POST['stock_quantity'] : ($book ? $book['stock_quantity'] : '10'); ?>">
            </div>
        </div>

        <h3 class="auth-box-section-title margin-top-lg">4. Long Descriptions</h3>

        <div class="form-group margin-top-md">
            <label class="form-label">Description (English)</label>
            <textarea name="description_en" class="form-control" rows="4"><?php echo isset($_POST['description_en']) ? e($_POST['description_en']) : ($book ? e($book['description_en']) : ''); ?></textarea>
        </div>
        <div class="form-group margin-top-md">
            <label class="form-label">Description (Urdu)</label>
            <textarea name="description_ur" class="form-control lang-ur-font" rows="4" style="line-height: 2.0;"><?php echo isset($_POST['description_ur']) ? e($_POST['description_ur']) : ($book ? e($book['description_ur']) : ''); ?></textarea>
        </div>

        <h3 class="auth-box-section-title margin-top-lg">5. Media &amp; Strategic Cataloguing</h3>

        <div class="form-row-2-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Upload Cover File (JPG/PNG/WEBP)</label>
                <input type="file" name="cover_file" accept=".jpg,.jpeg,.png,.webp" class="form-control">
                <?php if ($book && !empty($book['cover_image'])): ?>
                    <p class="small-font text-muted margin-top-xs">Current Cover: <strong><?php echo e($book['cover_image']); ?></strong></p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Or Set External Cover URL</label>
                <input type="text" name="cover_url" class="form-control" placeholder="https://..." value="<?php echo ($book && strpos($book['cover_image'], 'http') === 0) ? e($book['cover_image']) : ''; ?>">
            </div>
        </div>

        <div class="form-group margin-top-md">
            <label class="form-label">Library of Congress (LOC) Classification</label>
            <input type="text" name="loc_classification" class="form-control code-font" placeholder="e.g. PK2200.Q8 R3" value="<?php echo isset($_POST['loc_classification']) ? e($_POST['loc_classification']) : ($book ? e($book['loc_classification']) : ''); ?>">
        </div>
        <div class="form-group margin-top-md">
            <label class="form-label">MARC Bibliographic Record (Raw text/ISO format)</label>
            <textarea name="marc_record" class="form-control code-font" rows="3" placeholder="00000cam a2200301 a 4500..."><?php echo isset($_POST['marc_record']) ? e($_POST['marc_record']) : ($book ? e($book['marc_record']) : ''); ?></textarea>
        </div>

        <h3 class="auth-box-section-title margin-top-lg">6. Global Toggles</h3>

        <div class="form-row-3-cols margin-top-md bg-light padding-md border-radius-sm">
            <label class="widget-checkbox-label">
                <input type="checkbox" name="is_new_arrival" value="1" <?php echo (isset($_POST['is_new_arrival'])) || ($book && $book['is_new_arrival']) ? 'checked' : ''; ?>>
                <span>New Arrival Feed</span>
            </label>
            <label class="widget-checkbox-label">
                <input type="checkbox" name="is_featured" value="1" <?php echo (isset($_POST['is_featured'])) || ($book && $book['is_featured']) ? 'checked' : ''; ?>>
                <span>Featured Homepage Grid</span>
            </label>
            <label class="widget-checkbox-label">
                <input type="checkbox" name="is_active" value="1" <?php echo (!isset($_POST['save_book']) && !$book) || ($book && $book['is_active']) ? 'checked' : ''; ?>>
                <span>Catalog Active / Publish</span>
            </label>
        </div>

        <div class="margin-top-xl">
            <button type="submit" name="save_book" class="btn btn-maroon btn-lg">
                <i class="fa fa-save"></i> Save Book Details
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
