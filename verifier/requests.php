<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

$statusFilter = $_GET['status'] ?? 'all';
$params = [];
$where = "1=1";
if ($statusFilter != 'all') {
    $where .= " AND r.status = ?";
    $params[] = $statusFilter;
}

$sql = "SELECT r.*, m.name as medicine_name, m.unit, o.org_name 
        FROM medicine_requests r 
        JOIN medicines m ON r.medicine_id = m.id 
        JOIN organizations o ON r.organization_id = o.id 
        WHERE $where 
        ORDER BY r.requested_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Medicine Requests';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Medicine Requests</h2>
            <?php echo displayAlerts(); ?>
            <div class="mb-3">
                <a href="?status=all" class="btn btn-outline-secondary <?php if($statusFilter=='all') echo 'active'; ?>">All</a>
                <a href="?status=pending" class="btn btn-outline-warning <?php if($statusFilter=='pending') echo 'active'; ?>">Pending</a>
                <a href="?status=approved" class="btn btn-outline-success <?php if($statusFilter=='approved') echo 'active'; ?>">Approved</a>
                <a href="?status=rejected" class="btn btn-outline-danger <?php if($statusFilter=='rejected') echo 'active'; ?>">Rejected</a>
                <a href="?status=partially_approved" class="btn btn-outline-info <?php if($statusFilter=='partially_approved') echo 'active'; ?>">Partially Approved</a>
            </div>
            <?php if (empty($requests)): ?>
                <div class="alert alert-info">No requests found.</div>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Organization</th>
                            <th>Requested</th>
                            <th>Approved</th>
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
                                <td><?php echo htmlspecialchars($r['org_name']); ?></td>
                                <td><?php echo $r['requested_quantity']; ?></td>
                                <td><?php echo $r['approved_quantity'] ?? '-'; ?></td>
                                <td><?php echo getStatusBadge($r['urgency']); ?></td>
                                <td><?php echo getStatusBadge($r['status']); ?></td>
                                <td><?php echo formatDateTime($r['requested_at']); ?></td>
                                <td><a href="request-details.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary">Manage</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>