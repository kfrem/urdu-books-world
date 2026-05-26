<?php
/**
 * Urdu Books World - Privacy Policy
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Privacy Policy';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h1 class="section-title">Privacy Policy</h1>
            <div class="section-divider"></div>
        </div>

        <div class="content-page margin-top-lg">
            <p>Urdu Books World Limited collects only the information needed to process orders, respond to enquiries, manage customer accounts, and improve the service.</p>

            <h2>Information We Collect</h2>
            <p>We may collect your name, email address, phone number, delivery address, order history, account preferences, book request details, and newsletter subscription status.</p>

            <h2>How We Use Information</h2>
            <p>Your information is used to fulfil orders, provide customer support, manage delivery, send order updates, handle book requests, and send newsletters where you have subscribed.</p>

            <h2>Payments</h2>
            <p>Bank transfer and cash-on-delivery orders are handled directly by Urdu Books World. Card payments are marked as coming soon and are not processed by this website at launch.</p>

            <h2>Data Sharing</h2>
            <p>We share information only where required for order fulfilment, delivery, legal compliance, or business administration. We do not sell customer data.</p>

            <h2>Your Rights</h2>
            <p>You may request access, correction, or deletion of your personal information by contacting us through the details on the contact page.</p>

            <h2>Contact</h2>
            <p>For privacy questions, contact <a href="<?php echo SITE_URL; ?>/contact.php">Urdu Books World</a>.</p>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
