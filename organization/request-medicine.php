<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ?");
$stmt->execute([$user['id']]);
$org = $stmt->fetch();
if ($org['status'] != 'approved') { $_SESSION['error'] = 'Organization not approved.'; header('Location: /medidonate/index.php'); exit; }

$medicineId = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT m.*, i.available_quantity FROM medicines m JOIN inventory i ON m.id = i.medicine_id WHERE m.id = ? AND m.verification_status = 'verified' AND m.expiry_status = 'valid'");
$stmt->execute([$medicineId]);
$medicine = $stmt->fetch();
if (!$medicine) { $_SESSION['error'] = 'Medicine not available.'; header('Location: medicines.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $requestedQty = (int)$_POST['quantity'];
    $purpose = sanitize($_POST['purpose']);
    $urgency = sanitize($_POST['urgency']);
    $additional = sanitize($_POST['additional']);

    $errors = [];
    if (!validatePositive($requestedQty)) $errors[] = 'Quantity must be greater than 0.';
    if ($requestedQty > $medicine['available_quantity']) $errors[] = 'Requested quantity exceeds available quantity.';
    if (!validateRequired($purpose)) $errors[] = 'Purpose is required.';
    if (!in_array($urgency, ['Normal','Urgent','Very Urgent'])) $errors[] = 'Invalid urgency.';

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO medicine_requests (organization_id, medicine_id, requested_quantity, purpose, urgency, additional_info) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$org['id'], $medicineId, $requestedQty, $purpose, $urgency, $additional]);
            $reqId = $pdo->lastInsertId();
            $notifyUsers = $pdo->query("SELECT id FROM users WHERE role IN ('verifier','admin')")->fetchAll();
            foreach ($notifyUsers as $nu) {
                createNotification($nu['id'], 'New Medicine Request', "Organization {$org['org_name']} requested {$requestedQty} of {$medicine['name']}.", 'info', '/medidonate/verifier/requests.php');
            }
            createNotification($user['id'], 'Request Submitted', "Your request for {$medicine['name']} has been submitted.", 'success', '/medidonate/organization/my-requests.php');
            logAudit($user['id'], 'Request Submitted', 'medicine_request', $reqId, "Requested {$requestedQty} of {$medicine['name']}");
            $pdo->commit();
            $_SESSION['success'] = 'Request submitted successfully.';
            header('Location: my-requests.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to submit request: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$pageTitle = 'Request Medicine';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-md-9 p-4">
            <h2>Request Medicine</h2>
            <?php echo displayAlerts(); ?>
            <div class="card">
                <div class="card-body">
                    <h5><?php echo htmlspecialchars($medicine['name']); ?></h5>
                    <p>Available Quantity: <strong><?php echo $medicine['available_quantity']; ?></strong> <?php echo $medicine['unit']; ?></p>
                    <form method="POST" id="requestForm" novalidate>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Requested Quantity *</label>
                            <input type="number" name="quantity" class="form-control" id="quantity" min="1" max="<?php echo $medicine['available_quantity']; ?>" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="purpose" class="form-label">Purpose *</label>
                            <textarea name="purpose" class="form-control" id="purpose" rows="3" required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="urgency" class="form-label">Urgency</label>
                            <select name="urgency" class="form-select" id="urgency">
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                                <option value="Very Urgent">Very Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="additional" class="form-label">Additional Information</label>
                            <textarea name="additional" class="form-control" id="additional" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Submit Request</button>
                        <a href="medicines.php" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>