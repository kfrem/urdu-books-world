<?php
/**
 * Urdu Books World - Public Layout Footer
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$email_contact = get_setting('contact_email', 'info@urdubooksworld.co.uk');
$phone_uk_val = get_setting('contact_phone_uk', '+44 7123 456789');
$phone_pk_val = get_setting('contact_phone_pk', '+92 300 1234567');
$whatsapp_val = get_setting('whatsapp_number', '447123456789');
$site_name_val = get_setting('site_name', 'Urdu Books World');
?>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container newsletter-grid">
            <div class="newsletter-text">
                <h3 class="newsletter-title"><i class="fa fa-envelope-open-text text-gold"></i> <?php echo t('newsletter_signup'); ?></h3>
                <p class="newsletter-subtitle"><?php echo t('newsletter_desc'); ?></p>
            </div>
            <div class="newsletter-form-container">
                <form id="newsletter-form" class="newsletter-form" action="<?php echo SITE_URL; ?>/index.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="email" name="newsletter_email" placeholder="<?php echo t('email'); ?>" class="newsletter-input" required>
                    <button type="submit" name="newsletter_subscribe" class="newsletter-btn"><?php echo t('subscribe'); ?></button>
                </form>
                <div id="newsletter-message" class="newsletter-message"></div>
            </div>
        </div>
    </section>

    <!-- Main Footer -->
    <footer class="site-footer">
        <div class="container footer-grid">
            <!-- Brand Column -->
            <div class="footer-col brand-col">
                <a href="<?php echo SITE_URL; ?>/index.php" class="footer-logo">
                    <h4 class="text-white font-bold"><?php echo e($site_name_val); ?></h4>
                </a>
                <p class="footer-desc">
                    Selling Urdu and Pakistani books in the United Kingdom and Europe. Specialized in bilingual education, classic literature, and direct publisher distribution.
                </p>
                <div class="social-icons">
                    <a href="#" class="social-icon" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://wa.me/<?php echo e($whatsapp_val); ?>" class="social-icon" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-col">
                <h4 class="footer-col-title"><?php echo t('home'); ?></h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/category.php"><?php echo t('publications'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>/new-arrivals.php"><?php echo t('new_arrivals'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>/discounts.php"><?php echo t('discounts'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>/request-book.php"><?php echo t('request_book'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>/how-to-pay.php"><?php echo t('how_to_pay'); ?></a></li>
                </ul>
            </div>
            
            <!-- Informational Pages -->
            <div class="footer-col">
                <h4 class="footer-col-title">Information</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/about.php"><?php echo t('about'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>/delivery-charges.php"><?php echo t('delivery_charges'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php"><?php echo t('contact'); ?></a></li>
                    <li><a href="<?php echo SITE_URL; ?>/privacy-policy.php">Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/terms.php">Terms & Conditions</a></li>
                </ul>
            </div>
            
            <!-- Contact Details -->
            <div class="footer-col contact-col">
                <h4 class="footer-col-title">Contact</h4>
                <ul class="footer-contact-details">
                    <li>
                        <i class="fa fa-envelope footer-icon"></i>
                        <a href="mailto:<?php echo e($email_contact); ?>"><?php echo e($email_contact); ?></a>
                    </li>
                    <li>
                        <i class="fa fa-phone footer-icon"></i>
                        <span>UK: <?php echo e($phone_uk_val); ?></span>
                    </li>
                    <li>
                        <i class="fa fa-phone footer-icon"></i>
                        <span>PK: <?php echo e($phone_pk_val); ?></span>
                    </li>
                    <li>
                        <i class="fab fa-whatsapp footer-icon"></i>
                        <a href="https://wa.me/<?php echo e($whatsapp_val); ?>" target="_blank">WhatsApp Chat</a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Footer Bottom Bar -->
        <div class="footer-bottom">
            <div class="container footer-bottom-content">
                <p class="copyright-text">
                    &copy; <?php echo date('Y'); ?> <?php echo e($site_name_val); ?>. All Rights Reserved. Co-powered by KAFS LTD.
                </p>
                <div class="payment-methods">
                    <span class="payment-label">Accepted Payments:</span>
                    <span class="payment-badge" title="Bank Transfer"><i class="fa fa-university"></i> Bank</span>
                    <span class="payment-badge" title="Cash on Delivery"><i class="fa fa-truck"></i> COD</span>
                    <span class="payment-badge" title="Stripe Coming Soon"><i class="fab fa-cc-stripe"></i> Cards</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Global Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js" crossorigin="anonymous"></script>
    <script>
        window.siteUrl = "<?php echo SITE_URL; ?>";
        window.csrfToken = "<?php echo e($_SESSION['csrf_token']); ?>";
    </script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/cart.js"></script>
</body>
</html>
