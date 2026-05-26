<?php
/**
 * Urdu Books World - Customer Login Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(SITE_URL . '/account.php');
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    verify_csrf_token();
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_msg = 'Please fill in all fields.';
    } else {
        $result = login_user($email, $password);
        if ($result['status']) {
            // Sync cart
            sync_session_cart_to_db();
            
            // Redirect after successful login
            if (isset($_SESSION['redirect_after_login'])) {
                $target = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                redirect($target);
            } else {
                if ($result['role'] === 'admin') {
                    redirect(SITE_URL . '/admin/index.php');
                } else {
                    redirect(SITE_URL . '/account.php');
                }
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
    <div class="container container-sm">
        <div class="auth-box">
            <h1 class="auth-box-title text-center"><?php echo t('login_register'); ?></h1>
            <p class="auth-box-subtitle text-center">Log in to manage your orders, billing address, and preferences.</p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger margin-top-md">
                    <i class="fa fa-circle-exmark"></i> <?php echo e($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo SITE_URL; ?>/login.php" method="POST" class="auth-form margin-top-lg">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label for="login-email" class="form-label"><?php echo t('email'); ?> <span class="text-maroon">*</span></label>
                    <input type="email" id="login-email" name="email" class="form-control" required placeholder="name@example.com">
                </div>
                
                <div class="form-group margin-top-md">
                    <label for="login-password" class="form-label"><?php echo t('change_password'); ?> <span class="text-maroon">*</span></label>
                    <input type="password" id="login-password" name="password" class="form-control" required placeholder="Enter your password">
                </div>
                
                <div class="margin-top-lg">
                    <button type="submit" name="login_submit" class="btn btn-maroon btn-full-width btn-lg">
                        <i class="fa fa-sign-in-alt"></i> Login
                    </button>
                </div>
            </form>
            
            <div class="auth-box-footer margin-top-lg text-center">
                <p>New to Urdu Books World? <a href="<?php echo SITE_URL; ?>/register.php" class="text-maroon font-bold">Register a New Account</a></p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
