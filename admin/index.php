<?php
/**
 * Urdu Books World - Admin Dashboard index
 */

require_once __DIR__ . '/../includes/admin-header.php';

// Calculate Today's Metrics
try {
    $today_stmt = $pdo->prepare("SELECT COUNT(*) as count, IFNULL(SUM(total_gbp), 0) as revenue FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
    $today_stmt->execute();
    $today_metrics = $today_stmt->fetch();
} catch (PDOException $e) {
    $today_metrics = ['count' => 0, 'revenue' => 0];
}

// Calculate Month's Metrics
try {
    $month_stmt = $pdo->prepare("SELECT COUNT(*) as count, IFNULL(SUM(total_gbp), 0) as revenue FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status != 'cancelled'");
    $month_stmt->execute();
    $month_metrics = $month_stmt->fetch();
} catch (PDOException $e) {
    $month_metrics = ['count' => 0, 'revenue' => 0];
}

// Calculate Total Revenue
try {
    $total_revenue = (float)$pdo->query("SELECT IFNULL(SUM(total_gbp), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
} catch (PDOException $e) {
    $total_revenue = 0.00;
}

// Count Low-Stock Alert (stock <= 5)
try {
    $low_stock_count = (int)$pdo->query("SELECT COUNT(*) FROM books WHERE stock_quantity <= 5 AND is_active = 1")->fetchColumn();
} catch (PDOException $e) {
    $low_stock_count = 0;
}

// Count Sourcing Book Requests (pending)
try {
    $pending_requests = (int)$pdo->query("SELECT COUNT(*) FROM book_requests WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $pending_requests = 0;
}

// Count Pending Orders
try {
    $pending_orders_count = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
} catch (PDOException $e) {
    $pending_orders_count = 0;
}

// Fetch 5 Recent Pending Orders
try {
    $recent_orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    $recent_orders = [];
}

// Fetch 5 Recent Sourcing Requests
try {
    $recent_requests = $pdo->query("SELECT * FROM book_requests ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (PDOException $e) {
    $recent_requests = [];
}
?>

<div class="admin-page-header">
    <h1 class="admin-title">Dashboard Overview</h1>
    <p class="text-muted">Real-time metrics, low-stock warnings, and recent diaspora procurement updates.</p>
</div>

<!-- Stat Cards Grid -->
<div class="admin-stats-grid margin-top-lg">
    <!-- Card 1: Today Revenue -->
    <div class="stat-card">
        <div class="stat-card-icon bg-success-light text-success"><i class="fa fa-cash-register"></i></div>
        <div class="stat-card-info">
            <span class="stat-label">Today's Revenue</span>
            <h2 class="stat-value text-success"><?php echo money($today_metrics['revenue']); ?></h2>
            <span class="stat-subtext"><?php echo $today_metrics['count']; ?> orders placed today</span>
        </div>
    </div>
    
    <!-- Card 2: Month Revenue -->
    <div class="stat-card">
        <div class="stat-card-icon bg-maroon-light text-maroon"><i class="fa fa-chart-line"></i></div>
        <div class="stat-card-info">
            <span class="stat-label">This Month's Revenue</span>
            <h2 class="stat-value text-maroon"><?php echo money($month_metrics['revenue']); ?></h2>
            <span class="stat-subtext"><?php echo $month_metrics['count']; ?> orders this month</span>
        </div>
    </div>
    
    <!-- Card 3: Total Cumulative -->
    <div class="stat-card">
        <div class="stat-card-icon bg-gold-light text-gold"><i class="fa fa-coins"></i></div>
        <div class="stat-card-info">
            <span class="stat-label">Cumulative Revenue</span>
            <h2 class="stat-value text-gold"><?php echo money($total_revenue); ?></h2>
            <span class="stat-subtext">Total generated retail sales</span>
        </div>
    </div>
    
    <!-- Card 4: Pending orders -->
    <div class="stat-card">
        <div class="stat-card-icon bg-warning-light text-warning"><i class="fa fa-hourglass-half"></i></div>
        <div class="stat-card-info">
            <span class="stat-label">Pending Orders</span>
            <h2 class="stat-value text-warning"><?php echo $pending_orders_count; ?></h2>
            <span class="stat-subtext">Awaiting payment verification</span>
        </div>
    </div>
    
    <!-- Card 5: Sourcing alerts -->
    <div class="stat-card">
        <div class="stat-card-icon bg-info-light text-info"><i class="fa fa-paper-plane"></i></div>
        <div class="stat-card-info">
            <span class="stat-label">Book Requests</span>
            <h2 class="stat-value text-info"><?php echo $pending_requests; ?></h2>
            <span class="stat-subtext">Pending diaspora sourcing forms</span>
        </div>
    </div>
    
    <!-- Card 6: Low Stock -->
    <div class="stat-card">
        <div class="stat-card-icon bg-danger-light text-danger"><i class="fa fa-triangle-exclamation"></i></div>
        <div class="stat-card-info">
            <span class="stat-label">Low-Stock Warnings</span>
            <h2 class="stat-value text-danger"><?php echo $low_stock_count; ?></h2>
            <span class="stat-subtext">Books with stock level &le; 5</span>
        </div>
    </div>
</div>

<div class="admin-content-split-2 margin-top-xxl">
    <!-- Left: Recent Orders -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fa fa-shopping-bag text-maroon"></i> Recent Orders</h3>
            <a href="orders.php" class="btn btn-outline-maroon btn-xs">View All</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order No.</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $ord): ?>
                            <tr>
                                <td class="font-bold code-font"><?php echo e($ord['order_number']); ?></td>
                                <td><?php echo e($ord['customer_name']); ?></td>
                                <td class="font-medium text-maroon"><?php echo money($ord['total_gbp']); ?></td>
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
                                <td>
                                    <a href="order-view.php?id=<?php echo $ord['id']; ?>" class="action-btn-icon" title="View details"><i class="fa fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Right: Recent Sourcing Requests -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h3 class="admin-card-title"><i class="fa fa-envelope-open-text text-gold"></i> Recent Book Requests</h3>
            <a href="book-requests.php" class="btn btn-outline-maroon btn-xs">View All</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Book Requested</th>
                        <th>Author</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_requests)): ?>
                        <?php foreach ($recent_requests as $req): ?>
                            <tr>
                                <td><?php echo e($req['customer_name']); ?></td>
                                <td class="font-bold"><?php echo e($req['book_title']); ?></td>
                                <td><?php echo e($req['author'] ?: 'N/A'); ?></td>
                                <td>
                                    <?php
                                        $badge = 'badge-secondary';
                                        if ($req['status'] === 'fulfilled') $badge = 'badge-success';
                                        if ($req['status'] === 'pending') $badge = 'badge-warning';
                                        if ($req['status'] === 'processing') $badge = 'badge-maroon';
                                    ?>
                                    <span class="badge <?php echo $badge; ?> text-capitalize"><?php echo e($req['status']); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted">No sourcing requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
