<?php
/**
 * Urdu Books World - Admin Order View Details & Packing Slip
 */

require_once __DIR__ . '/../includes/admin-header.php';

$order_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

if (!$order_id) {
    redirect('orders.php');
}

$error_msg = '';
$success_msg = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf_token();
    
    $new_status = trim($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        $success_msg = 'Order status updated successfully!';
    } catch (PDOException $e) {
        $error_msg = 'Failed to update order status.';
    }
}

// Fetch Order Details
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        redirect('orders.php');
    }
    
    // Fetch Items in this order
    $item_stmt = $pdo->prepare("
        SELECT oi.*, b.title_en, b.title_ur, b.sku, b.isbn, b.loc_classification
        FROM order_items oi
        JOIN books b ON oi.book_id = b.id
        WHERE oi.order_id = ?
    ");
    $item_stmt->execute([$order_id]);
    $order_items = $item_stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Failed to load admin order view: " . $e->getMessage());
    die("Database error loading order details.");
}
?>

<!-- Print-Only Style Overrides -->
<style>
    @media print {
        body, .admin-body, .admin-layout, .admin-main {
            background: #FFFFFF !important;
            color: #000000 !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .admin-topbar, .admin-sidebar, .admin-page-header, .header-action-buttons, .status-card-form-box, .admin-topbar-brand, .admin-topbar-menu, .site-footer {
            display: none !important;
        }
        .admin-main {
            width: 100% !important;
            min-height: auto !important;
            box-shadow: none !important;
            border: none !important;
        }
        .admin-card {
            box-shadow: none !important;
            border: 1px solid #CCCCCC !important;
            margin: 0 0 20px 0 !important;
            padding: 10px !important;
        }
        .print-packing-title {
            display: block !important;
            font-size: 24px !important;
            font-weight: bold !important;
            text-align: center !important;
            margin-bottom: 20px !important;
            border-bottom: 2px solid #000000 !important;
            padding-bottom: 10px !important;
        }
    }
    .print-packing-title {
        display: none;
    }
</style>

<div class="print-packing-title">
    URDU BOOKS WORLD - PACKING SLIP &amp; INVOICE
</div>

<div class="admin-page-header split-header">
    <div>
        <h1 class="admin-title">Order Details: <?php echo e($order['order_number']); ?></h1>
        <p class="text-muted">Review shipping parameters and dispatch instructions.</p>
    </div>
    
    <div class="header-action-buttons">
        <button type="button" class="btn btn-gold btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Print Packing Slip</button>
        <a href="orders.php" class="btn btn-outline-maroon btn-sm"><i class="fa fa-arrow-left"></i> Back to Orders</a>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($error_msg)): ?>
    <div class="alert alert-danger margin-top-md">
        <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
    </div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success margin-top-md">
        <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
    </div>
<?php endif; ?>

