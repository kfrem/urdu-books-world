<?php
/**
 * Urdu Books World - Admin Orders Management
 */

require_once __DIR__ . '/../includes/admin-header.php';

// Setup search & filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';

// Pagination setup
$limit = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Build SQL Query
$sql_count = "SELECT COUNT(*) FROM orders WHERE 1=1";
$sql_select = "SELECT * FROM orders WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $sql_count .= " AND status = ?";
    $sql_select .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $sql_count .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
    $sql_select .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Count total
try {
    $count_stmt = $pdo->prepare($sql_count);
    $count_stmt->execute($params);
    $total_orders = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_orders / $limit);
} catch (PDOException $e) {
    $total_orders = 0;
    $total_pages = 1;
}

// Apply Pagination
$sql_select .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

try {
    $stmt = $pdo->prepare($sql_select);
    $bind_index = 1;
    foreach ($params as $p) {
        $type_binding = is_int($p) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($bind_index++, $p, $type_binding);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Orders query failed: " . $e->getMessage());
    $orders = [];
}
?>

<div class="admin-page-header">
    <h1 class="admin-title">Customer Orders</h1>
    <p class="text-muted">Review incoming orders, verify direct bank transfers, manage shipments, and print packing slips.</p>
</div>

<!-- Filters Bar -->
<div class="admin-card margin-top-lg">
    <div class="padding-md">
        <form action="orders.php" method="GET" class="admin-search-form flex-wrap-group">
            <div class="search-input-group flex-grow-1" style="min-width: 250px;">
                <input type="text" name="search" placeholder="Search by order number or customer name..." class="search-input" value="<?php echo e($search); ?>">
                <button type="submit" class="search-submit-btn"><i class="fa fa-search"></i> Search</button>
            </div>
            
            <div class="filter-dropdown-group" style="min-width: 180px;">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending (Unpaid)</option>
                    <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed (Paid)</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <?php if (!empty($search) || $status !== 'all'): ?>
                <div><a href="orders.php" class="small-font text-maroon font-bold">Clear Filters</a></div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Table Grid -->
    <div class="table-responsive">
        <table class="admin-table text-left">
            <thead>
                <tr>
                    <th>Order No.</th>
                    <th>Customer Details</th>
                    <th>Date / Time</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th class="text-right">Grand Total</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <!-- Order Number -->
                            <td class="font-bold code-font text-maroon"><?php echo e($ord['order_number']); ?></td>
                            
                            <!-- Customer Details -->
                            <td>
                                <div class="font-medium text-dark"><?php echo e($ord['customer_name']); ?></div>
                                <div class="small-font text-muted"><?php echo e($ord['customer_email']); ?></div>
                                <div class="small-font text-muted"><?php echo e($ord['customer_phone']); ?></div>
                            </td>
                            
                            <!-- Date -->
                            <td class="small-font"><?php echo date('d M Y, h:i A', strtotime($ord['created_at'])); ?></td>
                            
                            <!-- Payment -->
                            <td class="text-capitalize small-font font-medium">
                                <?php echo str_replace('_', ' ', $ord['payment_method']); ?>
                            </td>
                            
                            <!-- Status -->
                            <td>
                                <?php
                                    $badge = 'badge-secondary';
                                    if ($ord['status'] === 'delivered') $badge = 'badge-success';
                                    if ($ord['status'] === 'pending') $badge = 'badge-warning';
                                    if ($ord['status'] === 'processing' || $ord['status'] === 'shipped') $badge = 'badge-maroon';
                                    if ($ord['status'] === 'cancelled') $badge = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $badge; ?> text-capitalize"><?php echo e($ord['status']); ?></span>
                            </td>
                            
                            <!-- Total -->
                            <td class="text-right font-bold text-maroon"><?php echo money($ord['total_gbp']); ?></td>
                            
                            <!-- Actions -->
                            <td class="text-right whitespace-nowrap">
                                <a href="order-view.php?id=<?php echo $ord['id']; ?>" class="action-btn-icon text-maroon" title="View details &amp; packing slip"><i class="fa fa-eye"></i> Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted padding-lg">No orders found matching criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="admin-card-footer padding-md">
            <div class="pagination-container" style="justify-content: flex-end;">
                <ul class="pagination-list">
                    <?php if ($page > 1): ?>
                        <li><a href="?search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&page=<?php echo $page - 1; ?>" class="pagination-btn"><i class="fa fa-chevron-left"></i></a></li>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li><a href="?search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&page=<?php echo $i; ?>" class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li><a href="?search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&page=<?php echo $page + 1; ?>" class="pagination-btn"><i class="fa fa-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
