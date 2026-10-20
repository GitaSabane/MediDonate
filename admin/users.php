<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$params = [];
$where = "1=1";
if ($search) {
    $where .= " AND (full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($roleFilter) {
    $where .= " AND role = ?";
    $params[] = $roleFilter;
}
if ($statusFilter) {
    $where .= " AND status = ?";
    $params[] = $statusFilter;
}

$sql = "SELECT * FROM users WHERE $where ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = $_POST['status'];
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        $_SESSION['error'] = 'CSRF validation failed.';
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
        logAudit($_SESSION['user_id'], 'Update User Status', 'user', $userId, "Changed status to $newStatus");
        $_SESSION['success'] = 'User status updated.';
    }
    header('Location: users.php');
    exit;
}

$pageTitle = 'Manage Users';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Manage Users</h2>
            <?php echo displayAlerts(); ?>
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name/email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="donor" <?php if($roleFilter=='donor') echo 'selected'; ?>>Donor</option>
                        <option value="organization" <?php if($roleFilter=='organization') echo 'selected'; ?>>Organization</option>
                        <option value="verifier" <?php if($roleFilter=='verifier') echo 'selected'; ?>>Verifier</option>
                        <option value="admin" <?php if($roleFilter=='admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php if($statusFilter=='active') echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if($statusFilter=='inactive') echo 'selected'; ?>>Inactive</option>
                        <option value="suspended" <?php if($statusFilter=='suspended') echo 'selected'; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo ucfirst($u['role']); ?></td>
                            <td><?php echo getStatusBadge($u['status']); ?></td>
                            <td><?php echo formatDate($u['created_at']); ?></td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Change status?')">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="active" <?php if($u['status']=='active') echo 'selected'; ?>>Active</option>
                                        <option value="inactive" <?php if($u['status']=='inactive') echo 'selected'; ?>>Inactive</option>
                                        <option value="suspended" <?php if($u['status']=='suspended') echo 'selected'; ?>>Suspended</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>