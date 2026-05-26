<?php
/**
 * Urdu Books World - Order Confirmation Success Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

if (empty($order_number)) {
    redirect(SITE_URL . '/index.php');
}

// Fetch Order Details
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$order_number]);
    $order = $stmt->fetch();
    
    if (!$order) {
        redirect(SITE_URL . '/index.php');
    }
} catch (PDOException $e) {
    error_log("Failed to load order receipt: " . $e->getMessage());
    redirect(SITE_URL . '/index.php');
}

$bank_details = get_setting('bank_account_details', "Sort Code: 20-12-34\nAccount Number: 87654321");
$page_title = t('order_confirmed');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container container-sm">
        <div class="auth-box text-center confirmation-box">
            
            <div class="confirmation-icon-box margin-top-md">
                <i class="fa fa-circle-check text-success text-5xl"></i>
            </div>
            
            <h1 class="auth-box-title margin-top-md <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>">
                <?php echo t('order_confirmed'); ?>
            </h1>
            
            <p class="auth-box-subtitle margin-top-sm <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>">
                <?php echo t('order_success_msg'); ?>
            </p>
            
            <div class="receipt-summary margin-top-lg text-justify">
                <div class="receipt-row">
                    <span class="receipt-label"><?php echo t('order_number'); ?>:</span>
                    <span class="receipt-value font-bold text-maroon"><?php echo e($order['order_number']); ?></span>
                </div>
                <div class="receipt-row margin-top-xs">
                    <span class="receipt-label"><?php echo t('order_date'); ?>:</span>
                    <span class="receipt-value"><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="receipt-row margin-top-xs">
                    <span class="receipt-label">Customer Name:</span>
                    <span class="receipt-value font-medium"><?php echo e($order['customer_name']); ?></span>
                </div>
                <div class="receipt-row margin-top-xs">
                    <span class="receipt-label"><?php echo t('payment_method'); ?>:</span>
                    <span class="receipt-value font-medium">
                        <?php 
                            if ($order['payment_method'] === 'bank_transfer') {
                                echo t('bank_transfer');
                            } elseif ($order['payment_method'] === 'cod') {
                                echo t('cod');
                            } else {
                                echo $order['payment_method'];
                            }
                        ?>
                    </span>
                </div>
                <hr class="meta-divider margin-top-sm">
                <div class="receipt-row total-receipt-row margin-top-sm">
                    <span class="receipt-total-label"><?php echo t('total'); ?>:</span>
                    <span class="receipt-total-value font-bold text-maroon text-lg"><?php echo money($order['total_gbp']); ?></span>
                </div>
                <div class="receipt-row-pkr text-muted text-right">
                    (PKR Equivalent: <?php echo pkr($order['total_gbp']); ?>)
                </div>
            </div>
            
            <!-- Bank Transfer Routing Instructions -->
            <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                <div class="bank-details-box margin-top-lg text-justify bg-light padding-md border-radius-sm">
                    <h3 class="bank-box-title text-maroon font-bold"><i class="fa fa-university"></i> <?php echo t('bank_details'); ?></h3>
                    <p class="margin-top-xs text-muted">Please transfer the grand total via your mobile banking application using the following routing details:</p>
                    <div class="bank-raw-text code-font margin-top-sm">
                        <?php echo nl2br(e($bank_details)); ?>
                    </div>
                    <div class="bank-reference-alert alert alert-warning margin-top-md">
                        <strong>IMPORTANT:</strong> Please use the Order Number <strong><?php echo e($order['order_number']); ?></strong> as your payment reference.
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="margin-top-xl flex-center-group">
                <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-maroon"><?php echo t('home'); ?> Page</a>
                <?php if (is_logged_in()): ?>
                    <a href="<?php echo SITE_URL; ?>/account.php" class="btn btn-outline-maroon"><?php echo t('my_account'); ?></a>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
