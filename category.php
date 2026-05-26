<?php
/**
 * Urdu Books World - Category Listing & Filtering Catalog
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$lang_filter = isset($_GET['lang_filter']) ? trim($_GET['lang_filter']) : '';
$in_stock = isset($_GET['in_stock']) && $_GET['in_stock'] === '1' ? true : false;
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0.00;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 50.00;
$discount_pct = isset($_GET['discount_pct']) && is_numeric($_GET['discount_pct']) ? (int)$_GET['discount_pct'] : 0;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Pagination setup
$limit = 24;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch Category Details if slug is provided
$current_category = null;
$category_id = null;
if (!empty($slug)) {
    try {
        $cat_stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
        $cat_stmt->execute([$slug]);
        $current_category = $cat_stmt->fetch();
        if ($current_category) {
            $category_id = $current_category['id'];
        }
    } catch (PDOException $e) {
        error_log("Category load failed: " . $e->getMessage());
    }
}

// Build SQL Query dynamically based on active filters
$sql = "SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur
        FROM books b
        JOIN authors a ON b.author_id = a.id
        WHERE b.is_active = 1";
$params = [];

// Apply Category Filter (include children subcategories if applicable)
if ($category_id) {
    // Check if category has subcategories
    try {
        $sub_stmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ? AND is_active = 1");
        $sub_stmt->execute([$category_id]);
        $sub_ids = $sub_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($sub_ids)) {
            $sub_ids[] = $category_id; // Add parent itself
            $placeholders = implode(',', array_fill(0, count($sub_ids), '?'));
            $sql .= " AND b.category_id IN ($placeholders)";
            foreach ($sub_ids as $id) {
                $params[] = $id;
            }
        } else {
            $sql .= " AND b.category_id = ?";
            $params[] = $category_id;
        }
    } catch (PDOException $e) {
        $sql .= " AND b.category_id = ?";
        $params[] = $category_id;
    }
}

// Apply Language Filter
if (!empty($lang_filter) && $lang_filter !== 'all') {
    $sql .= " AND b.language = ?";
    $params[] = $lang_filter;
}

// Apply Stock Filter
if ($in_stock) {
    $sql .= " AND b.stock_quantity > 0";
}

// Apply Price Filters (calculating final prices with discount applied!)
$sql .= " AND (b.price_gbp * (1 - b.discount_percent / 100)) >= ?";
$params[] = $min_price;

$sql .= " AND (b.price_gbp * (1 - b.discount_percent / 100)) <= ?";
$params[] = $max_price;

// Apply Discount Filter
if ($discount_pct > 0) {
    $sql .= " AND b.discount_percent >= ?";
    $params[] = $discount_pct;
}

// Count total records for pagination BEFORE limit/offset
try {
    $count_sql = str_replace("b.*, a.name_en AS author_en, a.name_ur AS author_ur", "COUNT(*)", $sql);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_books = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_books / $limit);
} catch (PDOException $e) {
    error_log("Count query failed: " . $e->getMessage());
    $total_books = 0;
    $total_pages = 1;
}

// Apply Sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY (b.price_gbp * (1 - b.discount_percent / 100)) ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY (b.price_gbp * (1 - b.discount_percent / 100)) DESC";
        break;
    case 'discount_desc':
        $sql .= " ORDER BY b.discount_percent DESC";
        break;
    case 'alpha_asc':
        $sql .= " ORDER BY b.title_en ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY b.is_new_arrival DESC, b.created_at DESC";
        break;
}

// Apply Limit & Offset for Pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Fetch filtered books
try {
    $stmt = $pdo->prepare($sql);
    // Explicit bind parameters is needed because offset and limit must be INT in some MySQL modes
    $param_index = 1;
    foreach ($params as $p) {
        $type = is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($param_index++, $p, $type);
    }
    $stmt->execute();
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Catalog query failed: " . $e->getMessage());
    $books = [];
}

// Fetch all main categories for sidebar links
try {
    $sidebar_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC");
    $sidebar_stmt->execute();
    $sidebar_categories = $sidebar_stmt->fetchAll();
} catch (PDOException $e) {
    $sidebar_categories = [];
}

// Fetch subcategories of active parent category if applicable
$sidebar_subcategories = [];
if ($current_category) {
    $parent_search_id = $current_category['parent_id'] ?: $current_category['id'];
    try {
        $sub_list_stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC");
        $sub_list_stmt->execute([$parent_search_id]);
        $sidebar_subcategories = $sub_list_stmt->fetchAll();
    } catch (PDOException $e) {
        $sidebar_subcategories = [];
    }
}

$page_title = $current_category ? lang_val($current_category, 'name') : t('publications');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">
    <div class="catalog-layout">
        
        <!-- Sidebar Filters -->
        <aside class="catalog-sidebar">
            <form action="<?php echo SITE_URL; ?>/category.php" method="GET" id="filter-form">
                <?php if (!empty($slug)): ?>
                    <input type="hidden" name="slug" value="<?php echo e($slug); ?>">
                <?php endif; ?>
                
                <!-- Category links -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Categories</h3>
                    <ul class="widget-list">
                        <li>
                            <a href="<?php echo SITE_URL; ?>/category.php" class="<?php echo empty($slug) ? 'active' : ''; ?>">
                                All Publications
                            </a>
                        </li>
                        <?php foreach ($sidebar_categories as $s_cat): ?>
                            <li>
                                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo e($s_cat['slug']); ?>" class="<?php echo ($slug === $s_cat['slug'] || ($current_category && $current_category['parent_id'] == $s_cat['id'])) ? 'active' : ''; ?>">
                                    <?php echo lang_val($s_cat, 'name'); ?>
                                </a>
                                <?php if (($slug === $s_cat['slug'] || ($current_category && $current_category['parent_id'] == $s_cat['id'])) && !empty($sidebar_subcategories)): ?>
                                    <ul class="widget-sublist">
                                        <?php foreach ($sidebar_subcategories as $sub): ?>
                                            <li>
                                                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo e($sub['slug']); ?>" class="<?php echo $slug === $sub['slug'] ? 'active font-bold' : ''; ?>">
                                                    - <?php echo lang_val($sub, 'name'); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Language Filter checkbox -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Language</h3>
                    <div class="widget-checkbox-group">
                        <label class="widget-checkbox-label">
                            <input type="radio" name="lang_filter" value="all" <?php echo (empty($lang_filter) || $lang_filter === 'all') ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                            <span>All Languages</span>
                        </label>
                        <label class="widget-checkbox-label">
                            <input type="radio" name="lang_filter" value="Urdu" <?php echo $lang_filter === 'Urdu' ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                            <span>Urdu</span>
                        </label>
                        <label class="widget-checkbox-label">
                            <input type="radio" name="lang_filter" value="English" <?php echo $lang_filter === 'English' ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                            <span>English</span>
                        </label>
                        <label class="widget-checkbox-label">
                            <input type="radio" name="lang_filter" value="Arabic" <?php echo $lang_filter === 'Arabic' ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                            <span>Arabic</span>
                        </label>
                    </div>
                </div>
                
                <!-- Price Range slider/input -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Price Range</h3>
                    <div class="widget-price-inputs">
                        <div class="price-input-box">
                            <span>Min (£)</span>
                            <input type="number" name="min_price" value="<?php echo $min_price; ?>" class="price-input" min="0" max="50">
                        </div>
                        <div class="price-input-box">
                            <span>Max (£)</span>
                            <input type="number" name="max_price" value="<?php echo $max_price; ?>" class="price-input" min="0" max="50">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-maroon btn-sm btn-full-width margin-top-xs">Apply Price</button>
                </div>
                
                <!-- Stock Availability Checkbox -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Availability</h3>
                    <label class="widget-checkbox-label">
                        <input type="checkbox" name="in_stock" value="1" <?php echo $in_stock ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                        <span>Exclude Out of Stock</span>
                    </label>
                </div>
                
                <!-- Discount Percentage Filter -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Discounts</h3>
                    <select name="discount_pct" class="widget-select" onchange="document.getElementById('filter-form').submit()">
                        <option value="0" <?php echo $discount_pct === 0 ? 'selected' : ''; ?>>All Books</option>
                        <option value="10" <?php echo $discount_pct === 10 ? 'selected' : ''; ?>>10% OFF or more</option>
                        <option value="20" <?php echo $discount_pct === 20 ? 'selected' : ''; ?>>20% OFF or more</option>
                        <option value="30" <?php echo $discount_pct === 30 ? 'selected' : ''; ?>>30% OFF or more</option>
                        <option value="50" <?php echo $discount_pct === 50 ? 'selected' : ''; ?>>50% OFF or more</option>
                    </select>
                </div>
                
                <!-- Carry Sort Parameter in form -->
                <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
            </form>
        </aside>
        
        <!-- Main Catalog Feed -->
        <main class="catalog-main">
            <!-- Feed Header -->
            <div class="catalog-feed-header">
                <div>
                    <h1 class="catalog-title <?php echo (CURRENT_LANG === 'ur') ? 'lang-ur-font' : ''; ?>">
                        <?php echo e($page_title); ?>
                    </h1>
                    <p class="results-count text-muted"><?php echo $total_books; ?> Books Found</p>
                </div>
                
                <!-- Sort Selector -->
                <div class="catalog-sort-wrapper">
                    <label for="catalog-sort" class="sort-label">Sort By:</label>
                    <select id="catalog-sort" class="sort-select" onchange="applySort(this.value)">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="discount_desc" <?php echo $sort === 'discount_desc' ? 'selected' : ''; ?>>Highest Discount</option>
                        <option value="alpha_asc" <?php echo $sort === 'alpha_asc' ? 'selected' : ''; ?>>Alphabetical</option>
                    </select>
                </div>
            </div>
            
            <!-- Book Grid -->
            <?php if (!empty($books)): ?>
                <div class="book-grid grid-3-cols margin-top-md">
                    <?php foreach ($books as $book): ?>
                        <?php 
                            $original_price = (float)$book['price_gbp'];
                            $discount_pct_val = (int)$book['discount_percent'];
                            $final_price = $original_price - ($original_price * ($discount_pct_val / 100));
                        ?>
                        <article class="book-card">
                            <?php if ($discount_pct_val > 0): ?>
                                <span class="discount-badge"><?php echo sprintf(t('save_percent'), $discount_pct_val); ?></span>
                            <?php endif; ?>
                            
                            <div class="book-card-cover-wrapper">
                                <img src="<?php echo get_book_cover($book); ?>" alt="<?php echo e($book['title_en']); ?> Cover" class="book-card-cover" loading="lazy">
                                <div class="book-cover-hover-overlay">
                                    <a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>" class="btn btn-gold btn-sm"><i class="fa fa-eye"></i> View</a>
                                </div>
                            </div>
                            
                            <div class="book-card-info">
                                <h3 class="book-card-title-en"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>"><?php echo e($book['title_en']); ?></a></h3>
                                <h4 class="book-card-title-ur lang-ur-font"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>"><?php echo e($book['title_ur']); ?></a></h4>
                                <p class="book-card-author"><span class="text-muted"><?php echo t('author'); ?>:</span> <span class="font-medium"><?php echo lang_val($book, 'author'); ?></span></p>
                                
                                <div class="book-card-price-row">
                                    <div class="price-gbp">
                                        <?php if ($discount_pct_val > 0): ?>
                                            <span class="price-original"><?php echo money($original_price); ?></span>
                                            <span class="price-current font-bold text-maroon"><?php echo money($final_price); ?></span>
                                        <?php else: ?>
                                            <span class="price-current font-bold text-maroon"><?php echo money($original_price); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="price-pkr text-muted hidden-mobile">
                                        (<?php echo pkr($discount_pct_val > 0 ? $final_price : $original_price); ?>)
                                    </div>
                                </div>
                                
                                <div class="book-card-actions">
                                    <button type="button" class="btn btn-maroon btn-full-width add-to-cart-btn" data-id="<?php echo $book['id']; ?>">
                                        <i class="fa fa-cart-plus"></i> <?php echo t('add_to_cart'); ?>
                                    </button>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination Control -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container margin-top-xl">
                        <ul class="pagination-list">
                            <?php if ($page > 1): ?>
                                <li><a href="<?php echo build_pagination_url($page - 1); ?>" class="pagination-btn"><i class="fa fa-chevron-left"></i></a></li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="<?php echo build_pagination_url($i); ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li><a href="<?php echo build_pagination_url($page + 1); ?>" class="pagination-btn"><i class="fa fa-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- No Results fallback -->
                <div class="no-results-box text-center margin-top-xl">
                    <i class="fa fa-folder-open text-muted text-4xl"></i>
                    <h3 class="margin-top-md">No books found matching this filter</h3>
                    <p class="text-muted">Would you like to request us to procure this book for you?</p>
                    <a href="<?php echo SITE_URL; ?>/request-book.php" class="btn btn-gold btn-md margin-top-md">
                        <i class="fa fa-paper-plane"></i> Request a Book
                    </a>
                </div>
            <?php endif; ?>
        </main>
        
    </div>
</div>

<script>
    // JS Helper to apply sort parameters
    function applySort(val) {
        var url = new URL(window.location.href);
        url.searchParams.set('sort', val);
        url.searchParams.set('page', '1'); // Reset to page 1
        window.location.href = url.toString();
    }
</script>

<?php
// PHP Helper to construct pagination URLs
function build_pagination_url($target_page) {
    $params = $_GET;
    $params['page'] = $target_page;
    return SITE_URL . '/category.php?' . http_build_query($params);
}

require_once __DIR__ . '/includes/footer.php';
?>
