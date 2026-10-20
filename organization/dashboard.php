<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ?");
$stmt->execute([$user['id']]);
$org = $stmt->fetch();

// Handle unapproved status nicely without broken redirects
if (!$org || $org['status'] != 'approved') {
    $pageTitle = 'Organization Pending Approval';
    include '../includes/header.php';
    include '../includes/navbar.php';
    ?>
    <div class="container-fluid bg-light py-5" style="flex: 1;">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                    <div class="mb-3 text-warning">
                        <i class="bi bi-hourglass-split display-4"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Approval Pending</h3>
                    <p class="text-muted mb-4">Your organization account is currently pending review and verification by the administrator. Please check back later.</p>
                    <a href="../index.php" class="btn btn-success rounded-3 fw-semibold px-4 py-2">Return Home</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    include '../includes/footer.php';
    exit;
}

checkAllExpiry();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_requests WHERE organization_id = ?");
$stmt->execute([$org['id']]);
$totalRequests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_requests WHERE organization_id = ? AND status = 'pending'");
$stmt->execute([$org['id']]);
$pendingRequests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM medicine_requests WHERE organization_id = ? AND status IN ('approved','partially_approved')");
$stmt->execute([$org['id']]);
$approvedRequests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM distributions d 
                       JOIN medicine_requests r ON d.request_id = r.id 
                       WHERE r.organization_id = ? AND d.status = 'completed'");
$stmt->execute([$org['id']]);
$completedDistributions = $stmt->fetchColumn();

$pageTitle = 'Organization Dashboard';
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container-fluid bg-light py-4" style="flex: 1;">
    <div class="row g-4">
        <!-- Modern Sidebar Column -->
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
                    <h2 class="fw-bold text-dark mb-1">Welcome, <?php echo htmlspecialchars($org['org_name']); ?>!</h2>
                    <p class="text-muted mb-0">Overview of your medicine requests and distribution activities.</p>
                </div>
                <a href="medicines.php" class="btn btn-success rounded-3 fw-semibold px-4 py-2 shadow-sm">
                    <i class="bi bi-search me-2"></i> Search Medicines
                </a>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Statistics Grid -->
            <div class="row g-3 mb-4">
                <!-- Total Requests -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3">
                                    <i class="bi bi-journal-text fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Total Requests</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($totalRequests); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Pending Requests -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Pending</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($pendingRequests); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Approved Requests -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info-subtle text-info p-2 rounded-3 me-3">
                                    <i class="bi bi-check-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Approved</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($approvedRequests); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Completed Distributions -->
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success-subtle text-success p-2 rounded-3 me-3">
                                    <i class="bi bi-truck fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Completed</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($completedDistributions); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Organization Details Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3">Organization Profile Summary</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="text-muted small fw-semibold d-block">Organization Name</span>
                            <span class="text-dark fw-semibold"><?php echo htmlspecialchars($org['org_name']); ?></span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small fw-semibold d-block">Account Status</span>
                            <div class="mt-1"><?php echo getStatusBadge($org['status']); ?></div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small fw-semibold d-block">Registration Number</span>
                            <span class="text-dark fw-semibold"><?php echo htmlspecialchars($org['registration_number'] ?? 'N/A'); ?></span>
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