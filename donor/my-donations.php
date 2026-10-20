<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('donor');

$user = getCurrentUser();
$userId = $user['id'];

$stmt = $pdo->prepare("SELECT * FROM medicines WHERE donor_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$donations = $stmt->fetchAll();

$pageTitle = 'My Donations';
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
                    <h2 class="fw-bold text-dark mb-1">My Donations</h2>
                    <p class="text-muted mb-0">Track all the medicines you have donated and their current verification status.</p>
                </div>
                <a href="donate-medicine.php" class="btn btn-success rounded-3 fw-semibold px-4 py-2 shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i> Donate Medicine
                </a>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Donations Table Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7">
                                <tr>
                                    <th class="py-3 rounded-start">Medicine Name</th>
                                    <th class="py-3">Quantity</th>
                                    <th class="py-3">Expiry Date</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 rounded-end text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($donations)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">You haven't made any donations yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($donations as $d): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($d['name']); ?></td>
                                            <td><?php echo number_format($d['quantity']); ?></td>
                                            <td><?php echo formatDate($d['expiry_date']); ?></td>
                                            <td><?php echo getStatusBadge($d['verification_status']); ?></td>
                                            <td class="text-end">
                                                <a href="donation-details.php?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-outline-info rounded-3 px-3">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </a>
                                            </td>
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

<?php include '../includes/footer.php'; ?>