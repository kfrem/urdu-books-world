<?php
/**
 * Urdu Books World - Contact Us Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$success_msg = '';
$error_msg = '';

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    verify_csrf_token();
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_msg = 'All form fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } else {
        // Send email mock
        $mail_to = get_setting('contact_email', 'info@urdubooksworld.co.uk');
        $safe_subject = str_replace(["\r", "\n"], '', $subject);
        $safe_email = str_replace(["\r", "\n"], '', $email);
        $mail_subject = "Urdu Books World Support - " . $safe_subject;
        $mail_body = "Message from: " . $name . " <" . $email . ">\n\n" . $message;
        $mail_headers = "From: no-reply@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\nReply-To: " . $safe_email;
        @mail($mail_to, $mail_subject, $mail_body, $mail_headers);
        
        $success_msg = 'Your inquiry has been successfully sent! Our customer support team will email you back shortly.';
    }
}

$email_val = get_setting('contact_email', 'info@urdubooksworld.co.uk');
$phone_uk_val = get_setting('contact_phone_uk', '+44 7123 456789');
$phone_pk_val = get_setting('contact_phone_pk', '+92 300 1234567');
$whatsapp_val = get_setting('whatsapp_number', '447123456789');

$page_title = t('contact');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container">
        
        <div class="section-header">
            <h1 class="section-title"><i class="fa fa-envelope text-gold"></i> <?php echo t('contact'); ?></h1>
            <div class="section-divider"></div>
        </div>
        
        <div class="book-details-grid margin-top-lg">
            <!-- Left: Info Details -->
            <div class="book-details-image-col">
                <div class="bg-light padding-md border-radius-sm">
                    <h3 class="font-bold text-maroon"><i class="fa fa-building"></i> Urdu Books World HQ</h3>
                    <p class="margin-top-xs text-muted">A dedicated B2B and B2C depot distributing Pakistani and Urdu publications in the United Kingdom.</p>
                    
                    <ul class="footer-contact-details margin-top-md" style="padding-left: 0;">
                        <li style="margin-bottom: 12px;">
                            <i class="fa fa-map-marker-alt text-gold" style="width: 25px;"></i>
                            <span>Office 10, Commerce Chambers, Royal Avenue, London, EC1A 2WB, United Kingdom</span>
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fa fa-envelope text-gold" style="width: 25px;"></i>
                            <a href="mailto:<?php echo e($email_val); ?>" class="text-maroon"><?php echo e($email_val); ?></a>
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fa fa-phone text-gold" style="width: 25px;"></i>
                            <span>UK Line: <?php echo e($phone_uk_val); ?></span>
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fa fa-phone text-gold" style="width: 25px;"></i>
                            <span>PK Liaison: <?php echo e($phone_pk_val); ?></span>
                        </li>
                        <li style="margin-bottom: 12px;">
                            <i class="fab fa-whatsapp text-gold" style="width: 25px;"></i>
                            <a href="https://wa.me/<?php echo e($whatsapp_val); ?>" target="_blank" class="text-maroon font-bold">WhatsApp Direct Message</a>
                        </li>
                    </ul>
                    
                    <h4 class="margin-top-lg font-bold">Business Hours</h4>
                    <p class="small-font text-muted">Monday - Friday: 9:00 AM - 5:00 PM GMT<br>Saturday: 10:00 AM - 2:00 PM GMT<br>Sunday: Closed</p>
                </div>
            </div>
            
            <!-- Right: Submission Form -->
            <div class="book-details-info-col">
                <div class="auth-box" style="box-shadow: none; padding: 0;">
                    <h3 class="auth-box-section-title">Send us a Message</h3>
                    <p class="text-muted">Use this form for library acquisitions, B2B quotes, or general customer support issues.</p>
                    
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
                    
                    <form action="<?php echo SITE_URL; ?>/contact.php" method="POST" class="auth-form margin-top-md">
                        <?php echo csrf_field(); ?>
                        
                        <div class="form-row-2-cols">
                            <div class="form-group">
                                <label class="form-label">Your Name <span class="text-maroon">*</span></label>
                                <input type="text" name="name" class="form-control" required value="<?php echo $user ? e($user['first_name'] . ' ' . $user['last_name']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="text-maroon">*</span></label>
                                <input type="email" name="email" class="form-control" required value="<?php echo $user ? e($user['email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group margin-top-md">
                            <label class="form-label">Subject <span class="text-maroon">*</span></label>
                            <input type="text" name="subject" class="form-control" required placeholder="Acquisitions, Delivery Inquiry, Order Issue...">
                        </div>
                        
                        <div class="form-group margin-top-md">
                            <label class="form-label">Your Message <span class="text-maroon">*</span></label>
                            <textarea name="message" class="form-control" rows="5" required placeholder="Write details here..."></textarea>
                        </div>
                        
                        <div class="margin-top-lg">
                            <button type="submit" name="contact_submit" class="btn btn-maroon btn-md">
                                <i class="fa fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
