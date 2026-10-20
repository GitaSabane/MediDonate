<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('donor');
$user = getCurrentUser();

markNotificationsRead($user['id']);
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
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
                    <h2 class="fw-bold text-dark mb-1">Notifications</h2>
                    <p class="text-muted mb-0">Stay updated with your latest system alerts and donation activities.</p>
                </div>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Notifications Card Container -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bell-slash text-muted fs-1 mb-3 d-block"></i>
                            <p class="text-muted fw-semibold mb-0">No notifications found.</p>
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($notifications as $n): ?>
                                <div class="p-3 rounded-4 border <?php echo $n['is_read'] ? 'bg-white border-light' : 'bg-success-subtle border-success-subtle'; ?> transition-hover">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-white text-success p-2 rounded-3 me-3 shadow-sm">
                                                <i class="bi bi-bell-fill fs-5"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($n['title']); ?></h6>
                                                <p class="text-muted mb-0"><?php echo htmlspecialchars($n['message']); ?></p>
                                            </div>
                                        </div>
                                        <small class="text-muted text-nowrap ms-3 fs-7"><?php echo formatDateTime($n['created_at']); ?></small>
                                    </div>
                                    <?php if ($n['link']): ?>
                                        <div class="mt-2 text-end">
                                            <a href="<?php echo htmlspecialchars($n['link']); ?>" class="btn btn-sm btn-success rounded-3 px-3 fw-semibold">
                                                View <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
        transform: translateY(-2px);
        box-shadow: 0 .25rem .75rem rgba(0,0,0,.05)!important;
    }
</style>

<?php include '../includes/footer.php'; ?>