<div class="admin-content-split-2 margin-top-lg">
    <!-- Left Column: Shipping details and order items -->
    <div class="flex-column-group">
        <!-- Billing Details -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fa fa-address-card text-maroon"></i> Customer &amp; Delivery Details</h3>
            </div>
            <div class="padding-md">
                <div class="admin-meta-grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <span class="meta-label">Customer Name:</span>
                        <span class="meta-value font-bold"><?php echo e($order['customer_name']); ?></span>
                    </div>
                    <div>
                        <span class="meta-label">Order Number:</span>
                        <span class="meta-value font-bold text-maroon"><?php echo e($order['order_number']); ?></span>
                    </div>
                    <div>
                        <span class="meta-label">Customer Email:</span>
                        <span class="meta-value"><a href="mailto:<?php echo e($order['customer_email']); ?>" class="text-maroon"><?php echo e($order['customer_email']); ?></a></span>
                    </div>
                    <div>
                        <span class="meta-label">Customer Phone:</span>
                        <span class="meta-value"><?php echo e($order['customer_phone']); ?></span>
                    </div>
                    <div>
                        <span class="meta-label">Delivery Address:</span>
                        <span class="meta-value" style="white-space: pre-line;"><?php echo e($order['delivery_address']); ?></span>
                    </div>
                    <div>
                        <span class="meta-label">Destination Country:</span>
                        <span class="meta-value font-medium"><?php echo e($order['city']); ?>, <?php echo e($order['postcode']); ?><br><strong><?php echo e($order['country']); ?></strong></span>
                    </div>
                </div>
                
                <?php if (!empty($order['notes'])): ?>
                    <div class="admin-customer-notes-box margin-top-md bg-light padding-md border-radius-sm">
                        <span class="meta-label text-maroon font-bold"><i class="fa fa-comment-dots"></i> Special Delivery Notes:</span>
                        <p class="margin-top-xs text-dark" style="margin-bottom:0; font-size:14px;"><?php echo nl2br(e($order['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="admin-card margin-top-lg">
            <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fa fa-shopping-bag text-maroon"></i> Ordered Books</h3>
            </div>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Book Details</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-center">Discount</th>
                            <th class="text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="font-bold text-dark"><?php echo e($item['title_en']); ?></div>
                                    <div class="text-maroon small-font font-bold"><?php echo e($item['sku']); ?></div>
                                    <div class="text-muted small-font">
                                        ISBN: <?php echo e($item['isbn'] ?: 'N/A'); ?>
                                        <?php if (!empty($item['loc_classification'])): ?>
                                             | LOC: <?php echo e($item['loc_classification']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center font-bold text-dark">x<?php echo $item['quantity']; ?></td>
                                <td class="text-right"><?php echo money($item['unit_price_gbp']); ?></td>
                                <td class="text-center"><?php echo $item['discount_percent']; ?>%</td>
                                <td class="text-right font-bold text-maroon"><?php echo money($item['line_total_gbp']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Totals rows -->
                        <tr>
                            <td colspan="4" class="text-right text-muted small-font">Subtotal:</td>
                            <td class="text-right font-medium"><?php echo money($order['subtotal_gbp']); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right text-muted small-font">Delivery Charge:</td>
                            <td class="text-right font-medium"><?php echo money($order['delivery_charge_gbp']); ?></td>
                        </tr>
                        <tr style="border-top: 2px solid #CCCCCC;">
                            <td colspan="4" class="text-right font-bold text-dark">Grand Total:</td>
                            <td class="text-right font-bold text-maroon text-lg"><?php echo money($order['total_gbp']); ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right text-muted small-font">PKR Equivalent:</td>
                            <td class="text-right text-muted font-medium"><?php echo pkr($order['total_gbp']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Status & Sourcing actions -->
    <div class="status-card-form-box">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title"><i class="fa fa-sliders text-maroon"></i> Fulfillment Status</h3>
            </div>
            <div class="padding-md">
                <div class="summary-row">
                    <span class="meta-label">Payment Method:</span>
                    <span class="meta-value text-capitalize font-bold"><?php echo str_replace('_', ' ', $order['payment_method']); ?></span>
                </div>
                <div class="summary-row margin-top-xs">
                    <span class="meta-label">Active Status:</span>
                    <?php
                        $badge = 'badge-secondary';
                        if ($order['status'] === 'delivered') $badge = 'badge-success';
                        if ($order['status'] === 'pending') $badge = 'badge-warning';
                        if ($order['status'] === 'processing' || $order['status'] === 'shipped') $badge = 'badge-maroon';
                        if ($order['status'] === 'cancelled') $badge = 'badge-danger';
                    ?>
                    <span class="badge <?php echo $badge; ?> text-capitalize" style="font-size:14px; padding: 4px 8px;"><?php echo e($order['status']); ?></span>
                </div>
                
                <hr class="meta-divider margin-top-md">
                
                <!-- Status Update Form -->
                <form action="order-view.php?id=<?php echo $order['id']; ?>" method="POST" class="auth-form margin-top-md">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label class="form-label">Update Order State</label>
                        <select name="status" class="form-control">
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending (Unpaid)</option>
                            <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed (Paid)</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="margin-top-md">
                        <button type="submit" name="update_status" class="btn btn-maroon btn-full-width">
                            <i class="fa fa-save"></i> Save Fulfillment Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- UK Bank details instructions quick reference -->
        <?php if ($order['payment_method'] === 'bank_transfer'): ?>
            <div class="admin-card margin-top-lg">
                <div class="admin-card-header">
                    <h3 class="admin-card-title"><i class="fa fa-university text-maroon"></i> Payment Info</h3>
                </div>
                <div class="padding-md">
                    <p class="small-font text-muted" style="margin-bottom:0;">Please double check your Barclays bank account balance to ensure the customer completed BACS transfer with reference <strong><?php echo e($order['order_number']); ?></strong> before updating status to "Confirmed (Paid)".</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
