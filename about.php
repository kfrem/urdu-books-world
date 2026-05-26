<?php
/**
 * Urdu Books World - About Us Page
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

$page_title = t('about');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container container-sm">
        <div class="auth-box text-justify">
            <h1 class="auth-box-title text-center <?php echo is_rtl() ? 'lang-ur-font' : ''; ?>"><?php echo t('about'); ?></h1>
            <div class="section-divider"></div>
            
            <p class="margin-top-md"><strong>Urdu Books World</strong> is a specialized bilingual e-commerce platform dedicated to connecting the South Asian diaspora in the United Kingdom and Europe with the richest traditions of Urdu, Pakistani, and regional literature.</p>
            
            <p class="margin-top-sm">Our catalog covers classical poetry (Allama Iqbal, Faiz, Ghalib), landmark Urdu novels (Aab-e-Gum, Raja Gidh, Udaas Naslein), modern travelogues, translated international classics, and academic titles. Managed locally under KAFS LTD, we maintain a logistics warehouse in London to dispatch packets quickly and securely.</p>
            
            <h3 class="auth-box-section-title margin-top-lg"><i class="fa fa-book-bookmark text-maroon"></i> Library &amp; Academic Supply (B2G)</h3>
            <p class="margin-top-xs">We are uniquely positioned to serve municipal libraries, university departments, and government procurement officers. We provide cataloguing integration support, including **Library of Congress Classifications (LCC)** and complete **MARC bibliographic records** for catalog databases, ensuring zero administrative friction for academic collections.</p>
            
            <h3 class="auth-box-section-title margin-top-lg"><i class="fa fa-hands-helping text-maroon"></i> Publisher Distribution (B2B)</h3>
            <p class="margin-top-xs">By establishing wholesale channels directly with premier publishing houses in Lahore, Karachi, Rawalpindi, and Jhelum (such as Sang-e-Meel, Oxford University Press, and Book Corner Jhelum), we ensure fair margins for creators and competitive prices for schools, universities, and retail bookstores in the West.</p>
            
            <div class="margin-top-xl text-center">
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-outline-maroon btn-md">Contact our Procurement Team</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
