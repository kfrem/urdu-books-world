<?php
/**
 * Urdu Books World - Terms and Conditions
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Terms & Conditions';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h1 class="section-title">Terms &amp; Conditions</h1>
            <div class="section-divider"></div>
        </div>

        <div class="content-page margin-top-lg">
            <h2>Orders</h2>
            <p>Orders are accepted subject to stock availability and successful payment confirmation. If a book is unavailable, we will contact you about alternatives, sourcing, or cancellation.</p>

            <h2>Prices and Delivery</h2>
            <p>Prices are shown in GBP. PKR values are displayed as estimates using the current exchange-rate setting. Delivery charges are calculated at checkout.</p>

            <h2>Payment</h2>
            <p>At launch, supported payment methods are bank transfer and UK-only cash on delivery. Orders paid by bank transfer are dispatched after payment clears.</p>

            <h2>Returns</h2>
            <p>You may return eligible books within 14 days of delivery if they are unused and in their original condition. Return postage is the customer's responsibility unless an item is incorrect or damaged.</p>

            <h2>Library and Trade Supply</h2>
            <p>Library, school, mosque, community-centre, and trade supply enquiries may be handled separately from standard retail terms.</p>

            <h2>Contact</h2>
            <p>Questions about an order or these terms can be sent through the <a href="<?php echo SITE_URL; ?>/contact.php">contact page</a>.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
