<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

$user = getCurrentUser();
checkAllExpiry();

// Statistics
$pendingMed = $pdo->query("SELECT COUNT(*) FROM medicines WHERE verification_status = 'pending'")->fetchColumn();
$pendingReq = $pdo->query("SELECT COUNT(*) FROM medicine_requests WHERE status = 'pending'")->fetchColumn();
$expiring = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_status = 'expiring_soon'")->fetchColumn();
$verified = $pdo->query("SELECT COUNT(*) FROM medicines WHERE verification_status = 'verified'")->fetchColumn();
$totalMed = $pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
$totalRejected = $pdo->query("SELECT COUNT(*) FROM medicines WHERE verification_status = 'rejected'")->fetchColumn();

$pageTitle = 'Verifier Dashboard';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Verifier Dashboard</h2>
            <?php echo displayAlerts(); ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <h5>Pending Medicines</h5>
                            <h2><?php echo $pendingMed; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <h5>Pending Requests</h5>
                            <h2><?php echo $pendingReq; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5>Expiring Soon</h5>
                            <h2><?php echo $expiring; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <h5>Verified</h5>
                            <h2><?php echo $verified; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-secondary">
                        <div class="card-body">
                            <h5>Total Medicines</h5>
                            <h2><?php echo $totalMed; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <h5>Rejected</h5>
                            <h2><?php echo $totalRejected; ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>