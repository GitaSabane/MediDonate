<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

$stmt = $pdo->prepare("SELECT m.*, u.full_name as donor_name FROM medicines m 
                       JOIN users u ON m.donor_id = u.id 
                       WHERE m.verification_status = 'pending' 
                       ORDER BY m.created_at ASC");
$stmt->execute();
$medicines = $stmt->fetchAll();

$pageTitle = 'Pending Medicines';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Pending Medicines</h2>
            <?php echo displayAlerts(); ?>
            <?php if (empty($medicines)): ?>
                <div class="alert alert-success">No pending medicines at the moment.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Donor</th>
                            <th>Quantity</th>
                            <th>Expiry</th>
                            <th>Submitted</th>
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
                                <td><?php echo formatDateTime($m['created_at']); ?></td>
                                <td><a href="medicine-details.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-primary">Review</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>