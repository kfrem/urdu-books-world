<?php
/**
 * Urdu Books World - Customer Checkout Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

// Verify cart is not empty
$cart_items = get_cart_items();
if (empty($cart_items)) {
    redirect(SITE_URL . '/cart.php');
}

$user = current_user();
$error_msg = '';

// Load defaults from shipping session destination
$destination = isset($_SESSION['shipping_destination']) ? $_SESSION['shipping_destination'] : 'uk';
$free_threshold = (float)get_setting('free_delivery_threshold_gbp', 30.00);
$uk_charge = (float)get_setting('uk_delivery_charge_gbp', 3.95);
$intl_charge = (float)get_setting('international_delivery_charge_gbp', 12.50);
$pkr_rate = (float)get_setting('pkr_to_gbp_rate', 360);

// Calculate totals
$subtotal = 0.00;
foreach ($cart_items as $item) {
    $subtotal += $item['line_total'];
}

$delivery = 0.00;
if ($destination === 'international') {
    $delivery = $intl_charge;
} else {
    $delivery = ($subtotal >= $free_threshold) ? 0.00 : $uk_charge;
}

$total = $subtotal + $delivery;

// Order Placement Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    verify_csrf_token();
    
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address1 = trim($_POST['address1']);
    $address2 = trim($_POST['address2']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $country = trim($_POST['country'] ?: 'United Kingdom');
    $payment_method = trim($_POST['payment_method'] ?: 'bank_transfer');
    $notes = trim($_POST['notes']);
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address1) || empty($city) || empty($postcode)) {
        $error_msg = 'Please fill in all required shipping and contact details.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } elseif ($payment_method === 'cod' && $country !== 'United Kingdom') {
        $error_msg = 'Cash on Delivery is only available for orders shipping to the United Kingdom.';
    } else {
        
        // Double check stock quantity before transactions
        $stock_check = true;
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                $error_msg = sprintf('Sorry, only %d copies of "%s" are available. Please adjust your cart.', $item['stock_quantity'], $item['title_en']);
                $stock_check = false;
                break;
            }
        }
        
        if ($stock_check) {
            // Recalculate delivery charge dynamically based on country post inputs
            $ship_intl = ($country !== 'United Kingdom');
            if ($ship_intl) {
                $delivery = $intl_charge;
            } else {
                $delivery = ($subtotal >= $free_threshold) ? 0.00 : $uk_charge;
            }
            $total = $subtotal + $delivery;
            
            // Database Transaction
            try {
                $pdo->beginTransaction();
                
                // Generate Unique Order Number
                $order_number = 'UBW-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                
                // Prepare Address Block
                $address_block = $address1;
                if (!empty($address2)) {
                    $address_block .= "\n" . $address2;
                }
                
                $customer_full_name = $first_name . ' ' . $last_name;
                $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
                
                // Insert into orders table
                $stmt = $pdo->prepare("INSERT INTO orders 
                    (order_number, user_id, customer_email, customer_phone, customer_name, delivery_address, city, postcode, country, subtotal_gbp, delivery_charge_gbp, total_gbp, payment_method, status, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                $stmt->execute([
                    $order_number,
                    $user_id,
                    $email,
                    $phone,
                    $customer_full_name,
                    $address_block,
                    $city,
                    $postcode,
                    $country,
                    $subtotal,
                    $delivery,
                    $total,
                    $payment_method,
                    $notes
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Insert items & decrement stocks
                foreach ($cart_items as $item) {
                    $stmt_item = $pdo->prepare("INSERT INTO order_items 
                        (order_id, book_id, quantity, unit_price_gbp, discount_percent, line_total_gbp) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_item->execute([
                        $order_id,
                        $item['id'],
                        $item['quantity'],
                        $item['discounted_price'],
                        $item['discount_percent'],
                        $item['line_total']
                    ]);
                    
                    // Decrement stock
                    $stmt_stock = $pdo->prepare("UPDATE books SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
                    $stmt_stock->execute([$item['quantity'], $item['id'], $item['quantity']]);
                    if ($stmt_stock->rowCount() !== 1) {
                        throw new PDOException('Insufficient stock for book ID ' . $item['id']);
                    }
                }
                
                $pdo->commit();
                
                // Clear the Cart
                clear_cart();
                
                // Send Mock Confirmation Email
                $mail_to = $email;
                $mail_subject = "Urdu Books World - Order Confirmed: " . $order_number;
                $mail_body = "Hello " . $customer_full_name . ",\n\nYour order has been received!\n\nOrder Number: " . $order_number . "\nTotal: " . money($total) . "\n\nPlease complete bank transfer payment to confirm dispatch.\n\nThank you for shopping with us!\nUrdu Books World Team";
                $reply_to = get_setting('contact_email', 'info@urdubooksworld.co.uk');
                $mail_headers = "From: no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\nReply-To: " . $reply_to;
                @mail($mail_to, $mail_subject, $mail_body, $mail_headers);
                
                // Redirect to Success page
                redirect(SITE_URL . '/order-confirmation.php?order_number=' . $order_number);
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Order transaction failed: " . $e->getMessage());
                $error_msg = 'An error occurred while creating your order. Please try again.';
            }
        }
    }
}

$page_title = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container">
        
        <div class="section-header">
            <h1 class="section-title"><i class="fa fa-credit-card text-gold"></i> Checkout</h1>
            <div class="section-divider"></div>
        </div>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger margin-top-md">
                <i class="fa fa-circle-exmark"></i> <?php echo e($error_msg); ?>
            </div>
        <?php endif; ?>
        
        <div class="cart-layout margin-top-lg">
            <!-- Billing Details Form -->
            <div class="cart-table-col">
                <form action="<?php echo SITE_URL; ?>/checkout.php" method="POST" class="auth-form checkout-form">
                    <?php echo csrf_field(); ?>
                    
                    <h2 class="auth-box-section-title"><?php echo t('billing_details'); ?></h2>
                    
                    <div class="form-row-2-cols margin-top-md">
                        <div class="form-group">
                            <label for="first_name" class="form-label"><?php echo t('first_name'); ?> <span class="text-maroon">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required value="<?php echo $user ? e($user['first_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label"><?php echo t('last_name'); ?> <span class="text-maroon">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required value="<?php echo $user ? e($user['last_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row-2-cols margin-top-md">
                        <div class="form-group">
                            <label for="email" class="form-label"><?php echo t('email'); ?> <span class="text-maroon">*</span></label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo $user ? e($user['email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label"><?php echo t('phone'); ?> <span class="text-maroon">*</span></label>
                            <input type="text" id="phone" name="phone" class="form-control" required value="<?php echo $user ? e($user['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group margin-top-md">
                        <label for="address1" class="form-label"><?php echo t('address_line1'); ?> <span class="text-maroon">*</span></label>
                        <input type="text" id="address1" name="address1" class="form-control" placeholder="House number and street name" required value="<?php echo $user ? e($user['address_line1']) : ''; ?>">
                    </div>
                    <div class="form-group margin-top-md">
                        <label for="address2" class="form-label"><?php echo t('address_line2'); ?></label>
                        <input type="text" id="address2" name="address2" class="form-control" placeholder="Apartment, suite, unit etc. (optional)" value="<?php echo $user ? e($user['address_line2']) : ''; ?>">
                    </div>
                    
                    <div class="form-row-3-cols margin-top-md">
                        <div class="form-group">
                            <label for="city" class="form-label"><?php echo t('city'); ?> <span class="text-maroon">*</span></label>
                            <input type="text" id="city" name="city" class="form-control" required value="<?php echo $user ? e($user['city']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="postcode" class="form-label"><?php echo t('postcode'); ?> <span class="text-maroon">*</span></label>
                            <input type="text" id="postcode" name="postcode" class="form-control" required value="<?php echo $user ? e($user['postcode']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="country" class="form-label"><?php echo t('country'); ?> <span class="text-maroon">*</span></label>
                            <select id="country" name="country" class="form-control" onchange="adjustDestination(this.value)">
                                <option value="United Kingdom" <?php echo ($user && $user['country'] === 'United Kingdom') || (!$user && $destination === 'uk') ? 'selected' : ''; ?>>United Kingdom</option>
                                <option value="Pakistan" <?php echo ($user && $user['country'] === 'Pakistan') ? 'selected' : ''; ?>>Pakistan</option>
                                <option value="Ireland" <?php echo ($user && $user['country'] === 'Ireland') || (!$user && $destination === 'international') ? 'selected' : ''; ?>>Ireland</option>
                                <option value="Germany" <?php echo ($user && $user['country'] === 'Germany') ? 'selected' : ''; ?>>Germany</option>
                                <option value="France" <?php echo ($user && $user['country'] === 'France') ? 'selected' : ''; ?>>France</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group margin-top-md">
                        <label for="notes" class="form-label"><?php echo t('notes'); ?></label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Notes about your delivery or procurement instructions..."></textarea>
                    </div>
                    
                    <!-- Payment Choice -->
                    <h2 class="auth-box-section-title margin-top-lg"><?php echo t('payment_method'); ?></h2>
                    
                    <div class="payment-method-box margin-top-md">
                        <label class="payment-method-option active">
                            <input type="radio" name="payment_method" value="bank_transfer" checked>
                            <span class="payment-option-details">
                                <span class="payment-option-title font-bold"><i class="fa fa-university text-maroon"></i> <?php echo t('bank_transfer'); ?></span>
                                <span class="payment-option-desc text-muted">Complete payment directly to our bank details. Orders ship once transfer clears.</span>
                            </span>
                        </label>
                        
                        <label class="payment-method-option margin-top-xs" id="cod-payment-option" style="<?php echo ($destination === 'international') ? 'display:none;' : ''; ?>">
                            <input type="radio" name="payment_method" value="cod">
                            <span class="payment-option-details">
                                <span class="payment-option-title font-bold"><i class="fa fa-truck text-maroon"></i> <?php echo t('cod'); ?></span>
                                <span class="payment-option-desc text-muted">Pay with cash upon Royal Mail home delivery. Available in UK only.</span>
                            </span>
                        </label>
                        
                        <label class="payment-method-option option-disabled margin-top-xs">
                            <input type="radio" name="payment_method" value="card_pending" disabled>
                            <span class="payment-option-details">
                                <span class="payment-option-title font-bold text-muted"><i class="fab fa-cc-stripe"></i> Card Payment (Stripe - Coming Soon)</span>
                                <span class="payment-option-desc text-muted">Direct credit / debit card processing is being integrated and will launch soon.</span>
                            </span>
                        </label>
                    </div>
                    
                    <div class="margin-top-xl">
                        <button type="submit" name="place_order" class="btn btn-maroon btn-full-width btn-lg">
                            <i class="fa fa-check-double"></i> <?php echo t('place_order'); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary Column -->
            <div class="cart-summary-col">
                <div class="cart-summary-box checkout-summary-box">
                    <h2 class="summary-box-title"><?php echo t('order_summary'); ?></h2>
                    
                    <!-- Items scroll -->
                    <div class="summary-items-list margin-top-md">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item-row">
                                <span class="summary-item-name">
                                    <?php echo e($item['title_en']); ?> <strong class="text-maroon">x<?php echo $item['quantity']; ?></strong>
                                </span>
                                <span class="summary-item-cost font-medium">
                                    <?php echo money($item['line_total']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr class="meta-divider margin-top-md">
                    
                    <div class="summary-row margin-top-md">
                        <span class="summary-label"><?php echo t('subtotal'); ?>:</span>
                        <span class="summary-value"><?php echo money($subtotal); ?></span>
                    </div>
                    
                    <div class="summary-row margin-top-sm">
                        <span class="summary-label"><?php echo t('delivery_charge'); ?>:</span>
                        <span class="summary-value" id="summary-delivery-charge">
                            <?php echo $delivery > 0 ? money($delivery) : t('free_delivery'); ?>
                        </span>
                    </div>
                    
                    <hr class="meta-divider margin-top-md">
                    
                    <div class="summary-row summary-total-row margin-top-md">
                        <span class="summary-total-label"><?php echo t('total'); ?>:</span>
                        <span class="summary-total-value text-maroon font-bold text-xl" id="summary-total-cost"><?php echo money($total); ?></span>
                    </div>
                    <div class="summary-row-pkr text-muted">
                        (PKR Equivalent: <span id="summary-pkr-total-cost"><?php echo pkr($total); ?></span>)
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<script>
    // Dynamically adjust shipping fees based on selected country
    var ukCharge = <?php echo $uk_charge; ?>;
    var intlCharge = <?php echo $intl_charge; ?>;
    var subtotal = <?php echo $subtotal; ?>;
    var freeThreshold = <?php echo $free_threshold; ?>;
    var exchangeRate = <?php echo $pkr_rate; ?>;

    function adjustDestination(country) {
        var isIntl = (country !== 'United Kingdom');
        var delCharge = 0.00;
        
        if (isIntl) {
            delCharge = intlCharge;
            $('#cod-payment-option').hide();
            // select bank transfer if COD was checked
            if ($('input[name="payment_method"]:checked').val() === 'cod') {
                $('input[name="payment_method"][value="bank_transfer"]').prop('checked', true);
            }
        } else {
            delCharge = (subtotal >= freeThreshold) ? 0.00 : ukCharge;
            $('#cod-payment-option').show();
        }
        
        var total = subtotal + delCharge;
        
        // Update labels
        var formattedDel = delCharge > 0 ? '£' + delCharge.toFixed(2) : 'Free Delivery';
        $('#summary-delivery-charge').text(formattedDel);
        $('#summary-total-cost').text('£' + total.toFixed(2));
        
        var pkrTotal = Math.round(total * exchangeRate);
        $('#summary-pkr-total-cost').text('Rs. ' + pkrTotal.toLocaleString());
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
