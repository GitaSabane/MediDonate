<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ?");
$stmt->execute([$user['id']]);
$org = $stmt->fetch();

$stmt = $pdo->prepare("SELECT r.*, m.name as medicine_name, m.unit 
                       FROM medicine_requests r 
                       JOIN medicines m ON r.medicine_id = m.id 
                       WHERE r.organization_id = ? 
                       ORDER BY r.requested_at DESC");
$stmt->execute([$org['id']]);
$requests = $stmt->fetchAll();

$pageTitle = 'My Requests';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>My Requests</h2>
            <?php echo displayAlerts(); ?>
            <?php if (empty($requests)): ?>
                <div class="alert alert-info">You have not made any requests yet.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Requested Qty</th>
                            <th>Approved Qty</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['medicine_name']); ?></td>
                                <td><?php echo $r['requested_quantity']; ?></td>
                                <td><?php echo $r['approved_quantity'] ?? '-'; ?></td>
                                <td><?php echo getStatusBadge($r['urgency']); ?></td>
                                <td><?php echo getStatusBadge($r['status']); ?></td>
                                <td><?php echo formatDateTime($r['requested_at']); ?></td>
                                <td>
                                    <a href="request-details.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if ($r['status'] == 'pending'): ?>
                                        <a href="#" onclick="if(confirm('Cancel this request?')) window.location='cancel-request.php?id=<?php echo $r['id']; ?>';" class="btn btn-sm btn-danger">Cancel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>