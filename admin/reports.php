<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

// Donation report
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN verification_status='verified' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN verification_status='pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN verification_status='rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN verification_status='verified' THEN quantity ELSE 0 END) as total_verified_qty
    FROM medicines WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$from, $to]);
$donationReport = $stmt->fetch();

// Medicine report - most donated
$stmt = $pdo->prepare("SELECT name, COUNT(*) as count, SUM(quantity) as total_qty 
                       FROM medicines 
                       WHERE DATE(created_at) BETWEEN ? AND ? 
                       GROUP BY name 
                       ORDER BY count DESC LIMIT 10");
$stmt->execute([$from, $to]);
$mostDonated = $stmt->fetchAll();

// Organization report - requests
$stmt = $pdo->prepare("SELECT o.org_name, 
                       COUNT(r.id) as total_requests,
                       SUM(CASE WHEN r.status='approved' OR r.status='partially_approved' THEN 1 ELSE 0 END) as approved,
                       SUM(CASE WHEN r.status='rejected' THEN 1 ELSE 0 END) as rejected,
                       SUM(CASE WHEN r.status='pending' THEN 1 ELSE 0 END) as pending,
                       SUM(r.requested_quantity) as total_requested
                       FROM organizations o 
                       LEFT JOIN medicine_requests r ON o.id = r.organization_id 
                       WHERE DATE(r.requested_at) BETWEEN ? AND ? OR r.requested_at IS NULL
                       GROUP BY o.id");
$stmt->execute([$from, $to]);
$orgReports = $stmt->fetchAll();

$pageTitle = 'Reports';
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
                    <h2 class="fw-bold text-dark mb-1">Reports</h2>
                    <p class="text-muted mb-0">Generate and analyze system donation and request summaries.</p>
                </div>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Date Filter Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted small">From Date</label>
                        <input type="date" name="from" class="form-control rounded-3" value="<?php echo htmlspecialchars($from); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-muted small">To Date</label>
                        <input type="date" name="to" class="form-control rounded-3" value="<?php echo htmlspecialchars($to); ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100 rounded-3 fw-semibold py-2">
                            <i class="bi bi-filter me-2"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Donation Summary -->
            <h5 class="fw-bold text-dark mb-3">Donation Summary</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary-subtle text-primary p-2 rounded-3 me-3">
                                    <i class="bi bi-capsule fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Total Donations</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($donationReport['total'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success-subtle text-success p-2 rounded-3 me-3">
                                    <i class="bi bi-check-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Approved</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($donationReport['approved'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-warning-subtle text-warning p-2 rounded-3 me-3">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Pending</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($donationReport['pending'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 transition-hover">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger-subtle text-danger p-2 rounded-3 me-3">
                                    <i class="bi bi-x-circle-fill fs-5"></i>
                                </div>
                                <h6 class="text-muted fw-semibold mb-0">Rejected</h6>
                            </div>
                            <h3 class="fw-bold text-dark mb-0"><?php echo number_format($donationReport['rejected'] ?? 0); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Most Donated Medicines Table -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3">Most Donated Medicines</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7">
                                <tr>
                                    <th class="py-3 rounded-start">Medicine Name</th>
                                    <th class="py-3">Donation Count</th>
                                    <th class="py-3 rounded-end">Total Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mostDonated)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No records found for this date range.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($mostDonated as $md): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($md['name']); ?></td>
                                            <td><span class="badge bg-primary-subtle text-primary fw-medium"><?php echo $md['count']; ?> times</span></td>
                                            <td class="fw-bold"><?php echo number_format($md['total_qty']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Organization Request Summary Table -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-body p-4">
                    <h5 class="fw-bold text-dark mb-3">Organization Request Summary</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7">
                                <tr>
                                    <th class="py-3 rounded-start">Organization</th>
                                    <th class="py-3">Total Requests</th>
                                    <th class="py-3">Approved</th>
                                    <th class="py-3">Rejected</th>
                                    <th class="py-3">Pending</th>
                                    <th class="py-3 rounded-end">Total Requested Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orgReports)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No organization data found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($orgReports as $or): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($or['org_name']); ?></td>
                                            <td><?php echo number_format($or['total_requests'] ?? 0); ?></td>
                                            <td><span class="text-success fw-semibold"><?php echo number_format($or['approved'] ?? 0); ?></span></td>
                                            <td><span class="text-danger fw-semibold"><?php echo number_format($or['rejected'] ?? 0); ?></span></td>
                                            <td><span class="text-warning fw-semibold"><?php echo number_format($or['pending'] ?? 0); ?></span></td>
                                            <td class="fw-bold"><?php echo number_format($or['total_requested'] ?? 0); ?></td>
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