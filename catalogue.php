<?php
/**
 * Urdu Books World - Catalogue
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/cart.php';

try {
    $stmt = $pdo->query("
        SELECT b.*, a.name_en AS author_en, a.name_ur AS author_ur, c.name_en AS category_en, c.name_ur AS category_ur
        FROM books b
        JOIN authors a ON b.author_id = a.id
        JOIN categories c ON b.category_id = c.id
        WHERE b.is_active = 1
        ORDER BY c.sort_order ASC, b.title_en ASC
    ");
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Catalogue load failed: " . $e->getMessage());
    $books = [];
}

$page_title = t('catalogue');
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h1 class="section-title"><i class="fa fa-book-open text-gold"></i> <?php echo t('catalogue'); ?></h1>
            <div class="section-divider"></div>
        </div>

        <?php if (!empty($books)): ?>
            <div class="table-responsive margin-top-lg">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th><?php echo t('author'); ?></th>
                            <th>Category</th>
                            <th><?php echo t('isbn'); ?></th>
                            <th class="text-right"><?php echo t('price'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <?php
                                $price = (float)$book['price_gbp'];
                                $discount = (int)$book['discount_percent'];
                                $final = $price - ($price * ($discount / 100));
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo SITE_URL; ?>/book.php?slug=<?php echo e($book['slug']); ?>" class="font-bold text-maroon"><?php echo e($book['title_en']); ?></a>
                                    <div class="lang-ur-font text-muted"><?php echo e($book['title_ur']); ?></div>
                                </td>
                                <td><?php echo lang_val($book, 'author'); ?></td>
                                <td><?php echo lang_val($book, 'category'); ?></td>
                                <td><?php echo e($book['isbn'] ?: 'N/A'); ?></td>
                                <td class="text-right"><?php echo money($final); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-muted margin-top-lg">The catalogue is being prepared.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
