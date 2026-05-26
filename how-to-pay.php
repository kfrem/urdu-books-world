<?php
/**
 * Urdu Books World - How to Pay
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$bank_details = get_setting('bank_account_details', "Barclays Bank UK\nAccount Name: KAFS LTD t/a Urdu Books World\nSort Code: 20-12-34\nAccount Number: 87654321");
$page_title = t('how_to_pay');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container container-sm">
        <div class="auth-box text-justify">
            <h1 class="auth-box-title text-center <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>"><?php echo t('how_to_pay'); ?></h1>
            <div class="section-divider"></div>
            
            <p class="margin-top-md">To ensure maximum security and support non-technical readers, we provide reliable, low-risk payment options including direct Bank Transfers and Cash on Delivery (COD).</p>
            
            <h3 class="auth-box-section-title margin-top-lg"><i class="fa fa-university text-maroon"></i> 1. Direct Bank Transfer (UK BACS)</h3>
            <p class="margin-top-xs">This is our recommended, fastest payment method. Transfer the order total directly from your mobile banking application or desktop account using BACS:</p>
            <div class="bank-details-box margin-top-sm bg-light padding-md border-radius-sm code-font">
                <?php echo nl2br(e($bank_details)); ?>
            </div>
            <div class="alert alert-warning margin-top-md">
                <strong>IMPORTANT:</strong> Always reference your unique <strong>Order Number</strong> (e.g. UBW-20251120-A3) in the reference field of your BACS transfer to allow instant automated verification.
            </div>
            
            <h3 class="auth-box-section-title margin-top-lg"><i class="fa fa-truck text-maroon"></i> 2. Cash on Delivery (COD)</h3>
            <p class="margin-top-xs">Available for shipping addresses located within the <strong>United Kingdom</strong> only. When completing checkout, choose the Cash on Delivery option. Pay Royal Mail or our courier in cash at your doorstep when the book packet is handed to you.</p>
            
            <h3 class="auth-box-section-title margin-top-lg"><i class="fab fa-cc-stripe text-maroon"></i> 3. Credit / Debit Card Processing</h3>
            <p class="margin-top-xs">We are currently integrating a secure Stripe payment processing module to enable direct visa, mastercard, and apple pay checkout on the subdomain. This feature will go live shortly.</p>
            
            <div class="margin-top-xl text-center">
                <a href="<?php echo SITE_URL; ?>/category.php" class="btn btn-maroon btn-lg">Browse and Order Books</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
