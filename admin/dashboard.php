<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

checkAllExpiry();

// Statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalDonors = $pdo->query("SELECT COUNT(*) FROM users WHERE role='donor'")->fetchColumn();
$totalOrgs = $pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
$totalVerifiers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='verifier'")->fetchColumn();
$totalDonations = $pdo->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
$pendingVerifications = $pdo->query("SELECT COUNT(*) FROM medicines WHERE verification_status='pending'")->fetchColumn();
$approvedMedicines = $pdo->query("SELECT COUNT(*) FROM medicines WHERE verification_status='verified'")->fetchColumn();
$rejectedMedicines = $pdo->query("SELECT COUNT(*) FROM medicines WHERE verification_status='rejected'")->fetchColumn();
$availableMedicines = $pdo->query("SELECT SUM(available_quantity) FROM inventory")->fetchColumn();
$expiringMedicines = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_status='expiring_soon'")->fetchColumn();
$expiredMedicines = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_status='expired'")->fetchColumn();
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM medicine_requests WHERE status='pending'")->fetchColumn();
$completedDistributions = $pdo->query("SELECT COUNT(*) FROM distributions WHERE status='completed'")->fetchColumn();
$pendingOrgs = $pdo->query("SELECT COUNT(*) FROM organizations WHERE status='pending'")->fetchColumn();

$pageTitle = 'Admin Dashboard';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid bg-light py-4" style="flex: 1;">
    <div class="row g-4">
        <!-- Modern Styled Sidebar Column with corrected top offset -->
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
                    <h2 class="fw-bold text-dark mb-1">Admin Dashboard</h2>
                    <p class="text-muted mb-0">Overview of system metrics, users, and inventory status.</p>
                </div>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Statistics Grid -->
            <div class="row g-3 mb-4">
                <!-- Total Users -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3">
                                    <i class="bi bi-people-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Total Users</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($totalUsers); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Donors -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info-subtle text-info p-2 rounded-3 me-3">
                                    <i class="bi bi-heart-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Donors</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($totalDonors); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Organizations -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success-subtle text-success p-2 rounded-3 me-3">
                                    <i class="bi bi-building-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Organizations</h6>
                            </div>
                            <div class="d-flex justify-content-between align-items-baseline">
                                <h3 class="fw-bold text-dark mb-0"><?php echo number_format($totalOrgs); ?></h3>
                                <?php if ($pendingOrgs > 0): ?>
                                    <span class="badge bg-warning-subtle text-warning fw-medium"><?php echo $pendingOrgs; ?> pending</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verifiers -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-secondary-subtle text-secondary p-2 rounded-3 me-3">
                                    <i class="bi bi-shield-check-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Verifiers</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($totalVerifiers); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Donations -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                    <i class="bi bi-capsule fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Donations</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($totalDonations); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Pending Verification -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Pending Verif.</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($pendingVerifications); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Approved Medicines -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success-subtle text-success p-2 rounded-3 me-3">
                                    <i class="bi bi-check-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Approved</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($approvedMedicines); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Rejected Medicines -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger-subtle text-danger p-2 rounded-3 me-3">
                                    <i class="bi bi-x-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Rejected</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($rejectedMedicines); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Available Medicines -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info-subtle text-info p-2 rounded-3 me-3">
                                    <i class="bi bi-box-seam-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Available Qty</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($availableMedicines ?: 0); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Expiring Soon -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Expiring</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($expiringMedicines); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Expired -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger-subtle text-danger p-2 rounded-3 me-3">
                                    <i class="bi bi-slash-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Expired</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($expiredMedicines); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                    <i class="bi bi-journal-text fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Pending Req.</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($pendingRequests); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Completed Distributions -->
                <div class="col-md-4 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success-subtle text-success p-2 rounded-3 me-3">
                                    <i class="bi bi-truck fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Completed Dist.</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($completedDistributions); ?></h3>
                        </div>
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