<?php
/**
 * Urdu Books World - Shopping Cart Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

// Handle Cart Actions (AJAX or Standard POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_token();
    
    $action = $_POST['action'];
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    
    $result = ['status' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'add':
            $result = add_to_cart($book_id, $qty);
            break;
        case 'update':
            $result = update_cart_qty($book_id, $qty);
            break;
        case 'remove':
            $result = remove_from_cart($book_id);
            break;
    }
    
    // Check if AJAX request
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        
        if ($result['status']) {
            $cart_count = get_cart_count();
            $items = get_cart_items();
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['line_total'];
            }
            
            // Format new values
            $free_threshold = (float)get_setting('free_delivery_threshold_gbp', 30.00);
            $delivery_charge = (float)get_setting('uk_delivery_charge_gbp', 3.95);
            
            $delivery = ($subtotal >= $free_threshold) ? 0.00 : $delivery_charge;
            $total = $subtotal + $delivery;
            
            echo json_encode([
                'success' => true,
                'cart_count' => $cart_count,
                'subtotal' => money($subtotal),
                'pkr_subtotal' => pkr($subtotal),
                'delivery' => $delivery > 0 ? money($delivery) : t('free_delivery'),
                'total' => money($total),
                'pkr_total' => pkr($total),
                'free_notice' => ($subtotal < $free_threshold) ? sprintf(t('free_delivery_notice'), money($free_threshold - $subtotal)) : ''
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => isset($result['message']) ? $result['message'] : 'Action failed']);
        }
        exit;
    } else {
        // Standard redirect
        redirect(SITE_URL . '/cart.php');
    }
}

// Get shipping destination (defaults to UK)
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : 'uk';
$_SESSION['shipping_destination'] = $destination;

// Load Cart Details
$cart_items = get_cart_items();
$subtotal = 0.00;
foreach ($cart_items as $item) {
    $subtotal += $item['line_total'];
}

// Settings
$free_threshold = (float)get_setting('free_delivery_threshold_gbp', 30.00);
$uk_charge = (float)get_setting('uk_delivery_charge_gbp', 3.95);
$intl_charge = (float)get_setting('international_delivery_charge_gbp', 12.50);

// Delivery fee calculation
if ($destination === 'international') {
    $delivery = $intl_charge;
} else {
    // UK
    $delivery = ($subtotal >= $free_threshold || $subtotal == 0) ? 0.00 : $uk_charge;
}

$total = $subtotal + $delivery;

$page_title = t('shopping_cart');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container">
        
        <div class="section-header">
            <h1 class="section-title"><i class="fa fa-shopping-bag text-gold"></i> <?php echo t('shopping_cart'); ?></h1>
            <div class="section-divider"></div>
        </div>
        
        <?php if (!empty($cart_items)): ?>
            <div class="cart-layout">
                
                <!-- Left: Table of Items -->
                <div class="cart-table-col">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th colspan="2"><?php echo t('item'); ?></th>
                                <th class="text-center"><?php echo t('price'); ?></th>
                                <th class="text-center"><?php echo t('qty'); ?></th>
                                <th class="text-right"><?php echo t('total'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                                <tr class="cart-row" data-id="<?php echo $item['id']; ?>">
                                    <!-- Image Cover -->
                                    <td class="cart-col-image">
                                        <img src="<?php echo get_book_cover($item); ?>" alt="<?php echo e($item['title_en']); ?> Cover" class="cart-item-image">
                                    </td>
                                    
                                    <!-- Details -->
                                    <td class="cart-col-details">
                                        <h3 class="cart-item-title-en"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($item['slug']); ?>"><?php echo e($item['title_en']); ?></a></h3>
                                        <h4 class="cart-item-title-ur lang-ur-font"><a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($item['slug']); ?>"><?php echo e($item['title_ur']); ?></a></h4>
                                        <p class="cart-item-author text-muted"><?php echo lang_val($item, 'author'); ?></p>
                                    </td>
                                    
                                    <!-- Price -->
                                    <td class="cart-col-price text-center">
                                        <span class="cart-price"><?php echo money($item['discounted_price']); ?></span>
                                    </td>
                                    
                                    <!-- Quantity Adjustment -->
                                    <td class="cart-col-qty text-center">
                                        <div class="qty-selector cart-qty-selector">
                                            <button type="button" class="qty-btn cart-qty-btn-minus" onclick="updateQty(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)"><i class="fa fa-minus"></i></button>
                                            <input type="number" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="qty-input cart-qty-input" onchange="updateQty(<?php echo $item['id']; ?>, this.value)">
                                            <button type="button" class="qty-btn cart-qty-btn-plus" onclick="updateQty(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </td>
                                    
                                    <!-- Line Total -->
                                    <td class="cart-col-total text-right font-bold text-maroon">
                                        <span class="line-total"><?php echo money($item['line_total']); ?></span>
                                    </td>
                                    
                                    <!-- Delete Item -->
                                    <td class="cart-col-action text-center">
                                        <button type="button" class="cart-remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)" aria-label="Remove Item">
                                            <i class="fa fa-trash-can"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-table-footer margin-top-md">
                        <a href="<?php echo SITE_URL; ?>/category.php" class="btn btn-outline-maroon">
                            <i class="fa fa-chevron-left"></i> <?php echo t('continue_shopping'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Right: Summary Box -->
                <div class="cart-summary-col">
                    <div class="cart-summary-box">
                        <h2 class="summary-box-title"><?php echo t('order_summary'); ?></h2>
                        
                        <!-- Shipping Selector -->
                        <div class="summary-shipping-selector margin-top-md">
                            <span class="shipping-selector-label">Shipping Country:</span>
                            <div class="shipping-radios margin-top-xs">
                                <label class="shipping-radio-label">
                                    <input type="radio" name="dest_select" value="uk" <?php echo $destination === 'uk' ? 'checked' : ''; ?> onchange="changeDestination('uk')">
                                    <span>United Kingdom</span>
                                </label>
                                <label class="shipping-radio-label">
                                    <input type="radio" name="dest_select" value="international" <?php echo $destination === 'international' ? 'checked' : ''; ?> onchange="changeDestination('international')">
                                    <span>International</span>
                                </label>
                            </div>
                        </div>
                        
                        <hr class="meta-divider margin-top-md">
                        
                        <div class="summary-row margin-top-md">
                            <span class="summary-label"><?php echo t('subtotal'); ?>:</span>
                            <span class="summary-value" id="summary-subtotal"><?php echo money($subtotal); ?></span>
                        </div>
                        <div class="summary-row-pkr text-muted">
                            (PKR Equivalent: <span id="summary-pkr-subtotal"><?php echo pkr($subtotal); ?></span>)
                        </div>
                        
                        <div class="summary-row margin-top-sm">
                            <span class="summary-label"><?php echo t('delivery_charge'); ?>:</span>
                            <span class="summary-value" id="summary-delivery">
                                <?php echo $delivery > 0 ? money($delivery) : t('free_delivery'); ?>
                            </span>
                        </div>
                        
                        <!-- Shipping threshold prompt -->
                        <?php if ($destination === 'uk'): ?>
                            <div class="free-shipping-notice margin-top-sm" id="free-delivery-notice" style="<?php echo ($subtotal >= $free_threshold) ? 'display:none;' : ''; ?>">
                                <i class="fa fa-info-circle text-maroon"></i> 
                                <span class="notice-text">
                                    <?php echo sprintf(t('free_delivery_notice'), money($free_threshold - $subtotal)); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <hr class="meta-divider margin-top-md">
                        
                        <div class="summary-row summary-total-row margin-top-md">
                            <span class="summary-total-label"><?php echo t('total'); ?>:</span>
                            <span class="summary-total-value text-maroon font-bold text-xl" id="summary-total"><?php echo money($total); ?></span>
                        </div>
                        <div class="summary-row-pkr text-muted">
                            (PKR Equivalent: <span id="summary-pkr-total"><?php echo pkr($total); ?></span>)
                        </div>
                        
                        <div class="margin-top-lg">
                            <a href="<?php echo SITE_URL; ?>/checkout.php" class="btn btn-maroon btn-full-width btn-lg">
                                <?php echo t('checkout'); ?> <i class="fa fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
            </div>
        <?php else: ?>
            <div class="empty-cart-box text-center margin-top-xl">
                <i class="fa fa-shopping-bag text-muted text-5xl"></i>
                <h2 class="margin-top-md"><?php echo t('cart_empty'); ?></h2>
                <p class="text-muted">Browse our catalogs to discover new literary releases.</p>
                <a href="<?php echo SITE_URL; ?>/category.php" class="btn btn-maroon btn-md margin-top-md">
                    Explore Catalog
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<!-- Standard Form POST backup for JS-disabled browsers -->
<form id="cart-action-form" action="<?php echo SITE_URL; ?>/cart.php" method="POST" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="action" id="action-field">
    <input type="hidden" name="book_id" id="book-id-field">
    <input type="hidden" name="qty" id="qty-field">
</form>

<script>
    function changeDestination(dest) {
        var url = new URL(window.location.href);
        url.searchParams.set('destination', dest);
        window.location.href = url.toString();
    }

    function updateQty(bookId, qty) {
        // First try via AJAX
        $.ajax({
            url: window.siteUrl + '/cart.php',
            method: 'POST',
            data: {
                action: 'update',
                book_id: bookId,
                qty: qty,
                csrf_token: window.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    // Update header cart count
                    $('#cart-counter-header').text(response.success ? response.cart_count : '0');
                    // Reload page to reflect full calculations easily
                    window.location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                // Fallback to standard form post
                $('#action-field').val('update');
                $('#book-id-field').val(bookId);
                $('#qty-field').val(qty);
                $('#cart-action-form').submit();
            }
        });
    }

    function removeItem(bookId) {
        $.ajax({
            url: window.siteUrl + '/cart.php',
            method: 'POST',
            data: {
                action: 'remove',
                book_id: bookId,
                csrf_token: window.csrfToken
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                $('#action-field').val('remove');
                $('#book-id-field').val(bookId);
                $('#cart-action-form').submit();
            }
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
