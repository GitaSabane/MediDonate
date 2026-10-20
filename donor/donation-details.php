<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('donor');
$user = getCurrentUser();

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ? AND donor_id = ?");
$stmt->execute([$id, $user['id']]);
$donation = $stmt->fetch();
if (!$donation) {
    $_SESSION['error'] = 'Donation not found.';
    header('Location: my-donations.php');
    exit;
}

$pageTitle = 'Donation Details';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Donation Details</h2>
            <div class="card">
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($donation['name']); ?></p>
                    <p><strong>Type:</strong> <?php echo $donation['type']; ?></p>
                    <p><strong>Strength:</strong> <?php echo $donation['strength']; ?></p>
                    <p><strong>Quantity:</strong> <?php echo $donation['quantity']; ?> <?php echo $donation['unit']; ?></p>
                    <p><strong>Manufacturing Date:</strong> <?php echo formatDate($donation['manufacturing_date']); ?></p>
                    <p><strong>Expiry Date:</strong> <?php echo formatDate($donation['expiry_date']); ?></p>
                    <p><strong>Batch:</strong> <?php echo $donation['batch_number']; ?></p>
                    <p><strong>Manufacturer:</strong> <?php echo $donation['manufacturer']; ?></p>
                    <p><strong>Packaging:</strong> <?php echo $donation['packaging_condition']; ?></p>
                    <p><strong>Storage:</strong> <?php echo $donation['storage_condition']; ?></p>
                    <p><strong>Verification Status:</strong> <?php echo getStatusBadge($donation['verification_status']); ?></p>
                    <?php if ($donation['images']): ?>
                        <p><strong>Images:</strong></p>
                        <?php foreach (explode(',', $donation['images']) as $img): ?>
                            <img src="../uploads/medicines/<?php echo $img; ?>" width="100" class="m-1">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>