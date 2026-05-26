<?php
/**
 * Urdu Books World - Admin Users Management
 */

require_once __DIR__ . '/../includes/admin-header.php';

$error_msg = '';
$success_msg = '';

// Handle Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    verify_csrf_token();
    
    $target_uid = (int)$_POST['user_id'];
    $new_role = trim($_POST['role']);
    
    // Prevent locking out current admin
    if ($target_uid === (int)$_SESSION['user_id'] && $new_role !== 'admin') {
        $error_msg = 'Cannot demote your own active account from the administrator role.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_uid]);
            $success_msg = 'User role updated successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Failed to update user role.';
        }
    }
}

// Handle User Deactivation/Toggling
if (isset($_GET['action']) && $_GET['action'] === 'toggle_active' && isset($_GET['id'])) {
    $target_uid = (int)$_GET['id'];
    $token = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    
    if ($token !== $_SESSION['csrf_token']) {
        $error_msg = 'CSRF verification failed.';
    } elseif ($target_uid === (int)$_SESSION['user_id']) {
        $error_msg = 'Cannot deactivate your own active account.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ?");
            $stmt->execute([$target_uid]);
            $success_msg = 'User status updated successfully!';
        } catch (PDOException $e) {
            $error_msg = 'Failed to update user status.';
        }
    }
}

// Fetch all users
try {
    $users = $pdo->query("SELECT * FROM users ORDER BY role ASC, created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $users = [];
}
?>

<div class="admin-page-header">
    <h1 class="admin-title">Users &amp; Roles</h1>
    <p class="text-muted">Review registered retail customers, configure library procurement privileges, and manage admin credentials.</p>
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

<!-- Users Table -->
<div class="admin-card margin-top-lg">
    <div class="table-responsive">
        <table class="admin-table text-left">
            <thead>
                <tr>
                    <th>User Details</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Registered Date</th>
                    <th class="text-center">Active Status</th>
                    <th class="text-center">Assigned Role</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <!-- Name -->
                            <td class="font-bold text-dark"><?php echo e($u['first_name'] . ' ' . $u['last_name']); ?></td>
                            
                            <!-- Email -->
                            <td class="code-font"><?php echo e($u['email']); ?></td>
                            
                            <!-- Phone -->
                            <td><?php echo e($u['phone'] ?: 'N/A'); ?></td>
                            
                            <!-- Reg Date -->
                            <td class="small-font"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                            
                            <!-- Status -->
                            <td class="text-center">
                                <?php if ($u['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Deactivated</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Role Dropdown Form -->
                            <td class="text-center">
                                <form action="users.php" method="POST" class="inline-status-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <select name="role" class="form-control" style="width: auto; display: inline-block; padding: 2px 4px; font-size:12px;" onchange="this.form.submit()">
                                        <option value="customer" <?php echo $u['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="library" <?php echo $u['role'] === 'library' ? 'selected' : ''; ?>>Library Board</option>
                                        <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <input type="hidden" name="update_role" value="1">
                                </form>
                            </td>
                            
                            <!-- Actions -->
                            <td class="text-right whitespace-nowrap">
                                <a href="users.php?action=toggle_active&id=<?php echo $u['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-outline-maroon btn-xs" onclick="return confirm('Are you sure you want to toggle this user\'s access status?')">
                                    <?php echo $u['is_active'] ? '<i class="fa fa-ban"></i> Deactivate' : '<i class="fa fa-check"></i> Activate'; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
