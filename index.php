<?php
/**
 * Urdu Books World - Homepage
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

// Handle Newsletter Subscription (Bilingual)
$newsletter_msg = '';
$newsletter_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_subscribe'])) {
    verify_csrf_token();
    
    $email = filter_input(INPUT_POST, 'newsletter_email', FILTER_VALIDATE_EMAIL);
    if ($email) {
        try {
            // Check if already subscribed
            $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $newsletter_msg = t('subscribed_success'); // Friendly double subscription
                $newsletter_success = true;
            } else {
                $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
                $stmt->execute([$email]);
                $newsletter_msg = t('subscribed_success');
                $newsletter_success = true;
            }
        } catch (PDOException $e) {
            $newsletter_msg = t('registration_error');
        }
    } else {
        $newsletter_msg = t('invalid_email');
    }
    
    // For AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $newsletter_success, 'message' => $newsletter_msg]);
        exit;
    }
}

// Fetch 12 New Arrivals
try {
    $new_stmt = $pdo->prepare("
        SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur 
        FROM books b 
        JOIN authors a ON b.author_id = a.id
        WHERE b.is_active = 1
        ORDER BY b.is_new_arrival DESC, b.created_at DESC 
        LIMIT 12
    ");
    $new_stmt->execute();
    $new_arrivals = $new_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed fetching new arrivals: " . $e->getMessage());
    $new_arrivals = [];
}

// Fetch 8 Featured Books
try {
    $feat_stmt = $pdo->prepare("
        SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur 
        FROM books b 
        JOIN authors a ON b.author_id = a.id
        WHERE b.is_active = 1 AND b.is_featured = 1
        ORDER BY b.updated_at DESC
        LIMIT 8
    ");
    $feat_stmt->execute();
    $featured_books = $feat_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed fetching featured: " . $e->getMessage());
    $featured_books = [];
}

// Fetch Categories with counts for Category Grid
try {
    $grid_stmt = $pdo->prepare("
        SELECT c.*, COUNT(b.id) AS book_count 
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id AND b.is_active = 1
        WHERE c.is_active = 1
        GROUP BY c.id 
        ORDER BY book_count DESC 
        LIMIT 8
    ");
    $grid_stmt->execute();
    $grid_categories = $grid_stmt->fetchAll();
} catch (PDOException $e) {
    $grid_categories = [];
}

$page_title = t('home');
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Tagline Banner -->
<section class="hero-banner">
    <div class="container hero-content text-center">
        <h1 class="hero-title <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>">
            <?php if (is_rtl()): ?>
                اردو بکس ورلڈ میں خوش آمدید
            <?php else: ?>
                Welcome to Urdu Books World
            <?php endif; ?>
        </h1>
        <p class="hero-subtitle <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>">
            <?php if (is_rtl()): ?>
                برطانیہ اور یورپ کا سب سے بڑا دو لسانی کتاب گھر۔ اب آپ کی پسندیدہ اردو اور انگریزی کتب آپ کے دروازے پر۔
            <?php else: ?>
                The UK diaspora's premier bilingual online bookstore. Explore Urdu classics, translations, and Islamic literature.
            <?php endif; ?>
        </p>
        <div class="hero-cta-btn-group">
            <a href="#new-arrivals" class="btn btn-gold"><?php echo t('new_arrivals'); ?></a>
            <a href="<?php echo SITE_URL; ?>/category.php" class="btn btn-outline-white"><?php echo t('publications'); ?></a>
        </div>
    </div>
</section>

<!-- 2. Newsletter Post Message Alert (Non-AJAX Fallback) -->
<?php if (!empty($newsletter_msg)): ?>
<div class="container margin-top-lg">
    <div class="alert <?php echo $newsletter_success ? 'alert-success' : 'alert-danger'; ?>">
        <?php echo e($newsletter_msg); ?>
    </div>
</div>
<?php endif; ?>

<!-- 3. New Arrivals Grid (12 Books) -->
<section id="new-arrivals" class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fa fa-star text-gold"></i> <?php echo t('new_arrivals'); ?></h2>
            <div class="section-divider"></div>
        </div>
        
        <?php if (!empty($new_arrivals)): ?>
            <div class="book-grid">
                <?php foreach ($new_arrivals as $book): ?>
                    <?php 
                        $original_price = (float)$book['price_gbp'];
                        $discount_pct = (int)$book['discount_percent'];
                        $final_price = $original_price - ($original_price * ($discount_pct / 100));
                    ?>
                    <article class="book-card">
                        <!-- Discount Badge -->
                        <?php if ($discount_pct > 0): ?>
                            <span class="discount-badge"><?php echo sprintf(t('save_percent'), $discount_pct); ?></span>
                        <?php endif; ?>
                        
                        <!-- Book Cover Image -->
                        <div class="book-card-cover-wrapper">
                            <img src="<?php echo get_book_cover($book); ?>" alt="<?php echo e($book['title_en']); ?> Cover" class="book-card-cover" loading="lazy">
                            <div class="book-cover-hover-overlay">
                                <a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>" class="btn btn-gold btn-sm"><i class="fa fa-eye"></i> View</a>
                                <?php if (!empty($book['inside_preview_pdf'])): ?>
                                    <a href="<?php echo SITE_URL; ?>/assets/previews/<?php echo e($book['inside_preview_pdf']); ?>" target="_blank" class="preview-link-card"><i class="fa fa-book-open"></i> <?php echo t('inside_book'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Book Information -->
                        <div class="book-card-info">
                            <!-- Bilingual Titles -->
                            <h3 class="book-card-title-en"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>"><?php echo e($book['title_en']); ?></a></h3>
                            <h4 class="book-card-title-ur lang-ur-font"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>"><?php echo e($book['title_ur']); ?></a></h4>
                            
                            <!-- Author -->
                            <p class="book-card-author">
                                <span class="text-muted"><?php echo t('author'); ?>:</span> 
                                <span class="font-medium"><?php echo lang_val($book, 'author'); ?></span>
                            </p>
                            
                            <!-- Prices -->
                            <div class="book-card-price-row">
                                <div class="price-gbp">
                                    <?php if ($discount_pct > 0): ?>
                                        <span class="price-original"><?php echo money($original_price); ?></span>
                                        <span class="price-current font-bold text-maroon"><?php echo money($final_price); ?></span>
                                    <?php else: ?>
                                        <span class="price-current font-bold text-maroon"><?php echo money($original_price); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="price-pkr text-muted hidden-mobile">
                                    (<?php echo pkr($discount_pct > 0 ? $final_price : $original_price); ?>)
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="book-card-actions">
                                <button type="button" class="btn btn-maroon btn-full-width add-to-cart-btn" data-id="<?php echo $book['id']; ?>">
                                    <i class="fa fa-cart-plus"></i> <?php echo t('add_to_cart'); ?>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No new arrivals found.</p>
        <?php endif; ?>
    </div>
</section>

<!-- 4. Featured Books Grid (8 Books) -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fa fa-award text-gold"></i> Featured Books</h2>
            <div class="section-divider"></div>
        </div>
        
        <?php if (!empty($featured_books)): ?>
            <div class="book-grid">
                <?php foreach ($featured_books as $book): ?>
                    <?php 
                        $original_price = (float)$book['price_gbp'];
                        $discount_pct = (int)$book['discount_percent'];
                        $final_price = $original_price - ($original_price * ($discount_pct / 100));
                    ?>
                    <article class="book-card">
                        <!-- Discount Badge -->
                        <?php if ($discount_pct > 0): ?>
                            <span class="discount-badge"><?php echo sprintf(t('save_percent'), $discount_pct); ?></span>
                        <?php endif; ?>
                        
                        <!-- Book Cover Image -->
                        <div class="book-card-cover-wrapper">
                            <img src="<?php echo get_book_cover($book); ?>" alt="<?php echo e($book['title_en']); ?> Cover" class="book-card-cover" loading="lazy">
                            <div class="book-cover-hover-overlay">
                                <a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>" class="btn btn-gold btn-sm"><i class="fa fa-eye"></i> View</a>
                                <?php if (!empty($book['inside_preview_pdf'])): ?>
                                    <a href="<?php echo SITE_URL; ?>/assets/previews/<?php echo e($book['inside_preview_pdf']); ?>" target="_blank" class="preview-link-card"><i class="fa fa-book-open"></i> <?php echo t('inside_book'); ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Book Information -->
                        <div class="book-card-info">
                            <h3 class="book-card-title-en"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>"><?php echo e($book['title_en']); ?></a></h3>
                            <h4 class="book-card-title-ur lang-ur-font"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>"><?php echo e($book['title_ur']); ?></a></h4>
                            
                            <p class="book-card-author">
                                <span class="text-muted"><?php echo t('author'); ?>:</span> 
                                <span class="font-medium"><?php echo lang_val($book, 'author'); ?></span>
                            </p>
                            
                            <div class="book-card-price-row">
                                <div class="price-gbp">
                                    <?php if ($discount_pct > 0): ?>
                                        <span class="price-original"><?php echo money($original_price); ?></span>
                                        <span class="price-current font-bold text-maroon"><?php echo money($final_price); ?></span>
                                    <?php else: ?>
                                        <span class="price-current font-bold text-maroon"><?php echo money($original_price); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="price-pkr text-muted hidden-mobile">
                                    (<?php echo pkr($discount_pct > 0 ? $final_price : $original_price); ?>)
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
        <?php else: ?>
            <p class="text-center text-muted">No featured books found.</p>
        <?php endif; ?>
    </div>
</section>

<!-- 5. Browse by Category Grid -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fa fa-th-large text-gold"></i> Browse by Category</h2>
            <div class="section-divider"></div>
        </div>
        
        <?php if (!empty($grid_categories)): ?>
            <div class="category-grid">
                <?php foreach ($grid_categories as $cat): ?>
                    <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo e($cat['slug']); ?>" class="category-tile">
                        <div class="category-tile-icon"><i class="fa fa-folder text-maroon"></i></div>
                        <h3 class="category-tile-title-en"><?php echo e($cat['name_en']); ?></h3>
                        <h4 class="category-tile-title-ur lang-ur-font text-gold"><?php echo e($cat['name_ur']); ?></h4>
                        <span class="category-tile-count"><?php echo $cat['book_count']; ?> Books</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No categories found.</p>
        <?php endif; ?>
    </div>
</section>

<!-- 6. Why Choose Us Section -->
<section class="section-padding why-choose-us bg-maroon text-white">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="section-title text-white"><i class="fa fa-circle-question text-gold"></i> <?php echo t('why_choose_us'); ?></h2>
            <div class="section-divider bg-gold"></div>
        </div>
        
        <div class="features-grid container margin-top-lg">
            <div class="feature-col text-center">
                <div class="feature-icon-box bg-dark-maroon"><i class="fa fa-warehouse text-gold"></i></div>
                <h3 class="feature-title text-gold"><?php echo t('uk_warehouse'); ?></h3>
                <p class="feature-desc"><?php echo t('uk_warehouse_desc'); ?></p>
            </div>
            
            <div class="feature-col text-center">
                <div class="feature-icon-box bg-dark-maroon"><i class="fa fa-book-bookmark text-gold"></i></div>
                <h3 class="feature-title text-gold"><?php echo t('library_cataloguing'); ?></h3>
                <p class="feature-desc"><?php echo t('library_cataloguing_desc'); ?></p>
            </div>
            
            <div class="feature-col text-center">
                <div class="feature-icon-box bg-dark-maroon"><i class="fa fa-truck-fast text-gold"></i></div>
                <h3 class="feature-title text-gold"><?php echo t('fast_delivery'); ?></h3>
                <p class="feature-desc"><?php echo t('fast_delivery_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
