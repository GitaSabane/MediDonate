<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT m.*, u.full_name as donor_name, u.email as donor_email 
                       FROM medicines m 
                       JOIN users u ON m.donor_id = u.id 
                       WHERE m.id = ?");
$stmt->execute([$id]);
$medicine = $stmt->fetch();
if (!$medicine) {
    $_SESSION['error'] = 'Medicine not found.';
    header('Location: pending-medicines.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $remarks = sanitize($_POST['remarks'] ?? '');
    $verifierId = $user['id'] ?? $_SESSION['user_id'];

    $pdo->beginTransaction();
    try {
        if ($action == 'approve') {
            $status = 'verified';
            $msg = "Your medicine '{$medicine['name']}' has been verified and approved.";
            $stmt = $pdo->prepare("UPDATE medicines SET verification_status = ?, verification_remarks = ?, approval_date = NOW() WHERE id = ?");
            $stmt->execute([$status, $remarks, $id]);
            adjustInventoryOnVerification($id);
            // Notify donor with absolute link
            createNotification($medicine['donor_id'], 'Donation Approved', $msg, 'success', '/medidonate/donor/my-donations.php');
            sendEmail($medicine['donor_email'], 'Donation Approved', "<p>$msg</p>");
            logAudit($verifierId, 'Approve Medicine', 'medicine', $id, "Approved medicine ID $id");
        } elseif ($action == 'reject') {
            $status = 'rejected';
            $msg = "Your medicine '{$medicine['name']}' has been rejected. Reason: $remarks";
            $stmt = $pdo->prepare("UPDATE medicines SET verification_status = ?, rejection_reason = ? WHERE id = ?");
            $stmt->execute([$status, $remarks, $id]);
            createNotification($medicine['donor_id'], 'Donation Rejected', $msg, 'danger', '/medidonate/donor/my-donations.php');
            sendEmail($medicine['donor_email'], 'Donation Rejected', "<p>$msg</p>");
            logAudit($verifierId, 'Reject Medicine', 'medicine', $id, "Rejected medicine ID $id. Reason: $remarks");
        } elseif ($action == 'more_info') {
            $status = 'more_info';
            $msg = "More information is needed for your medicine '{$medicine['name']}'. Please provide details.";
            $stmt = $pdo->prepare("UPDATE medicines SET verification_status = ?, verification_remarks = ? WHERE id = ?");
            $stmt->execute([$status, $remarks, $id]);
            createNotification($medicine['donor_id'], 'More Info Needed', $msg, 'info', '/medidonate/donor/donation-details.php?id='.$id);
            sendEmail($medicine['donor_email'], 'More Information Needed', "<p>$msg</p>");
            logAudit($verifierId, 'Request More Info', 'medicine', $id, "Requested more info for medicine ID $id");
        }
        $pdo->commit();
        $_SESSION['success'] = "Action '$action' performed successfully.";
        header('Location: pending-medicines.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Operation failed: ' . $e->getMessage();
    }
}

$pageTitle = 'Review Medicine';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Review Medicine</h2>
            <?php echo displayAlerts(); ?>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($medicine['name']); ?></p>
                            <p><strong>Type:</strong> <?php echo $medicine['type']; ?></p>
                            <p><strong>Strength:</strong> <?php echo $medicine['strength']; ?></p>
                            <p><strong>Quantity:</strong> <?php echo $medicine['quantity']; ?> <?php echo $medicine['unit']; ?></p>
                            <p><strong>Expiry:</strong> <?php echo formatDate($medicine['expiry_date']); ?></p>
                            <p><strong>Batch:</strong> <?php echo $medicine['batch_number']; ?></p>
                            <p><strong>Manufacturer:</strong> <?php echo $medicine['manufacturer']; ?></p>
                            <p><strong>Packaging:</strong> <?php echo $medicine['packaging_condition']; ?></p>
                            <p><strong>Storage:</strong> <?php echo $medicine['storage_condition']; ?></p>
                            <p><strong>Donor:</strong> <?php echo htmlspecialchars($medicine['donor_name']); ?></p>
                            <p><strong>Submitted:</strong> <?php echo formatDateTime($medicine['created_at']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($medicine['images']): ?>
                                <p><strong>Images:</strong></p>
                                <?php foreach (explode(',', $medicine['images']) as $img): ?>
                                    <img src="../uploads/medicines/<?php echo $img; ?>" width="150" class="m-1 img-thumbnail">
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks / Reason</label>
                            <textarea name="remarks" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="action" value="approve" class="btn btn-success">Approve</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">Reject</button>
                        <button type="submit" name="action" value="more_info" class="btn btn-info">Request More Info</button>
                        <a href="pending-medicines.php" class="btn btn-secondary">Back</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>