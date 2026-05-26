<?php
/**
 * Urdu Books World - Request a Book
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$success_msg = '';
$error_msg = '';

$user = current_user();
$prefill_title = isset($_GET['title']) ? trim($_GET['title']) : '';
$prefill_isbn = isset($_GET['isbn']) ? trim($_GET['isbn']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_submit'])) {
    verify_csrf_token();
    
    $customer_name = trim($_POST['customer_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $book_title = trim($_POST['book_title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $notes = trim($_POST['notes']);
    
    if (empty($customer_name) || empty($email) || empty($book_title)) {
        $error_msg = 'Please fill in your name, email, and the requested book title.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO book_requests 
                (customer_name, email, phone, book_title, author, isbn, notes, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$customer_name, $email, $phone, $book_title, $author, $isbn, $notes]);
            
            $success_msg = t('request_success');
            // Clear fields on success
            $prefill_title = '';
            $prefill_isbn = '';
        } catch (PDOException $e) {
            error_log("Failed to insert book request: " . $e->getMessage());
            $error_msg = 'An error occurred while submitting your request. Please try again.';
        }
    }
}

$page_title = t('request_book');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container container-sm">
        <div class="auth-box">
            <h1 class="auth-box-title text-center <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>"><?php echo t('request_book'); ?></h1>
            <p class="auth-box-subtitle text-center <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>"><?php echo t('request_book_desc'); ?></p>
            
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
            
            <form action="<?php echo SITE_URL; ?>/request-book.php" method="POST" class="auth-form margin-top-lg">
                <?php echo csrf_field(); ?>
                
                <h3 class="auth-box-section-title">Your Details</h3>
                
                <div class="form-row-2-cols margin-top-md">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="text-maroon">*</span></label>
                        <input type="text" name="customer_name" class="form-control" required value="<?php echo $user ? e($user['first_name'] . ' ' . $user['last_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address <span class="text-maroon">*</span></label>
                        <input type="email" name="email" class="form-control" required value="<?php echo $user ? e($user['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo $user ? e($user['phone']) : ''; ?>">
                </div>
                
                <h3 class="auth-box-section-title margin-top-lg">Book Details</h3>
                
                <div class="form-group margin-top-md">
                    <label class="form-label"><?php echo t('book_title'); ?> <span class="text-maroon">*</span></label>
                    <input type="text" name="book_title" class="form-control" required value="<?php echo e($prefill_title); ?>">
                </div>
                
                <div class="form-row-2-cols margin-top-md">
                    <div class="form-group">
                        <label class="form-label"><?php echo t('author'); ?></label>
                        <input type="text" name="author" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo t('isbn'); ?></label>
                        <input type="text" name="isbn" class="form-control" value="<?php echo e($prefill_isbn); ?>">
                    </div>
                </div>
                
                <div class="form-group margin-top-md">
                    <label class="form-label"><?php echo t('notes'); ?></label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Provide extra details e.g. publisher name, edition, quantity needed, or target language..."></textarea>
                </div>
                
                <div class="margin-top-xl">
                    <button type="submit" name="request_submit" class="btn btn-maroon btn-full-width btn-lg">
                        <i class="fa fa-paper-plane"></i> <?php echo t('submit_request'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
