<?php
/**
 * Urdu Books World - Public Layout Header
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/cart.php';

// Fetch settings for header info
$phone_uk = get_setting('contact_phone_uk', '+44 7123 456789');
$whatsapp = get_setting('whatsapp_number', '447123456789');
$site_name = get_setting('site_name', 'Urdu Books World');

// Fetch top categories for the horizontal category bar
try {
    $cat_stmt = $pdo->prepare("
        SELECT c.*, COUNT(b.id) AS book_count 
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id AND b.is_active = 1
        WHERE c.is_active = 1 AND c.parent_id IS NULL
        GROUP BY c.id 
        ORDER BY c.sort_order ASC, book_count DESC 
        LIMIT 15
    ");
    $cat_stmt->execute();
    $header_categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $header_categories = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo CURRENT_LANG; ?>" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' . e($site_name) : e($site_name); ?></title>
    
    <!-- Meta Descriptions for SEO -->
    <meta name="description" content="<?php echo isset($page_desc) ? e($page_desc) : 'Urdu Books World - Premier bilingual online bookstore selling Urdu and Pakistani literature in the UK diaspora. Fast shipping, library cataloguing, and rich collection.'; ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <?php if (is_rtl()): ?>
        <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style-rtl.css">
    <?php endif; ?>
</head>
<body>

    <!-- 1. Top Bar -->
    <div class="top-bar">
        <div class="container top-bar-content">
            <div class="top-contacts">
                <a href="https://wa.me/<?php echo e($whatsapp); ?>" target="_blank" class="top-link">
                    <i class="fab fa-whatsapp text-gold"></i> <span>WhatsApp: +<?php echo e($whatsapp); ?></span>
                </a>
                <a href="tel:<?php echo e($phone_uk); ?>" class="top-link hidden-mobile">
                    <i class="fa fa-phone text-gold"></i> <span>UK: <?php echo e($phone_uk); ?></span>
                </a>
            </div>
            <div class="top-menu">
                <div class="language-toggle">
                    <?php if (CURRENT_LANG === 'ur'): ?>
                        <a href="?lang=en" class="lang-link">English</a>
                    <?php else: ?>
                        <a href="?lang=ur" class="lang-link lang-ur-font">اردو</a>
                    <?php endif; ?>
                </div>
                
                <span class="top-divider">|</span>
                
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo SITE_URL; ?>/account.php" class="top-link">
                        <i class="fa fa-user"></i> <span><?php echo sprintf(t('hello'), e($_SESSION['user_name'])); ?></span>
                    </a>
                    <span class="top-divider">|</span>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/index.php" class="top-link">
                            <i class="fa fa-gauge-high"></i> <span><?php echo t('admin_panel'); ?></span>
                        </a>
                        <span class="top-divider">|</span>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="top-link">
                        <i class="fa fa-sign-out-alt"></i> <span><?php echo t('logout'); ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="top-link">
                        <i class="fa fa-lock"></i> <span><?php echo t('login_register'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 2. Sticky Brand Header -->
    <header class="main-header">
        <div class="container header-grid">
            <!-- Brand Logo -->
            <a href="<?php echo SITE_URL; ?>/index.php" class="logo-box">
                <?php if (is_rtl()): ?>
                    <div class="logo-text lang-ur-font text-maroon font-bold">اردو بکس ورلڈ</div>
                <?php else: ?>
                    <div class="logo-text text-maroon font-bold">URDU <span class="text-gold">BOOKS</span> WORLD</div>
                <?php endif; ?>
            </a>
            
            <!-- Global Search Box -->
            <form action="<?php echo SITE_URL; ?>/search.php" method="GET" class="search-form">
                <div class="search-input-group">
                    <select name="type" class="search-type-select">
                        <option value="all"><?php echo t('search_all'); ?></option>
                        <option value="title" <?php echo isset($_GET['type']) && $_GET['type'] === 'title' ? 'selected' : ''; ?>><?php echo t('search_title'); ?></option>
                        <option value="author" <?php echo isset($_GET['type']) && $_GET['type'] === 'author' ? 'selected' : ''; ?>><?php echo t('search_author'); ?></option>
                        <option value="publisher" <?php echo isset($_GET['type']) && $_GET['type'] === 'publisher' ? 'selected' : ''; ?>><?php echo t('search_publisher'); ?></option>
                        <option value="subject" <?php echo isset($_GET['type']) && $_GET['type'] === 'subject' ? 'selected' : ''; ?>><?php echo t('search_subject'); ?></option>
                        <option value="isbn" <?php echo isset($_GET['type']) && $_GET['type'] === 'isbn' ? 'selected' : ''; ?>><?php echo t('search_isbn'); ?></option>
                    </select>
                    <input type="text" name="q" placeholder="<?php echo t('search_placeholder'); ?>" class="search-input" value="<?php echo isset($_GET['q']) ? e($_GET['q']) : ''; ?>" required>
                    <button type="submit" class="search-submit-btn">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- Cart & Icons -->
            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>/cart.php" class="cart-trigger">
                    <span class="cart-icon-wrapper">
                        <i class="fa fa-shopping-bag text-maroon"></i>
                        <span class="cart-counter" id="cart-counter-header"><?php echo get_cart_count(); ?></span>
                    </span>
                    <span class="cart-label hidden-tablet font-medium"><?php echo t('cart'); ?></span>
                </a>
                
                <button type="button" class="mobile-menu-trigger" aria-label="Toggle Menu">
                    <i class="fa fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- 3. Primary Navigation Menu -->
    <nav class="nav-bar">
        <div class="container nav-content">
            <ul class="nav-list">
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/index.php" class="nav-link"><i class="fa fa-home"></i> <?php echo t('home'); ?></a></li>
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/category.php" class="nav-link"><?php echo t('publications'); ?></a></li>
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/new-arrivals.php" class="nav-link"><?php echo t('new_arrivals'); ?></a></li>
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/catalogue.php" class="nav-link"><?php echo t('catalogue'); ?></a></li>
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/how-to-pay.php" class="nav-link"><?php echo t('how_to_pay'); ?></a></li>
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/request-book.php" class="nav-link"><?php echo t('request_book'); ?></a></li>
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link"><?php echo t('contact'); ?></a></li>
                <li class="nav-item has-dropdown">
                    <a href="<?php echo SITE_URL; ?>/discounts.php" class="nav-link">
                        <?php echo t('discounts'); ?> <i class="fa fa-chevron-down nav-chevron"></i>
                    </a>
                    <ul class="nav-dropdown">
                        <li><a href="<?php echo SITE_URL; ?>/discounts.php?percent=10">10% <?php echo t('discounts'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/discounts.php?percent=20">20% <?php echo t('discounts'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/discounts.php?percent=30">30% <?php echo t('discounts'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/discounts.php?percent=40">40% <?php echo t('discounts'); ?></a></li>
                        <li><a href="<?php echo SITE_URL; ?>/discounts.php?percent=50">50% <?php echo t('discounts'); ?></a></li>
                    </ul>
                </li>
                <li class="nav-item"><a href="<?php echo SITE_URL; ?>/delivery-charges.php" class="nav-link"><?php echo t('delivery_charges'); ?></a></li>
            </ul>
        </div>
    </nav>

    <!-- 4. Category Horizontal Strip -->
    <?php if (!empty($header_categories)): ?>
    <div class="category-strip">
        <div class="container category-strip-wrapper">
            <span class="strip-title"><i class="fa fa-tags text-maroon"></i> <?php echo t('top_categories'); ?>:</span>
            <div class="strip-items">
                <?php foreach ($header_categories as $h_cat): ?>
                    <a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo e($h_cat['slug']); ?>" class="strip-item">
                        <?php echo lang_val($h_cat, 'name'); ?> 
                        <span class="count-badge">(<?php echo $h_cat['book_count']; ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 5. Language Filter Strip -->
    <div class="language-filter-strip">
        <div class="container lang-strip-wrapper">
            <span class="lang-strip-title"><i class="fa fa-language text-gold"></i> <?php echo t('filter_language'); ?>:</span>
            <div class="lang-strip-items">
                <a href="<?php echo SITE_URL; ?>/category.php?lang_filter=all" class="lang-strip-item <?php echo (!isset($_GET['lang_filter']) || $_GET['lang_filter'] === 'all') ? 'active' : ''; ?>">
                    <?php echo t('all_languages'); ?>
                </a>
                <a href="<?php echo SITE_URL; ?>/category.php?lang_filter=Urdu" class="lang-strip-item <?php echo (isset($_GET['lang_filter']) && $_GET['lang_filter'] === 'Urdu') ? 'active' : ''; ?>">
                    اردو (Urdu)
                </a>
                <a href="<?php echo SITE_URL; ?>/category.php?lang_filter=English" class="lang-strip-item <?php echo (isset($_GET['lang_filter']) && $_GET['lang_filter'] === 'English') ? 'active' : ''; ?>">
                    English
                </a>
                <a href="<?php echo SITE_URL; ?>/category.php?lang_filter=Arabic" class="lang-strip-item <?php echo (isset($_GET['lang_filter']) && $_GET['lang_filter'] === 'Arabic') ? 'active' : ''; ?>">
                    العربية (Arabic)
                </a>
                <a href="<?php echo SITE_URL; ?>/category.php?lang_filter=Punjabi" class="lang-strip-item <?php echo (isset($_GET['lang_filter']) && $_GET['lang_filter'] === 'Punjabi') ? 'active' : ''; ?>">
                    پنجابی (Punjabi)
                </a>
                <a href="<?php echo SITE_URL; ?>/category.php?lang_filter=Persian" class="lang-strip-item <?php echo (isset($_GET['lang_filter']) && $_GET['lang_filter'] === 'Persian') ? 'active' : ''; ?>">
                    فارسی (Persian)
                </a>
            </div>
        </div>
    </div>
