<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT m.*, i.available_quantity, u.full_name as donor_name, u.email as donor_email 
                       FROM medicines m 
                       JOIN inventory i ON m.id = i.medicine_id 
                       JOIN users u ON m.donor_id = u.id 
                       WHERE m.id = ? AND m.verification_status = 'verified' AND m.expiry_status = 'valid'");
$stmt->execute([$id]);
$medicine = $stmt->fetch();
if (!$medicine) {
    $_SESSION['error'] = 'Medicine not available.';
    header('Location: medicines.php');
    exit;
}

$pageTitle = 'Medicine Details';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2><?php echo htmlspecialchars($medicine['name']); ?></h2>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Type:</strong> <?php echo $medicine['type']; ?></p>
                            <p><strong>Strength:</strong> <?php echo $medicine['strength']; ?></p>
                            <p><strong>Quantity Available:</strong> <?php echo $medicine['available_quantity']; ?> <?php echo $medicine['unit']; ?></p>
                            <p><strong>Expiry Date:</strong> <?php echo formatDate($medicine['expiry_date']); ?></p>
                            <p><strong>Batch Number:</strong> <?php echo $medicine['batch_number']; ?></p>
                            <p><strong>Manufacturer:</strong> <?php echo $medicine['manufacturer']; ?></p>
                            <p><strong>Packaging Condition:</strong> <?php echo $medicine['packaging_condition']; ?></p>
                            <p><strong>Storage Condition:</strong> <?php echo $medicine['storage_condition']; ?></p>
                            <p><strong>Donor:</strong> <?php echo htmlspecialchars($medicine['donor_name']); ?></p>
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
                    <div class="mt-3">
                        <a href="request-medicine.php?id=<?php echo $medicine['id']; ?>" class="btn btn-success">Request this Medicine</a>
                        <a href="medicines.php" class="btn btn-secondary">Back to Search</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>