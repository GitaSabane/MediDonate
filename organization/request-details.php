<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ?");
$stmt->execute([$user['id']]);
$org = $stmt->fetch();

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT r.*, m.name as medicine_name, m.unit, m.strength, m.manufacturer 
                       FROM medicine_requests r 
                       JOIN medicines m ON r.medicine_id = m.id 
                       WHERE r.id = ? AND r.organization_id = ?");
$stmt->execute([$id, $org['id']]);
$request = $stmt->fetch();
if (!$request) {
    $_SESSION['error'] = 'Request not found.';
    header('Location: my-requests.php');
    exit;
}

// Fetch distribution details if any
$stmt = $pdo->prepare("SELECT * FROM distributions WHERE request_id = ?");
$stmt->execute([$id]);
$distribution = $stmt->fetch();

$pageTitle = 'Request Details';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Request Details</h2>
            <div class="card">
                <div class="card-body">
                    <p><strong>Medicine:</strong> <?php echo htmlspecialchars($request['medicine_name']); ?></p>
                    <p><strong>Strength:</strong> <?php echo $request['strength']; ?></p>
                    <p><strong>Manufacturer:</strong> <?php echo $request['manufacturer']; ?></p>
                    <p><strong>Requested Quantity:</strong> <?php echo $request['requested_quantity']; ?> <?php echo $request['unit']; ?></p>
                    <p><strong>Approved Quantity:</strong> <?php echo $request['approved_quantity'] ?? 'Pending'; ?></p>
                    <p><strong>Purpose:</strong> <?php echo nl2br(htmlspecialchars($request['purpose'])); ?></p>
                    <p><strong>Urgency:</strong> <?php echo getStatusBadge($request['urgency']); ?></p>
                    <p><strong>Status:</strong> <?php echo getStatusBadge($request['status']); ?></p>
                    <p><strong>Requested on:</strong> <?php echo formatDateTime($request['requested_at']); ?></p>
                    <?php if ($request['rejection_reason']): ?>
                        <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?></p>
                    <?php endif; ?>

                    <?php if ($distribution): ?>
                        <hr>
                        <h5>Distribution Details</h5>
                        <p><strong>Status:</strong> <?php echo getStatusBadge($distribution['status']); ?></p>
                        <p><strong>Quantity:</strong> <?php echo $distribution['quantity']; ?></p>
                        <p><strong>Distribution Date:</strong> <?php echo $distribution['distribution_date'] ? formatDateTime($distribution['distribution_date']) : 'N/A'; ?></p>
                        <p><strong>Method:</strong> <?php echo $distribution['distribution_method']; ?></p>
                        <?php if ($distribution['status'] == 'awaiting_distribution'): ?>
                            <form method="POST" action="confirm-receipt.php">
                                <input type="hidden" name="distribution_id" value="<?php echo $distribution['id']; ?>">
                                <button type="submit" class="btn btn-success">Confirm Receipt</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="my-requests.php" class="btn btn-secondary mt-3">Back to Requests</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>