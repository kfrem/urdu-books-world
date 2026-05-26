<?php
/**
 * Urdu Books World - 404 Not Found Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

http_response_code(404);
$page_title = "Page Not Found";
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container text-center" style="max-width: 600px; margin: 50px auto;">
        <div class="confirmation-icon-box">
            <i class="fa fa-triangle-exclamation text-gold text-6xl"></i>
        </div>
        
        <h1 class="margin-top-md font-bold text-3xl text-maroon">404 - Page Not Found</h1>
        <h2 class="lang-ur-font margin-top-xs text-xl text-gold" style="line-height: 1.8;">صفحہ دستیاب نہیں ہے</h2>
        
        <p class="margin-top-md text-muted">The page or literary catalog you are looking for might have been moved, renamed, or is temporarily unavailable.</p>
        
        <div class="margin-top-lg">
            <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-maroon btn-lg"><i class="fa fa-home"></i> Back to Homepage</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
