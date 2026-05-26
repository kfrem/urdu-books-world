<?php
/**
 * Urdu Books World - New Arrivals Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$lang_filter = isset($_GET['lang_filter']) ? trim($_GET['lang_filter']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Pagination setup
$limit = 24;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Build SQL Query
$sql = "SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur
        FROM books b
        JOIN authors a ON b.author_id = a.id
        WHERE b.is_active = 1 AND (b.is_new_arrival = 1 OR b.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY))";
$params = [];

if (!empty($lang_filter) && $lang_filter !== 'all') {
    $sql .= " AND b.language = ?";
    $params[] = $lang_filter;
}

// Count total
try {
    $count_sql = str_replace("b.*, a.name_en AS author_en, a.name_ur AS author_ur", "COUNT(*)", $sql);
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_books = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_books / $limit);
} catch (PDOException $e) {
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
        $sql .= " ORDER BY b.created_at DESC";
        break;
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

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
    error_log("New arrivals feed failed: " . $e->getMessage());
    $books = [];
}

$page_title = t('new_arrivals');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">
    <div class="catalog-layout">
        
        <!-- Sidebar filters -->
        <aside class="catalog-sidebar">
            <div class="sidebar-widget">
                <h3 class="widget-title">Language</h3>
                <ul class="widget-list">
                    <li><a href="?lang_filter=all" class="<?php echo (empty($lang_filter) || $lang_filter === 'all') ? 'active' : ''; ?>">All Languages</a></li>
                    <li><a href="?lang_filter=Urdu" class="<?php echo $lang_filter === 'Urdu' ? 'active' : ''; ?>">Urdu</a></li>
                    <li><a href="?lang_filter=English" class="<?php echo $lang_filter === 'English' ? 'active' : ''; ?>">English</a></li>
                </ul>
            </div>
            <div class="sidebar-widget">
                <h3 class="widget-title">Quick Information</h3>
                <p class="text-muted">This page displays all Pakistani and translated literary works added to our warehouse in the last 90 days.</p>
            </div>
        </aside>
        
        <!-- Main Catalog Feed -->
        <main class="catalog-main">
            <!-- Feed Header -->
            <div class="catalog-feed-header">
                <div>
                    <h1 class="catalog-title <?php echo (CURRENT_LANG === 'ur') ? 'lang-ur-font' : ''; ?>">
                        <?php echo t('new_arrivals'); ?>
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
                                </div>
                                
                                <div class="book-card-actions">
                                    <?php echo book_card_action_button($book); ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted margin-top-xl">No new arrivals found.</p>
            <?php endif; ?>
        </main>
        
    </div>
</div>

<script>
    function applySort(val) {
        var url = new URL(window.location.href);
        url.searchParams.set('sort', val);
        window.location.href = url.toString();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
