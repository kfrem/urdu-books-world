<?php
/**
 * Urdu Books World - Admin Settings Page
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

// Handle Settings POST Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    verify_csrf_token();
    
    $settings_to_update = [
        'site_name' => trim($_POST['site_name']),
        'pkr_to_gbp_rate' => (float)$_POST['pkr_to_gbp_rate'],
        'free_delivery_threshold_gbp' => (float)$_POST['free_delivery_threshold_gbp'],
        'uk_delivery_charge_gbp' => (float)$_POST['uk_delivery_charge_gbp'],
        'international_delivery_charge_gbp' => (float)$_POST['international_delivery_charge_gbp'],
        'contact_email' => trim($_POST['contact_email']),
        'contact_phone_uk' => trim($_POST['contact_phone_uk']),
        'contact_phone_pk' => trim($_POST['contact_phone_pk']),
        'whatsapp_number' => trim($_POST['whatsapp_number']),
        'bank_account_details' => trim($_POST['bank_account_details'])
    ];
    
    try {
        foreach ($settings_to_update as $key => $value) {
            set_setting($key, $value);
        }
        $success_msg = 'Site-wide configuration updated successfully!';
    } catch (PDOException $e) {
        $error_msg = 'Failed to write settings to database.';
    }
}

// Fetch active settings values
$site_name_val = get_setting('site_name', 'Urdu Books World');
$pkr_to_gbp_rate_val = get_setting('pkr_to_gbp_rate', '360');
$free_delivery_threshold_gbp_val = get_setting('free_delivery_threshold_gbp', '30.00');
$uk_delivery_charge_gbp_val = get_setting('uk_delivery_charge_gbp', '3.95');
$intl_delivery_charge_gbp_val = get_setting('international_delivery_charge_gbp', '12.50');
$contact_email_val = get_setting('contact_email', 'info@urdubooksworld.co.uk');
$contact_phone_uk_val = get_setting('contact_phone_uk', '+44 7123 456789');
$contact_phone_pk_val = get_setting('contact_phone_pk', '+92 300 1234567');
$whatsapp_number_val = get_setting('whatsapp_number', '447123456789');
$bank_account_details_val = get_setting('bank_account_details', '');
?>

<div class="admin-page-header">
    <h1 class="admin-title">Store Configuration Settings</h1>
    <p class="text-muted">Adjust conversion rates, delivery charge thresholds, contact telephone numbers, and bank routing details.</p>
</div>

<!-- Alerts -->
<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger margin-top-md">
        <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success margin-top-md">
        <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
    </div>
<?php endif; ?>

<div class="admin-card margin-top-lg padding-lg">
    <form action="settings.php" method="POST" class="auth-form admin-settings-form">
        <?php echo csrf_field(); ?>
        
        <h3 class="auth-box-section-title" style="margin-top:0;">1. General Store Parameters</h3>
        
        <div class="form-row-2-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Store Brand Name *</label>
                <input type="text" name="site_name" class="form-control" required value="<?php echo e($site_name_val); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">PKR to GBP (£) Exchange Rate *</label>
                <input type="number" step="0.01" name="pkr_to_gbp_rate" class="form-control" required value="<?php echo e($pkr_to_gbp_rate_val); ?>">
                <p class="small-font text-muted margin-top-xs">Used to display PKR equivalent estimates to visitors (1 GBP = X PKR).</p>
            </div>
        </div>
        
        <h3 class="auth-box-section-title margin-top-lg">2. Royal Mail Shipping &amp; Delivery Thresholds</h3>
        
        <div class="form-row-3-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">UK Delivery Flat Rate (£) *</label>
                <input type="number" step="0.01" name="uk_delivery_charge_gbp" class="form-control" required value="<?php echo e($uk_delivery_charge_gbp_val); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">UK Free Delivery Threshold (£) *</label>
                <input type="number" step="0.01" name="free_delivery_threshold_gbp" class="form-control" required value="<?php echo e($free_delivery_threshold_gbp_val); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">International Shipping Fee (£) *</label>
                <input type="number" step="0.01" name="international_delivery_charge_gbp" class="form-control" required value="<?php echo e($intl_delivery_charge_gbp_val); ?>">
            </div>
        </div>
        
        <h3 class="auth-box-section-title margin-top-lg">3. Support Contact &amp; Liaison Lines</h3>
        
        <div class="form-row-2-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">Contact Email Address *</label>
                <input type="email" name="contact_email" class="form-control" required value="<?php echo e($contact_email_val); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">WhatsApp Sourcing Line *</label>
                <input type="text" name="whatsapp_number" class="form-control" required placeholder="e.g. 447123456789" value="<?php echo e($whatsapp_number_val); ?>">
                <p class="small-font text-muted margin-top-xs">Numeric format only, with country code. Used for "Buy on WhatsApp" link.</p>
            </div>
        </div>
        
        <div class="form-row-2-cols margin-top-md">
            <div class="form-group">
                <label class="form-label">UK Support Telephone</label>
                <input type="text" name="contact_phone_uk" class="form-control" value="<?php echo e($contact_phone_uk_val); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Pakistan liaison telephone</label>
                <input type="text" name="contact_phone_pk" class="form-control" value="<?php echo e($contact_phone_pk_val); ?>">
            </div>
        </div>
        
        <h3 class="auth-box-section-title margin-top-lg">4. Barclays Bank Routing Coordinates</h3>
        
        <div class="form-group margin-top-md">
            <label class="form-label">BACS Payment Details (Displayed to Customers for Bank Transfer option)</label>
            <textarea name="bank_account_details" class="form-control code-font" rows="5" placeholder="Bank: Barclays Bank PLC..."><?php echo e($bank_account_details_val); ?></textarea>
        </div>
        
        <div class="margin-top-xl">
            <button type="submit" name="save_settings" class="btn btn-maroon btn-lg">
                <i class="fa fa-save"></i> Save Store Settings
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
