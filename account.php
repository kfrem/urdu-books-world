<?php
/**
 * Urdu Books World - Customer Account Control Panel
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

// Force Login
require_login();

$user = current_user();
$error_msg = '';
$success_msg = '';
$active_tab = isset($_GET['tab']) ? trim($_GET['tab']) : 'details';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $phone = trim($_POST['phone']);
        $address1 = trim($_POST['address1']);
        $address2 = trim($_POST['address2']);
        $city = trim($_POST['city']);
        $postcode = trim($_POST['postcode']);
        $country = trim($_POST['country']);
        
        if (empty($first_name) || empty($last_name)) {
            $error_msg = 'First name and Last name are required.';
            $active_tab = 'details';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET 
                    first_name = ?, last_name = ?, phone = ?, address_line1 = ?, address_line2 = ?, city = ?, postcode = ?, country = ?
                    WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $phone, $address1, $address2, $city, $postcode, $country, $user['id']]);
                
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $success_msg = 'Profile updated successfully!';
                $user = current_user(); // reload
                $active_tab = 'details';
            } catch (PDOException $e) {
                error_log("Profile update failed: " . $e->getMessage());
                $error_msg = 'Failed to update profile details.';
                $active_tab = 'details';
            }
        }
    }
    
    if ($action === 'change_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error_msg = 'All password fields are required.';
            $active_tab = 'password';
        } elseif ($new_password !== $confirm_password) {
            $error_msg = 'New passwords do not match.';
            $active_tab = 'password';
        } elseif (strlen($new_password) < 6) {
            $error_msg = 'Password must be at least 6 characters.';
            $active_tab = 'password';
        } else {
            // Verify old password
            if (password_verify($old_password, $user['password_hash'])) {
                try {
                    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$new_hash, $user['id']]);
                    $success_msg = 'Password updated successfully!';
                    $active_tab = 'password';
                } catch (PDOException $e) {
                    $error_msg = 'Failed to change password.';
                    $active_tab = 'password';
                }
            } else {
                $error_msg = 'Incorrect old password.';
                $active_tab = 'password';
            }
        }
    }
    
    if ($action === 'update_pref_lang') {
        $pref_lang = $_POST['pref_lang'] === 'ur' ? 'ur' : 'en';
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET preferred_language = ? WHERE id = ?");
            $stmt->execute([$pref_lang, $user['id']]);
            
            $_SESSION['lang'] = $pref_lang;
            setcookie('lang', $pref_lang, time() + (86400 * 30), "/");
            
            $success_msg = 'Language preference updated successfully! Re-loading...';
            $active_tab = 'language';
            
            // Redirect to refresh UI language
            header("Refresh:1; url=" . SITE_URL . "/account.php?tab=language");
        } catch (PDOException $e) {
            $error_msg = 'Failed to update preferences.';
            $active_tab = 'language';
        }
    }
}

// Fetch Past Orders
try {
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $order_stmt->execute([$user['id']]);
    $orders = $order_stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

$page_title = t('my_account');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container">
        
        <div class="section-header">
            <h1 class="section-title"><i class="fa fa-circle-user text-gold"></i> <?php echo t('my_account'); ?></h1>
            <div class="section-divider"></div>
        </div>
        
        <!-- Flash alerts -->
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger margin-top-md">
                <i class="fa fa-circle-exmark"></i> <?php echo e($error_msg); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success margin-top-md">
                <i class="fa fa-circle-check"></i> <?php echo e($success_msg); ?>
            </div>
        <?php endif; ?>
        
        <div class="account-layout margin-top-lg">
            
            <!-- Sidebar tabs navigation -->
            <div class="account-sidebar">
                <div class="account-card-user-info padding-md text-center bg-light border-radius-sm">
                    <div class="user-avatar-placeholder bg-maroon text-white font-bold">
                        <?php echo strtoupper($user['first_name'][0] . $user['last_name'][0]); ?>
                    </div>
                    <h3 class="margin-top-sm"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p class="text-muted small-font"><?php echo e($user['email']); ?></p>
                </div>
                
                <div class="account-tab-triggers margin-top-md">
                    <a href="?tab=details" class="account-tab-trigger <?php echo $active_tab === 'details' ? 'active' : ''; ?>">
                        <i class="fa fa-address-card"></i> <?php echo t('billing_details'); ?>
                    </a>
                    <a href="?tab=orders" class="account-tab-trigger <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
                        <i class="fa fa-rectangle-list"></i> <?php echo t('my_orders'); ?>
                    </a>
                    <a href="?tab=password" class="account-tab-trigger <?php echo $active_tab === 'password' ? 'active' : ''; ?>">
                        <i class="fa fa-shield-halved"></i> <?php echo t('change_password'); ?>
                    </a>
                    <a href="?tab=language" class="account-tab-trigger <?php echo $active_tab === 'language' ? 'active' : ''; ?>">
                        <i class="fa fa-language"></i> <?php echo t('preferred_language'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Tabs content -->
            <div class="account-content">
                
                <!-- Tab 1: Profile Details -->
                <div class="account-tab-panel <?php echo $active_tab === 'details' ? 'active' : ''; ?>">
                    <h2 class="panel-section-title">Shipping &amp; Billing Details</h2>
                    <form action="?tab=details" method="POST" class="auth-form margin-top-md">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-row-2-cols">
                            <div class="form-group">
                                <label class="form-label"><?php echo t('first_name'); ?></label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo e($user['first_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo t('last_name'); ?></label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo e($user['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group margin-top-md">
                            <label class="form-label"><?php echo t('phone'); ?></label>
                            <input type="text" name="phone" class="form-control" value="<?php echo e($user['phone']); ?>">
                        </div>
                        
                        <div class="form-group margin-top-md">
                            <label class="form-label"><?php echo t('address_line1'); ?></label>
                            <input type="text" name="address1" class="form-control" value="<?php echo e($user['address_line1']); ?>">
                        </div>
                        <div class="form-group margin-top-md">
                            <label class="form-label"><?php echo t('address_line2'); ?></label>
                            <input type="text" name="address2" class="form-control" value="<?php echo e($user['address_line2']); ?>">
                        </div>
                        
                        <div class="form-row-3-cols margin-top-md">
                            <div class="form-group">
                                <label class="form-label"><?php echo t('city'); ?></label>
                                <input type="text" name="city" class="form-control" value="<?php echo e($user['city']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo t('postcode'); ?></label>
                                <input type="text" name="postcode" class="form-control" value="<?php echo e($user['postcode']); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php echo t('country'); ?></label>
                                <select name="country" class="form-control">
                                    <option value="United Kingdom" <?php echo $user['country'] === 'United Kingdom' ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="Pakistan" <?php echo $user['country'] === 'Pakistan' ? 'selected' : ''; ?>>Pakistan</option>
                                    <option value="Ireland" <?php echo $user['country'] === 'Ireland' ? 'selected' : ''; ?>>Ireland</option>
                                    <option value="Germany" <?php echo $user['country'] === 'Germany' ? 'selected' : ''; ?>>Germany</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="margin-top-lg">
                            <button type="submit" class="btn btn-maroon btn-md">Update Details</button>
                        </div>
                    </form>
                </div>
                
                <!-- Tab 2: Orders list -->
                <div class="account-tab-panel <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
                    <h2 class="panel-section-title"><?php echo t('my_orders'); ?></h2>
                    
                    <?php if (!empty($orders)): ?>
                        <div class="table-responsive margin-top-md">
                            <table class="cart-table account-orders-table">
                                <thead>
                                    <tr>
                                        <th>Order Number</th>
                                        <th><?php echo t('order_date'); ?></th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th class="text-right"><?php echo t('total'); ?></th>
                                        <th class="text-center">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $ord): ?>
                                        <tr>
                                            <td class="font-bold text-maroon"><?php echo e($ord['order_number']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($ord['created_at'])); ?></td>
                                            <td class="text-capitalize small-font"><?php echo str_replace('_', ' ', $ord['payment_method']); ?></td>
                                            <td>
                                                <?php
                                                    $status_badge = 'badge-secondary';
                                                    if ($ord['status'] === 'delivered') $status_badge = 'badge-success';
                                                    if ($ord['status'] === 'cancelled') $status_badge = 'badge-danger';
                                                    if ($ord['status'] === 'processing' || $ord['status'] === 'shipped') $status_badge = 'badge-maroon';
                                                ?>
                                                <span class="badge <?php echo $status_badge; ?> text-capitalize"><?php echo e($ord['status']); ?></span>
                                            </td>
                                            <td class="text-right font-medium"><?php echo money($ord['total_gbp']); ?></td>
                                            <td class="text-center">
                                                <a href="<?php echo SITE_URL; ?>/order-confirmation.php?order_number=<?php echo e($ord['order_number']); ?>" class="btn btn-outline-maroon btn-sm">
                                                    <i class="fa fa-receipt"></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="padding-lg text-center text-muted">
                            <p><?php echo t('no_orders'); ?></p>
                            <a href="<?php echo SITE_URL; ?>/category.php" class="btn btn-maroon btn-sm margin-top-sm">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 3: Password Update -->
                <div class="account-tab-panel <?php echo $active_tab === 'password' ? 'active' : ''; ?>">
                    <h2 class="panel-section-title">Change Account Password</h2>
                    <form action="?tab=password" method="POST" class="auth-form margin-top-md" style="max-width: 450px;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="old_password" class="form-control" required placeholder="Enter old password">
                        </div>
                        <div class="form-group margin-top-md">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6" placeholder="At least 6 characters">
                        </div>
                        <div class="form-group margin-top-md">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="Repeat new password">
                        </div>
                        
                        <div class="margin-top-lg">
                            <button type="submit" class="btn btn-maroon btn-md">Change Password</button>
                        </div>
                    </form>
                </div>
                
                <!-- Tab 4: Language preferences -->
                <div class="account-tab-panel <?php echo $active_tab === 'language' ? 'active' : ''; ?>">
                    <h2 class="panel-section-title">Default preferred Language</h2>
                    <p class="text-muted small-font">This selects the default language the site loads in when you log in.</p>
                    
                    <form action="?tab=language" method="POST" class="auth-form margin-top-md" style="max-width: 450px;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="update_pref_lang">
                        
                        <div class="form-group">
                            <label class="form-label"><?php echo t('preferred_language'); ?></label>
                            <div class="margin-top-xs">
                                <label class="shipping-radio-label">
                                    <input type="radio" name="pref_lang" value="en" <?php echo $user['preferred_language'] === 'en' ? 'checked' : ''; ?>>
                                    <span>English (LTR)</span>
                                </label>
                                <label class="shipping-radio-label margin-top-xs">
                                    <input type="radio" name="pref_lang" value="ur" <?php echo $user['preferred_language'] === 'ur' ? 'checked' : ''; ?>>
                                    <span class="lang-ur-font">اردو (Urdu RTL)</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="margin-top-lg">
                            <button type="submit" class="btn btn-maroon btn-md">Save Preference</button>
                        </div>
                    </form>
                </div>
                
            </div>
            
        </div>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
