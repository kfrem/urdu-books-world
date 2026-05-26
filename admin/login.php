<?php
/**
 * Urdu Books World - Admin Login
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in as admin
if (is_logged_in() && $_SESSION['user_role'] === 'admin') {
    redirect(SITE_URL . '/admin/index.php');
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    verify_csrf_token();
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_msg = 'Please enter both email and password.';
    } else {
        $result = login_user($email, $password);
        if ($result['status'] && $result['role'] === 'admin') {
            redirect(SITE_URL . '/admin/index.php');
        } else {
            // Log out in case of role mismatch
            logout_user();
            $error_msg = 'Access Denied. Only administrator accounts are authorized to enter this panel.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Urdu Books World</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body class="admin-login-body">

    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="brand-header text-center">
                <h1 class="text-maroon font-bold">URDU <span class="text-gold">BOOKS</span> WORLD</h1>
                <p class="text-muted">Administrator Console Access</p>
            </div>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger margin-top-md">
                    <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" class="auth-form margin-top-lg">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label class="form-label">Administrator Email</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@urdubooksworld.co.uk">
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Security Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                
                <div class="margin-top-lg">
                    <button type="submit" name="admin_login" class="btn btn-maroon btn-full-width btn-lg">
                        <i class="fa fa-sign-in-alt"></i> Access Panel
                    </button>
                </div>
            </form>
            
            <div class="admin-login-footer margin-top-lg text-center">
                <a href="<?php echo SITE_URL; ?>/index.php" class="text-muted small-font"><i class="fa fa-arrow-left"></i> Back to Urdu Books World website</a>
            </div>
        </div>
    </div>

</body>
</html>
