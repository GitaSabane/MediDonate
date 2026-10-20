<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('verifier');

$user = getCurrentUser();
markNotificationsRead($user['id']);

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Notifications</h2>
            <?php if (empty($notifications)): ?>
                <div class="alert alert-info">No notifications.</div>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($notifications as $n): ?>
                        <li class="list-group-item <?php echo $n['is_read'] ? '' : 'fw-bold'; ?>">
                            <strong><?php echo htmlspecialchars($n['title']); ?></strong><br>
                            <?php echo htmlspecialchars($n['message']); ?>
                            <small class="text-muted float-end"><?php echo formatDateTime($n['created_at']); ?></small>
                            <?php if ($n['link']): ?>
                                <a href="<?php echo $n['link']; ?>" class="btn btn-sm btn-link">View</a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>