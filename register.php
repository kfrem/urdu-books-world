<?php
/**
 * Urdu Books World - Customer Registration Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

if (is_logged_in()) {
    redirect(SITE_URL . '/account.php');
}

$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    verify_csrf_token();
    
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $address1 = trim($_POST['address1']);
    $address2 = trim($_POST['address2']);
    $city = trim($_POST['city']);
    $postcode = trim($_POST['postcode']);
    $country = trim($_POST['country'] ?: 'United Kingdom');
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error_msg = 'Please fill in all required fields marked with an asterisk (*).';
    } elseif (strlen($password) < 6) {
        $error_msg = 'Password must be at least 6 characters long.';
    } else {
        $result = register_user($email, $password, $first_name, $last_name, $phone, $address1, $address2, $city, $postcode, $country);
        if ($result['status']) {
            // Sync cart
            sync_session_cart_to_db();
            
            // Redirect to destination
            if (isset($_SESSION['redirect_after_login'])) {
                $target = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                redirect($target);
            } else {
                redirect(SITE_URL . '/account.php');
            }
        } else {
            $error_msg = $result['message'];
        }
    }
}

$page_title = t('login_register');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container container-md">
        <div class="auth-box">
            <h1 class="auth-box-title text-center">Create a New Account</h1>
            <p class="auth-box-subtitle text-center">Join Urdu Books World today to save addresses and track your procurement deliveries.</p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger margin-top-md">
                    <i class="fa fa-circle-exmark"></i> <?php echo e($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo SITE_URL; ?>/register.php" method="POST" class="auth-form margin-top-lg">
                <?php echo csrf_field(); ?>
                
                <div class="form-row-2-cols">
                    <div class="form-group">
                        <label for="reg-first" class="form-label"><?php echo t('first_name'); ?> <span class="text-maroon">*</span></label>
                        <input type="text" id="reg-first" name="first_name" class="form-control" required placeholder="John">
                    </div>
                    <div class="form-group">
                        <label for="reg-last" class="form-label"><?php echo t('last_name'); ?> <span class="text-maroon">*</span></label>
                        <input type="text" id="reg-last" name="last_name" class="form-control" required placeholder="Doe">
                    </div>
                </div>
                
                <div class="form-row-2-cols margin-top-md">
                    <div class="form-group">
                        <label for="reg-email" class="form-label"><?php echo t('email'); ?> <span class="text-maroon">*</span></label>
                        <input type="email" id="reg-email" name="email" class="form-control" required placeholder="name@example.com">
                    </div>
                    <div class="form-group">
                        <label for="reg-pass" class="form-label">Password <span class="text-maroon">*</span></label>
                        <input type="password" id="reg-pass" name="password" class="form-control" required minlength="6" placeholder="At least 6 characters">
                    </div>
                </div>
                
                <div class="form-group margin-top-md">
                    <label for="reg-phone" class="form-label"><?php echo t('phone'); ?></label>
                    <input type="text" id="reg-phone" name="phone" class="form-control" placeholder="+44 7123 456789">
                </div>
                
                <h3 class="auth-box-section-title margin-top-lg">Shipping Address</h3>
                
                <div class="form-group margin-top-md">
                    <label for="reg-addr1" class="form-label"><?php echo t('address_line1'); ?></label>
                    <input type="text" id="reg-addr1" name="address1" class="form-control" placeholder="House number, street name">
                </div>
                <div class="form-group margin-top-md">
                    <label for="reg-addr2" class="form-label"><?php echo t('address_line2'); ?></label>
                    <input type="text" id="reg-addr2" name="address2" class="form-control" placeholder="Apartment, suite, unit (optional)">
                </div>
                
                <div class="form-row-3-cols margin-top-md">
                    <div class="form-group">
                        <label for="reg-city" class="form-label"><?php echo t('city'); ?></label>
                        <input type="text" id="reg-city" name="city" class="form-control" placeholder="London">
                    </div>
                    <div class="form-group">
                        <label for="reg-post" class="form-label"><?php echo t('postcode'); ?></label>
                        <input type="text" id="reg-post" name="postcode" class="form-control" placeholder="SW1A 1AA">
                    </div>
                    <div class="form-group">
                        <label for="reg-country" class="form-label"><?php echo t('country'); ?></label>
                        <select id="reg-country" name="country" class="form-control">
                            <option value="United Kingdom" selected>United Kingdom</option>
                            <option value="Pakistan">Pakistan</option>
                            <option value="Ireland">Ireland</option>
                            <option value="Germany">Germany</option>
                            <option value="France">France</option>
                            <option value="United States">United States</option>
                        </select>
                    </div>
                </div>
                
                <div class="margin-top-xl">
                    <button type="submit" name="register_submit" class="btn btn-maroon btn-full-width btn-lg">
                        <i class="fa fa-user-plus"></i> Complete Registration
                    </button>
                </div>
            </form>
            
            <div class="auth-box-footer margin-top-lg text-center">
                <p>Already have an account? <a href="<?php echo SITE_URL; ?>/login.php" class="text-maroon font-bold">Log in here</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
