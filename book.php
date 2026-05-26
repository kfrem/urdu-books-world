<?php
/**
 * Urdu Books World - Book Details Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    redirect(SITE_URL . '/404.php');
}

// Fetch Book with Author, Publisher, and Category
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               a.name_en AS author_en, a.name_ur AS author_ur, a.bio_en, a.bio_ur, a.slug AS author_slug,
               p.name_en AS publisher_en, p.name_ur AS publisher_ur,
               c.name_en AS category_en, c.name_ur AS category_ur, c.slug AS category_slug
        FROM books b
        JOIN authors a ON b.author_id = a.id
        JOIN publishers p ON b.publisher_id = p.id
        JOIN categories c ON b.category_id = c.id
        WHERE b.slug = ? AND b.is_active = 1
    ");
    $stmt->execute([$slug]);
    $book = $stmt->fetch();
    
    if (!$book) {
        redirect(SITE_URL . '/404.php');
    }
} catch (PDOException $e) {
    error_log("Failed loading book details: " . $e->getMessage());
    die("Error loading page content.");
}

// Fetch 6 Related Books
try {
    $rel_stmt = $pdo->prepare("
        SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur
        FROM books b
        JOIN authors a ON b.author_id = a.id
        WHERE b.category_id = ? AND b.id != ? AND b.is_active = 1
        LIMIT 6
    ");
    $rel_stmt->execute([$book['category_id'], $book['id']]);
    $related_books = $rel_stmt->fetchAll();
} catch (PDOException $e) {
    $related_books = [];
}

// Pre-fill parameters
$original_price = (float)$book['price_gbp'];
$discount_pct = (int)$book['discount_percent'];
$final_price = $original_price - ($original_price * ($discount_pct / 100));
$pkr_rate = (float)get_setting('pkr_to_gbp_rate', 360);
$whatsapp_num = get_setting('whatsapp_number', '447123456789');

// Pre-fill WhatsApp message text
$wa_price = money($final_price);
if (CURRENT_LANG === 'ur') {
    $wa_msg = "السلام علیکم! میں یہ کتاب خریدنے میں دلچسپی رکھتا ہوں:\nکتاب کا نام: " . $book['title_ur'] . " (SKU: " . $book['sku'] . ")\nقیمت: " . $wa_price . "\nبرائے مہربانی مجھے تفصیلات فراہم کریں۔ شکریہ!";
} else {
    $wa_msg = "Hello! I am interested in buying this book:\nTitle: " . $book['title_en'] . " (SKU: " . $book['sku'] . ")\nPrice: " . $wa_price . "\nPlease let me know how to proceed. Thank you!";
}
$wa_url = "https://api.whatsapp.com/send?phone=" . $whatsapp_num . "&text=" . urlencode($wa_msg);

$page_title = lang_val($book, 'title');
$page_desc = strip_tags(lang_val($book, 'description'));
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb-nav">
    <div class="container">
        <ul class="breadcrumb-list">
            <li><a href="<?php echo SITE_URL; ?>/index.php"><?php echo t('home'); ?></a></li>
            <li><i class="fa fa-chevron-right breadcrumb-chevron"></i></li>
            <li>
                <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo e($book['category_slug']); ?>">
                    <?php echo lang_val($book, 'category'); ?>
                </a>
            </li>
            <li><i class="fa fa-chevron-right breadcrumb-chevron"></i></li>
            <li class="active"><?php echo lang_val($book, 'title'); ?></li>
        </ul>
    </div>
</div>

<!-- Main Details Layout -->
<section class="section-padding">
    <div class="container">
        <div class="book-details-grid">
            
            <!-- Left Column: Image Cover & Preview Link -->
            <div class="book-details-image-col">
                <div class="book-details-cover-wrapper">
                    <img src="<?php echo get_book_cover($book); ?>" alt="<?php echo e($book['title_en']); ?> Cover" class="book-details-cover">
                    <?php if ($discount_pct > 0): ?>
                        <span class="discount-badge badge-large"><?php echo sprintf(t('save_percent'), $discount_pct); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($book['inside_preview_pdf'])): ?>
                    <a href="<?php echo SITE_URL; ?>/assets/previews/<?php echo e($book['inside_preview_pdf']); ?>" target="_blank" class="btn btn-outline-maroon btn-full-width margin-top-md">
                        <i class="fa fa-book-open"></i> <?php echo t('inside_preview'); ?>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Right Column: Info & Buy Block -->
            <div class="book-details-info-col">
                <h1 class="book-details-title-en"><?php echo e($book['title_en']); ?></h1>
                <h2 class="book-details-title-ur lang-ur-font text-maroon"><?php echo e($book['title_ur']); ?></h2>
                
                <?php if (!empty($book['subtitle_en'])): ?>
                    <p class="book-subtitle text-muted">
                        <?php echo CURRENT_LANG === 'ur' ? e($book['subtitle_ur']) : e($book['subtitle_en']); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Metadata table -->
                <div class="book-meta-grid margin-top-md">
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('author'); ?>:</span>
                        <span class="meta-value font-bold">
                            <a href="<?php echo SITE_URL; ?>/search.php?q=<?php echo urlencode($book['author_en']); ?>&type=author">
                                <?php echo lang_val($book, 'author'); ?>
                            </a>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('publisher'); ?>:</span>
                        <span class="meta-value">
                            <a href="<?php echo SITE_URL; ?>/search.php?q=<?php echo urlencode($book['publisher_en']); ?>&type=publisher">
                                <?php echo lang_val($book, 'publisher'); ?>
                            </a>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('isbn'); ?>:</span>
                        <span class="meta-value"><?php echo e($book['isbn'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">SKU:</span>
                        <span class="meta-value"><?php echo e($book['sku']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('language'); ?>:</span>
                        <span class="meta-value"><?php echo e(t($book['language']) ?: $book['language']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('binding'); ?>:</span>
                        <span class="meta-value"><?php echo e($book['binding']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('pages'); ?>:</span>
                        <span class="meta-value"><?php echo e($book['pages'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('weight'); ?>:</span>
                        <span class="meta-value"><?php echo $book['weight_grams'] ? $book['weight_grams'] . 'g' : 'N/A'; ?></span>
                    </div>
                    <?php if (!empty($book['loc_classification'])): ?>
                    <div class="meta-item">
                        <span class="meta-label"><?php echo t('loc_classification'); ?>:</span>
                        <span class="meta-value code-font"><?php echo e($book['loc_classification']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <hr class="meta-divider">
                
                <!-- Stock Status & Price Block -->
                <div class="price-stock-row margin-top-md">
                    <div class="price-box">
                        <div class="price-row">
                            <?php if ($discount_pct > 0): ?>
                                <span class="original-price-label text-muted"><?php echo t('original_price'); ?>:</span>
                                <span class="price-original"><?php echo money($original_price); ?></span>
                                <span class="price-current font-bold text-maroon text-2xl"><?php echo money($final_price); ?></span>
                            <?php else: ?>
                                <span class="price-current font-bold text-maroon text-2xl"><?php echo money($original_price); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="pkr-equivalent text-muted margin-top-xs">
                            PKR Equivalent: <span class="font-medium text-dark"><?php echo pkr($discount_pct > 0 ? $final_price : $original_price, $pkr_rate); ?></span>
                        </div>
                    </div>
                    
                    <div class="stock-status-box">
                        <span class="status-label"><?php echo t('stock_status'); ?>:</span>
                        <?php if (is_coming_soon($book)): ?>
                            <span class="badge badge-coming-soon"><i class="fa fa-clock"></i> <?php echo t('coming_soon'); ?></span>
                        <?php elseif ($book['stock_quantity'] > 0): ?>
                            <span class="badge badge-success"><i class="fa fa-circle-check"></i> <?php echo t('in_stock'); ?> (<?php echo $book['stock_quantity']; ?>)</span>
                        <?php else: ?>
                            <span class="badge badge-danger"><i class="fa fa-circle-xmark"></i> <?php echo t('out_of_stock'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Add to Cart & Buy Box -->
                <?php if (is_book_purchasable($book)): ?>
                    <div class="buy-action-box margin-top-lg">
                        <div class="qty-selector">
                            <button type="button" class="qty-btn qty-btn-minus" onclick="decreaseQty()"><i class="fa fa-minus"></i></button>
                            <input type="number" id="detail-qty" value="1" min="1" max="<?php echo $book['stock_quantity']; ?>" class="qty-input">
                            <button type="button" class="qty-btn qty-btn-plus" onclick="increaseQty()"><i class="fa fa-plus"></i></button>
                        </div>
                        
                        <button type="button" class="btn btn-maroon btn-lg add-to-cart-detail-btn" data-id="<?php echo $book['id']; ?>">
                            <i class="fa fa-cart-plus"></i> <?php echo t('add_to_cart'); ?>
                        </button>
                        
                        <a href="<?php echo $wa_url; ?>" target="_blank" class="btn btn-whatsapp btn-lg">
                            <i class="fab fa-whatsapp"></i> <?php echo t('buy_on_whatsapp'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="out-of-stock-cta margin-top-lg">
                        <p class="text-muted"><?php echo is_coming_soon($book) ? t('coming_soon_message') : 'This book is currently out of stock. You can request a copy using our request form.'; ?></p>
                        <a href="<?php echo SITE_URL; ?>/request-book.php?title=<?php echo urlencode($book['title_en']); ?>&isbn=<?php echo urlencode($book['isbn']); ?>" class="btn btn-gold btn-md margin-top-xs">
                            <i class="fa fa-paper-plane"></i> Request this Book
                        </a>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Tabbed Information: Description, Author, Returns -->
        <div class="book-tabs-container margin-top-xxl">
            <div class="tab-triggers">
                <button type="button" class="tab-trigger active" onclick="switchTab(event, 'tab-description')"><?php echo t('description'); ?></button>
                <button type="button" class="tab-trigger" onclick="switchTab(event, 'tab-author')"><?php echo t('author_bio'); ?></button>
                <button type="button" class="tab-trigger" onclick="switchTab(event, 'tab-delivery')"><?php echo t('delivery_returns'); ?></button>
            </div>
            
            <!-- Description Tab -->
            <div id="tab-description" class="tab-content active">
                <div class="bilingual-desc-grid">
                    <?php if (!empty($book['description_en'])): ?>
                        <div class="desc-english">
                            <h4 class="desc-lang-title">English Description</h4>
                            <p class="desc-text text-justify"><?php echo nl2br(e($book['description_en'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($book['description_ur'])): ?>
                        <div class="desc-urdu lang-ur-font" style="line-height: 2.2; font-size: 1.1rem;">
                            <h4 class="desc-lang-title lang-ur-font text-maroon">تفصیل (اردو)</h4>
                            <p class="desc-text text-justify"><?php echo nl2br(e($book['description_ur'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Author Biography Tab -->
            <div id="tab-author" class="tab-content">
                <div class="bilingual-desc-grid">
                    <?php if (!empty($book['bio_en'])): ?>
                        <div class="desc-english">
                            <h4 class="desc-lang-title"><?php echo e($book['author_en']); ?> - Biography</h4>
                            <p class="desc-text"><?php echo nl2br(e($book['bio_en'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($book['bio_ur'])): ?>
                        <div class="desc-urdu lang-ur-font" style="line-height: 2.2; font-size: 1.1rem;">
                            <h4 class="desc-lang-title lang-ur-font text-maroon"><?php echo e($book['author_ur']); ?> - سوانح حیات</h4>
                            <p class="desc-text"><?php echo nl2br(e($book['bio_ur'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Delivery & Returns Tab -->
            <div id="tab-delivery" class="tab-content">
                <h4>Delivery Locations & Rates</h4>
                <p>We hold 100% of our catalogue in our UK warehouse to guarantee fast delivery and exclude hidden shipping duties.</p>
                <ul>
                    <li><strong>UK Mainland Delivery:</strong> Flat £<?php echo get_setting('uk_delivery_charge_gbp', '3.95'); ?> (Royal Mail 2nd Class Tracked). <strong>FREE</strong> for orders above £<?php echo get_setting('free_delivery_threshold_gbp', '30.00'); ?>.</li>
                    <li><strong>International Delivery:</strong> Flat £<?php echo get_setting('international_delivery_charge_gbp', '12.50'); ?> (Worldwide Airmail Tracked). Delivered in 7-14 business days.</li>
                </ul>
                <h4 class="margin-top-md">Returns & Refunds</h4>
                <p>If you are not completely satisfied with your purchase, you may return the item within 14 days of delivery in its original, pristine condition for a full refund or exchange. Return shipping is borne by the customer unless the book arrived damaged or incorrect.</p>
            </div>
        </div>
        
        <!-- Related Books (6 Books) -->
        <?php if (!empty($related_books)): ?>
            <div class="related-books-section margin-top-xxl">
                <div class="section-header">
                    <h3 class="section-title"><i class="fa fa-rectangle-list text-gold"></i> <?php echo t('related_books'); ?></h3>
                    <div class="section-divider"></div>
                </div>
                
                <div class="book-grid margin-top-md">
                    <?php foreach ($related_books as $rel): ?>
                        <?php 
                            $rel_original = (float)$rel['price_gbp'];
                            $rel_discount = (int)$rel['discount_percent'];
                            $rel_final = $rel_original - ($rel_original * ($rel_discount / 100));
                        ?>
                        <article class="book-card">
                            <?php if ($rel_discount > 0): ?>
                                <span class="discount-badge"><?php echo sprintf(t('save_percent'), $rel_discount); ?></span>
                            <?php endif; ?>
                            
                            <div class="book-card-cover-wrapper">
                                <img src="<?php echo get_book_cover($rel); ?>" alt="<?php echo e($rel['title_en']); ?> Cover" class="book-card-cover" loading="lazy">
                                <div class="book-cover-hover-overlay">
                                    <a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($rel['slug']); ?>" class="btn btn-gold btn-sm"><i class="fa fa-eye"></i> View</a>
                                </div>
                            </div>
                            
                            <div class="book-card-info">
                                <h3 class="book-card-title-en"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($rel['slug']); ?>"><?php echo e($rel['title_en']); ?></a></h3>
                                <h4 class="book-card-title-ur lang-ur-font"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($rel['slug']); ?>"><?php echo e($rel['title_ur']); ?></a></h4>
                                <p class="book-card-author"><span class="text-muted"><?php echo t('author'); ?>:</span> <span class="font-medium"><?php echo lang_val($rel, 'author'); ?></span></p>
                                
                                <div class="book-card-price-row">
                                    <div class="price-gbp">
                                        <?php if ($rel_discount > 0): ?>
                                            <span class="price-original"><?php echo money($rel_original); ?></span>
                                            <span class="price-current font-bold text-maroon"><?php echo money($rel_final); ?></span>
                                        <?php else: ?>
                                            <span class="price-current font-bold text-maroon"><?php echo money($rel_original); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="book-card-actions">
                                    <?php echo book_card_action_button($rel); ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<!-- Page Specific JS Helper functions -->
<script>
    function increaseQty() {
        var input = document.getElementById('detail-qty');
        var val = parseInt(input.value);
        var max = parseInt(input.getAttribute('max'));
        if (val < max) {
            input.value = val + 1;
        }
    }
    
    function decreaseQty() {
        var input = document.getElementById('detail-qty');
        var val = parseInt(input.value);
        if (val > 1) {
            input.value = val - 1;
        }
    }
    
    function switchTab(evt, tabId) {
        var tabContents = document.getElementsByClassName('tab-content');
        for (var i = 0; i < tabContents.length; i++) {
            tabContents[i].classList.remove('active');
        }
        
        var tabTriggers = document.getElementsByClassName('tab-trigger');
        for (var i = 0; i < tabTriggers.length; i++) {
            tabTriggers[i].classList.remove('active');
        }
        
        document.getElementById(tabId).classList.add('active');
        evt.currentTarget.classList.add('active');
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
