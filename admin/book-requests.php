<?php
/**
 * Urdu Books World - Admin Book Requests Management
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_request_status'])) {
    verify_csrf_token();
    
    $req_id = (int)$_POST['request_id'];
    $new_status = trim($_POST['status']);
    
    try {
        $stmt = $pdo->prepare("UPDATE book_requests SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $req_id]);
        $success_msg = 'Request status updated successfully!';
    } catch (PDOException $e) {
        $error_msg = 'Failed to update request status.';
    }
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    
    if ($token !== $_SESSION['csrf_token']) {
        $error_msg = 'CSRF Security token verification failed.';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM book_requests WHERE id = ?");
            $stmt->execute([$del_id]);
            $success_msg = 'Request ticket deleted successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Failed to delete request ticket.';
        }
    }
}

// Fetch all book requests
try {
    $requests = $pdo->query("SELECT * FROM book_requests ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $requests = [];
}
?>

<div class="admin-page-header">
    <h1 class="admin-title">Special Book Requests</h1>
    <p class="text-muted">Track custom books requested by UK customers and libraries. Source them directly from partner publishing houses.</p>
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

<!-- Listing Table -->
<div class="admin-card margin-top-lg">
    <div class="table-responsive">
        <table class="admin-table text-left">
            <thead>
                <tr>
                    <th>Customer Info</th>
                    <th>Book Details</th>
                    <th>Special instructions</th>
                    <th>Date Received</th>
                    <th>Status</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($requests)): ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <!-- Customer Details -->
                            <td>
                                <div class="font-bold text-dark"><?php echo e($r['customer_name']); ?></div>
                                <div class="small-font text-muted"><?php echo e($r['email']); ?></div>
                                <div class="small-font text-muted"><?php echo e($r['phone'] ?: 'No phone'); ?></div>
                            </td>
                            
                            <!-- Book details -->
                            <td>
                                <div class="font-bold text-maroon"><?php echo e($r['book_title']); ?></div>
                                <div class="small-font text-muted">Author: <?php echo e($r['author'] ?: 'N/A'); ?></div>
                                <div class="small-font text-muted">ISBN: <?php echo e($r['isbn'] ?: 'N/A'); ?></div>
                            </td>
                            
                            <!-- Notes -->
                            <td>
                                <div class="small-font text-dark" style="max-width: 250px; word-wrap: break-word; white-space: pre-line;">
                                    <?php echo e($r['notes'] ?: 'No custom notes provided.'); ?>
                                </div>
                            </td>
                            
                            <!-- Date -->
                            <td class="small-font"><?php echo date('d M Y, h:i A', strtotime($r['created_at'])); ?></td>
                            
                            <!-- Status -->
                            <td>
                                <?php
                                    $badge = 'badge-secondary';
                                    if ($r['status'] === 'fulfilled') $badge = 'badge-success';
                                    if ($r['status'] === 'pending') $badge = 'badge-warning';
                                    if ($r['status'] === 'processing') $badge = 'badge-maroon';
                                    if ($r['status'] === 'closed') $badge = 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $badge; ?> text-capitalize"><?php echo e($r['status']); ?></span>
                            </td>
                            
                            <!-- Actions Form -->
                            <td class="text-right whitespace-nowrap">
                                <form action="book-requests.php" method="POST" class="inline-status-form" style="display:inline-block;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                    <select name="status" class="form-control" style="width: auto; display: inline-block; padding: 2px 4px; font-size:12px;" onchange="this.form.submit()">
                                        <option value="pending" <?php echo $r['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $r['status'] === 'processing' ? 'selected' : ''; ?>>Sourcing</option>
                                        <option value="fulfilled" <?php echo $r['status'] === 'fulfilled' ? 'selected' : ''; ?>>Sourced</option>
                                        <option value="closed" <?php echo $r['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                    <input type="hidden" name="update_request_status" value="1">
                                </form>
                                <a href="book-requests.php?action=delete&id=<?php echo $r['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="action-btn-icon text-danger" title="Delete request" onclick="return confirm('Are you sure you want to delete this request ticket?')"><i class="fa fa-trash-can"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted padding-lg">No book requests have been submitted yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
