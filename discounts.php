<?php
/**
 * Urdu Books World - Discounts Catalog Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$percent = isset($_GET['percent']) && is_numeric($_GET['percent']) ? (int)$_GET['percent'] : 10;
$lang_filter = isset($_GET['lang_filter']) ? trim($_GET['lang_filter']) : '';

// Pagination setup
$limit = 24;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Build SQL Query
$sql = "SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur
        FROM books b
        JOIN authors a ON b.author_id = a.id
        WHERE b.is_active = 1 AND b.discount_percent >= ?";
$params = [$percent];

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
$sql .= " ORDER BY b.discount_percent DESC, b.created_at DESC";

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
    error_log("Discounts catalog loading failed: " . $e->getMessage());
    $books = [];
}

$page_title = $percent . "% " . t('discounts');
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">
    <div class="catalog-layout">
        
        <!-- Sidebar filters -->
        <aside class="catalog-sidebar">
            <div class="sidebar-widget">
                <h3 class="widget-title">Discount Ranges</h3>
                <ul class="widget-list">
                    <li><a href="?percent=10" class="<?php echo $percent === 10 ? 'active font-bold' : ''; ?>">10% OFF &amp; More</a></li>
                    <li><a href="?percent=20" class="<?php echo $percent === 20 ? 'active font-bold' : ''; ?>">20% OFF &amp; More</a></li>
                    <li><a href="?percent=30" class="<?php echo $percent === 30 ? 'active font-bold' : ''; ?>">30% OFF &amp; More</a></li>
                    <li><a href="?percent=40" class="<?php echo $percent === 40 ? 'active font-bold' : ''; ?>">40% OFF &amp; More</a></li>
                    <li><a href="?percent=50" class="<?php echo $percent === 50 ? 'active font-bold' : ''; ?>">50% OFF &amp; More</a></li>
                </ul>
            </div>
            
            <div class="sidebar-widget">
                <h3 class="widget-title">Language</h3>
                <ul class="widget-list">
                    <li><a href="?percent=<?php echo $percent; ?>&lang_filter=all" class="<?php echo (empty($lang_filter) || $lang_filter === 'all') ? 'active' : ''; ?>">All Languages</a></li>
                    <li><a href="?percent=<?php echo $percent; ?>&lang_filter=Urdu" class="<?php echo $lang_filter === 'Urdu' ? 'active' : ''; ?>">Urdu</a></li>
                    <li><a href="?percent=<?php echo $percent; ?>&lang_filter=English" class="<?php echo $lang_filter === 'English' ? 'active' : ''; ?>">English</a></li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Catalog Feed -->
        <main class="catalog-main">
            <!-- Feed Header -->
            <div class="catalog-feed-header">
                <div>
                    <h1 class="catalog-title <?php echo (CURRENT_LANG === 'ur') ? 'lang-ur-font' : ''; ?>">
                        <?php echo $percent; ?>% <?php echo t('discounts'); ?>
                    </h1>
                    <p class="results-count text-muted"><?php echo $total_books; ?> Books Found</p>
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
                            <span class="discount-badge"><?php echo sprintf(t('save_percent'), $discount_pct_val); ?></span>
                            
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
                                        <span class="price-original"><?php echo money($original_price); ?></span>
                                        <span class="price-current font-bold text-maroon"><?php echo money($final_price); ?></span>
                                    </div>
                                    <div class="price-pkr text-muted hidden-mobile">
                                        (<?php echo pkr($final_price); ?>)
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
                <p class="text-center text-muted margin-top-xl">No bargain books found matching this discount level.</p>
            <?php endif; ?>
        </main>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
