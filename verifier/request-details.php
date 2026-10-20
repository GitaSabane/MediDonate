<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT r.*, m.name as medicine_name, m.unit, m.id as medicine_id,
                              o.org_name, o.id as org_id,
                              i.available_quantity
                       FROM medicine_requests r 
                       JOIN medicines m ON r.medicine_id = m.id 
                       JOIN organizations o ON r.organization_id = o.id 
                       LEFT JOIN inventory i ON m.id = i.medicine_id
                       WHERE r.id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();
if (!$request) {
    $_SESSION['error'] = 'Request not found.';
    header('Location: requests.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $remarks = sanitize($_POST['remarks'] ?? '');
    $approvedQty = (int)($_POST['approved_quantity'] ?? 0);
    $verifierId = $_SESSION['user_id'];

    $pdo->beginTransaction();
    try {
        if ($action == 'approve') {
            $status = 'approved';
            if ($approvedQty > $request['available_quantity']) {
                throw new Exception('Approved quantity exceeds available inventory.');
            }
            if ($approvedQty <= 0) {
                throw new Exception('Approved quantity must be positive.');
            }
            $stmt = $pdo->prepare("UPDATE medicine_requests SET status = ?, approved_quantity = ?, processed_at = NOW(), processed_by = ?, rejection_reason = ? WHERE id = ?");
            $stmt->execute([$status, $approvedQty, $verifierId, $remarks, $id]);
            adjustInventoryOnRequestApproval($id, $approvedQty);
            $stmt = $pdo->prepare("INSERT INTO distributions (request_id, medicine_id, organization_id, quantity, status) VALUES (?, ?, ?, ?, 'awaiting_distribution')");
            $stmt->execute([$id, $request['medicine_id'], $request['org_id'], $approvedQty]);
            // Get org user id
            $orgUser = $pdo->prepare("SELECT user_id FROM organizations WHERE id = ?");
            $orgUser->execute([$request['org_id']]);
            $orgUserId = $orgUser->fetchColumn();
            createNotification($orgUserId, 'Request Approved', "Your request for {$request['medicine_name']} has been approved for {$approvedQty} units.", 'success', '/medidonate/organization/my-requests.php');
            sendEmail($orgUserEmail ?? '', 'Request Approved', "<p>Your request for {$request['medicine_name']} has been approved.</p>");
            logAudit($verifierId, 'Approve Request', 'medicine_request', $id, "Approved request ID $id for qty $approvedQty");
        } elseif ($action == 'reject') {
            $status = 'rejected';
            $stmt = $pdo->prepare("UPDATE medicine_requests SET status = ?, processed_at = NOW(), processed_by = ?, rejection_reason = ? WHERE id = ?");
            $stmt->execute([$status, $verifierId, $remarks, $id]);
            $orgUser = $pdo->prepare("SELECT user_id FROM organizations WHERE id = ?");
            $orgUser->execute([$request['org_id']]);
            $orgUserId = $orgUser->fetchColumn();
            createNotification($orgUserId, 'Request Rejected', "Your request for {$request['medicine_name']} has been rejected. Reason: $remarks", 'danger', '/medidonate/organization/my-requests.php');
            logAudit($verifierId, 'Reject Request', 'medicine_request', $id, "Rejected request ID $id. Reason: $remarks");
        } elseif ($action == 'partial') {
            $status = 'partially_approved';
            if ($approvedQty <= 0 || $approvedQty > $request['available_quantity']) {
                throw new Exception('Invalid approved quantity.');
            }
            $stmt = $pdo->prepare("UPDATE medicine_requests SET status = ?, approved_quantity = ?, processed_at = NOW(), processed_by = ?, rejection_reason = ? WHERE id = ?");
            $stmt->execute([$status, $approvedQty, $verifierId, $remarks, $id]);
            adjustInventoryOnRequestApproval($id, $approvedQty);
            $stmt = $pdo->prepare("INSERT INTO distributions (request_id, medicine_id, organization_id, quantity, status) VALUES (?, ?, ?, ?, 'awaiting_distribution')");
            $stmt->execute([$id, $request['medicine_id'], $request['org_id'], $approvedQty]);
            $orgUser = $pdo->prepare("SELECT user_id FROM organizations WHERE id = ?");
            $orgUser->execute([$request['org_id']]);
            $orgUserId = $orgUser->fetchColumn();
            createNotification($orgUserId, 'Request Partially Approved', "Your request for {$request['medicine_name']} has been partially approved for {$approvedQty} units (requested {$request['requested_quantity']}).", 'info', '/medidonate/organization/my-requests.php');
            logAudit($verifierId, 'Partial Approve Request', 'medicine_request', $id, "Partially approved request ID $id for qty $approvedQty");
        }
        $pdo->commit();
        $_SESSION['success'] = "Request $action successful.";
        header('Location: requests.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Operation failed: ' . $e->getMessage();
    }
}

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
            <?php echo displayAlerts(); ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Medicine:</strong> <?php echo htmlspecialchars($request['medicine_name']); ?></p>
                    <p><strong>Organization:</strong> <?php echo htmlspecialchars($request['org_name']); ?></p>
                    <p><strong>Requested Quantity:</strong> <?php echo $request['requested_quantity']; ?> <?php echo $request['unit']; ?></p>
                    <p><strong>Available Inventory:</strong> <?php echo $request['available_quantity']; ?> <?php echo $request['unit']; ?></p>
                    <p><strong>Urgency:</strong> <?php echo getStatusBadge($request['urgency']); ?></p>
                    <p><strong>Purpose:</strong> <?php echo nl2br(htmlspecialchars($request['purpose'])); ?></p>
                    <p><strong>Additional Info:</strong> <?php echo nl2br(htmlspecialchars($request['additional_info'])); ?></p>
                    <p><strong>Current Status:</strong> <?php echo getStatusBadge($request['status']); ?></p>
                    <p><strong>Requested on:</strong> <?php echo formatDateTime($request['requested_at']); ?></p>
                    <?php if ($request['status'] == 'pending'): ?>
                        <hr>
                        <h5>Process Request</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="approved_quantity" class="form-label">Approved Quantity</label>
                                <input type="number" name="approved_quantity" class="form-control" min="1" max="<?php echo $request['available_quantity']; ?>" value="<?php echo min($request['requested_quantity'], $request['available_quantity']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks / Reason</label>
                                <textarea name="remarks" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                            <button type="submit" name="action" value="partial" class="btn btn-info">Partial Approve</button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                            <a href="requests.php" class="btn btn-secondary">Back</a>
                        </form>
                    <?php else: ?>
                        <p><strong>Processed on:</strong> <?php echo formatDateTime($request['processed_at']); ?></p>
                        <p><strong>Approved Quantity:</strong> <?php echo $request['approved_quantity'] ?? '-'; ?></p>
                        <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason'] ?? ''); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>