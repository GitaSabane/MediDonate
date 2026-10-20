<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

checkAllExpiry();

$stmt = $pdo->prepare("SELECT m.*, u.full_name as donor_name 
                       FROM medicines m 
                       JOIN users u ON m.donor_id = u.id 
                       WHERE m.expiry_status IN ('expiring_soon','expired')
                       ORDER BY m.expiry_date ASC");
$stmt->execute();
$medicines = $stmt->fetchAll();

$pageTitle = 'Expiring Medicines';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Expiring / Expired Medicines</h2>
            <?php echo displayAlerts(); ?>
            <?php if (empty($medicines)): ?>
                <div class="alert alert-success">No expiring or expired medicines.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Donor</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
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
                                <td><?php echo getStatusBadge($m['expiry_status']); ?></td>
                                <td><a href="medicine-details.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>