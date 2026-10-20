<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('donor');

$user = getCurrentUser();
$userId = $user['id'];
checkAllExpiry();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM medicines WHERE donor_id = ?");
$stmt->execute([$userId]);
$total = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as pending FROM medicines WHERE donor_id = ? AND verification_status = 'pending'");
$stmt->execute([$userId]);
$pending = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as verified FROM medicines WHERE donor_id = ? AND verification_status = 'verified'");
$stmt->execute([$userId]);
$verified = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as rejected FROM medicines WHERE donor_id = ? AND verification_status = 'rejected'");
$stmt->execute([$userId]);
$rejected = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM medicines m JOIN distributions d ON m.id = d.medicine_id WHERE m.donor_id = ? AND d.status = 'completed'");
$stmt->execute([$userId]);
$completed = $stmt->fetchColumn();

// Recent donations
$stmt = $pdo->prepare("SELECT id, name, expiry_date, verification_status, created_at FROM medicines WHERE donor_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recent = $stmt->fetchAll();

$pageTitle = 'Donor Dashboard';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid bg-light py-4" style="flex: 1;">
    <div class="row g-4">
        <!-- Sidebar Column -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white sticky-top" style="top: 100px; z-index: 10;">
                <?php include '../includes/sidebar.php'; ?>
            </div>
        </div>

        <!-- Main Content Column -->
        <div class="col-lg-9 px-lg-4">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? 'Donor'); ?>!</h2>
                    <p class="text-muted mb-0">Here is an overview of your medicine donations and their status.</p>
                </div>
                <a href="donate-medicine.php" class="btn btn-success rounded-3 fw-semibold px-4 py-2 shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i> Donate Medicine
                </a>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Statistics Grid -->
            <div class="row g-3 mb-4">
                <!-- Total Donations -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success-subtle text-success p-2 rounded-3 me-3">
                                    <i class="bi bi-capsule fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Total Donations</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($total); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Pending -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Pending Verification</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($pending); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Verified -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info-subtle text-info p-2 rounded-3 me-3">
                                    <i class="bi bi-check-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Verified</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($verified); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger-subtle text-danger p-2 rounded-3 me-3">
                                    <i class="bi bi-x-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Rejected</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($rejected); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Completed Distributions -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-secondary-subtle text-secondary p-2 rounded-3 me-3">
                                    <i class="bi bi-truck fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Completed Distributions</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($completed); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Donations Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0">Recent Donations</h5>
                        <a href="my-donations.php" class="text-success text-decoration-none fw-semibold small">View All <i class="bi bi-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7">
                                <tr>
                                    <th class="py-3 rounded-start">Medicine Name</th>
                                    <th class="py-3">Expiry Date</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 rounded-end">Donation Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent)): ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">You haven't made any donations yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recent as $d): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($d['name']); ?></td>
                                            <td><?php echo formatDate($d['expiry_date']); ?></td>
                                            <td><?php echo getStatusBadge($d['verification_status']); ?></td>
                                            <td><?php echo formatDate($d['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .transition-hover {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
    }
</style>

<?php include '../includes/footer.php'; ?>