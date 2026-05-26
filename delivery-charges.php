<?php
/**
 * Urdu Books World - Delivery Charges Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$uk_charge = get_setting('uk_delivery_charge_gbp', '3.95');
$intl_charge = get_setting('international_delivery_charge_gbp', '12.50');
$free_threshold = get_setting('free_delivery_threshold_gbp', '30.00');

$page_title = t('delivery_charges');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container container-sm">
        <div class="auth-box text-justify">
            <h1 class="auth-box-title text-center <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>"><?php echo t('delivery_charges'); ?></h1>
            <div class="section-divider"></div>
            
            <p class="margin-top-md">We manage 100% of our catalog locally from our UK warehouse, meaning there are no customs import delays or hidden tariff fees. Our packages are wrapped securely in bubble wrap and cardboard panels to prevent cover damage.</p>
            
            <table class="cart-table margin-top-lg">
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>Shipping Method</th>
                        <th>Rate</th>
                        <th>Delivery Estimate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="font-bold">United Kingdom</td>
                        <td>Royal Mail Tracked 48</td>
                        <td class="text-maroon font-bold">£<?php echo e($uk_charge); ?></td>
                        <td>2 - 3 Business Days</td>
                    </tr>
                    <tr>
                        <td class="font-bold">United Kingdom</td>
                        <td>Orders over £<?php echo e($free_threshold); ?></td>
                        <td class="text-success font-bold">FREE Delivery</td>
                        <td>2 - 3 Business Days</td>
                    </tr>
                    <tr>
                        <td class="font-bold">International (Europe &amp; US)</td>
                        <td>Royal Mail Airmail Tracked</td>
                        <td class="text-maroon font-bold">£<?php echo e($intl_charge); ?></td>
                        <td>7 - 14 Business Days</td>
                    </tr>
                </tbody>
            </table>
            
            <h3 class="auth-box-section-title margin-top-lg">Packaging Quality</h3>
            <p class="margin-top-xs">We understand that library procurements and avid readers require books in flawless condition. Our staff seals cover corners with defensive foam wraps and utilizes rigid cardboard envelopes. If a book arrives with physical defects, we will provide a free replacement.</p>
            
            <div class="margin-top-xl text-center">
                <a href="<?php echo SITE_URL; ?>/category.php" class="btn btn-maroon btn-lg">Shop with Confidence</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
