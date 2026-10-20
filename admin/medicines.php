<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$expiry = $_GET['expiry'] ?? '';

$params = [];
$where = "1=1";
if ($search) {
    $where .= " AND m.name LIKE ?";
    $params[] = "%$search%";
}
if ($status) {
    $where .= " AND m.verification_status = ?";
    $params[] = $status;
}
if ($expiry) {
    $where .= " AND m.expiry_status = ?";
    $params[] = $expiry;
}

$sql = "SELECT m.*, u.full_name as donor_name 
        FROM medicines m 
        JOIN users u ON m.donor_id = u.id 
        WHERE $where 
        ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$medicines = $stmt->fetchAll();

$pageTitle = 'Manage Medicines';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Medicines</h2>
            <?php echo displayAlerts(); ?>
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by name" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Verification</option>
                        <option value="pending" <?php if($status=='pending') echo 'selected'; ?>>Pending</option>
                        <option value="verified" <?php if($status=='verified') echo 'selected'; ?>>Verified</option>
                        <option value="rejected" <?php if($status=='rejected') echo 'selected'; ?>>Rejected</option>
                        <option value="more_info" <?php if($status=='more_info') echo 'selected'; ?>>More Info</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="expiry" class="form-select">
                        <option value="">All Expiry</option>
                        <option value="valid" <?php if($expiry=='valid') echo 'selected'; ?>>Valid</option>
                        <option value="expiring_soon" <?php if($expiry=='expiring_soon') echo 'selected'; ?>>Expiring Soon</option>
                        <option value="expired" <?php if($expiry=='expired') echo 'selected'; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Donor</th>
                        <th>Quantity</th>
                        <th>Expiry</th>
                        <th>Verification</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $m): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['name']); ?></td>
                            <td><?php echo htmlspecialchars($m['donor_name']); ?></td>
                            <td><?php echo $m['quantity']; ?> <?php echo $m['unit']; ?></td>
                            <td><?php echo formatDate($m['expiry_date']); ?></td>
                            <td><?php echo getStatusBadge($m['verification_status']); ?></td>
                            <td><a href="../verifier/medicine-details.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>