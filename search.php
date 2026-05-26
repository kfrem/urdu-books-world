<?php
/**
 * Urdu Books World - Search Results
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Pagination setup
$limit = 24;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

if (empty($q)) {
    redirect(SITE_URL . '/index.php');
}

// Build Search SQL Query dynamically
$sql = "SELECT DISTINCT b.*, a.name_en AS author_en, a.name_ur AS author_ur
        FROM books b
        JOIN authors a ON b.author_id = a.id
        JOIN publishers p ON b.publisher_id = p.id
        JOIN categories c ON b.category_id = c.id
        WHERE b.is_active = 1";
$params = [];
$search_term = "%$q%";

switch ($type) {
    case 'title':
        $sql .= " AND (b.title_en LIKE ? OR b.title_ur LIKE ? OR b.subtitle_en LIKE ? OR b.subtitle_ur LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        break;
        
    case 'author':
        $sql .= " AND (a.name_en LIKE ? OR a.name_ur LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        break;
        
    case 'publisher':
        $sql .= " AND (p.name_en LIKE ? OR p.name_ur LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        break;
        
    case 'subject':
        $sql .= " AND (c.name_en LIKE ? OR c.name_ur LIKE ? OR b.loc_classification LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        break;
        
    case 'isbn':
        $sql .= " AND (b.isbn LIKE ? OR b.sku LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        break;
        
    case 'all':
    default:
        $sql .= " AND (
            b.title_en LIKE ? OR b.title_ur LIKE ? 
            OR b.subtitle_en LIKE ? OR b.subtitle_ur LIKE ? 
            OR a.name_en LIKE ? OR a.name_ur LIKE ? 
            OR p.name_en LIKE ? OR p.name_ur LIKE ? 
            OR c.name_en LIKE ? OR c.name_ur LIKE ? 
            OR b.isbn LIKE ? OR b.sku LIKE ? 
            OR b.loc_classification LIKE ?
        )";
        for ($i = 0; $i < 13; $i++) {
            $params[] = $search_term;
        }
        break;
}

// Count total records for pagination BEFORE limit/offset
try {
    $count_sql = str_replace("DISTINCT b.*, a.name_en AS author_en, a.name_ur AS author_ur", "COUNT(DISTINCT b.id)", $sql);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_books = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_books / $limit);
} catch (PDOException $e) {
    error_log("Search count failed: " . $e->getMessage());
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
    $param_index = 1;
    foreach ($params as $p) {
        $type_binding = is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($param_index++, $p, $type_binding);
    }
    $stmt->execute();
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Search query failed: " . $e->getMessage());
    $books = [];
}

$page_title = "Search Results for '" . e($q) . "'";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">
    <div class="catalog-layout">
        
        <!-- Sidebar Quick Info -->
        <aside class="catalog-sidebar">
            <div class="sidebar-widget">
                <h3 class="widget-title">Search Scope</h3>
                <p class="text-muted">You searched for:</p>
                <div class="search-term-display">"<?php echo e($q); ?>"</div>
                <p class="text-muted margin-top-sm">Search Criteria:</p>
                <div class="badge badge-maroon font-medium"><?php echo t('search_' . $type) ?: $type; ?></div>
                <hr class="meta-divider margin-top-md">
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline-maroon btn-sm btn-full-width margin-top-sm">New Search</a>
            </div>
        </aside>
        
        <!-- Main Catalog Feed -->
        <main class="catalog-main">
            <!-- Feed Header -->
            <div class="catalog-feed-header">
                <div>
                    <h1 class="catalog-title">
                        Search Results
                    </h1>
                    <p class="results-count text-muted">
                        <?php echo $total_books; ?> Books Found for "<?php echo e($q); ?>"
                    </p>
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
                                    <?php echo book_card_action_button($book); ?>
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
                                <li><a href="<?php echo build_search_pagination_url($page - 1); ?>" class="pagination-btn"><i class="fa fa-chevron-left"></i></a></li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li>
                                    <a href="<?php echo build_search_pagination_url($i); ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li><a href="<?php echo build_search_pagination_url($page + 1); ?>" class="pagination-btn"><i class="fa fa-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- No Results fallback -->
                <div class="no-results-box text-center margin-top-xl">
                    <i class="fa fa-magnifying-glass text-muted text-4xl"></i>
                    <h3 class="margin-top-md">No books found matching "<?php echo e($q); ?>"</h3>
                    <p class="text-muted">Would you like to request us to procure this book for you?</p>
                    <a href="<?php echo SITE_URL; ?>/request-book.php?title=<?php echo urlencode($q); ?>" class="btn btn-gold btn-md margin-top-md">
                        <i class="fa fa-paper-plane"></i> Request a Book
                    </a>
                </div>
            <?php endif; ?>
        </main>
        
    </div>
</div>

<script>
    function applySort(val) {
        var url = new URL(window.location.href);
        url.searchParams.set('sort', val);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }
</script>

<?php
function build_search_pagination_url($target_page) {
    $params = $_GET;
    $params['page'] = $target_page;
    return SITE_URL . '/search.php?' . http_build_query($params);
}

require_once __DIR__ . '/includes/footer.php';
?>
