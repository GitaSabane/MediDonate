<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

$stmt = $pdo->prepare("SELECT m.id, m.name, m.verification_status, m.verification_remarks, 
                              m.approval_date, m.rejection_reason, m.created_at,
                              u.full_name as donor_name
                       FROM medicines m 
                       JOIN users u ON m.donor_id = u.id 
                       WHERE m.verification_status IN ('verified','rejected','more_info')
                       ORDER BY m.updated_at DESC");
$stmt->execute();
$history = $stmt->fetchAll();

$pageTitle = 'Verification History';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Verification History</h2>
            <?php echo displayAlerts(); ?>
            <?php if (empty($history)): ?>
                <div class="alert alert-info">No verification history.</div>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Donor</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($h['name']); ?></td>
                                <td><?php echo htmlspecialchars($h['donor_name']); ?></td>
                                <td><?php echo getStatusBadge($h['verification_status']); ?></td>
                                <td><?php echo htmlspecialchars($h['verification_remarks'] ?? $h['rejection_reason'] ?? ''); ?></td>
                                <td><?php echo formatDateTime($h['updated_at'] ?? $h['created_at']); ?></td>
                                <td><a href="medicine-details.php?id=<?php echo $h['id']; ?>" class="btn btn-sm btn-info">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>