<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('donor');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current = $_POST['current'];
    $new = $_POST['new'];
    $confirm = $_POST['confirm'];
    
    if (!password_verify($current, $user['password_hash'])) {
        $_SESSION['error'] = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $_SESSION['error'] = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        $_SESSION['success'] = 'Password changed successfully.';
    }
    header('Location: change-password.php');
    exit;
}

$pageTitle = 'Change Password';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Change Password</h2>
            <?php echo displayAlerts(); ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="current" class="form-label">Current Password</label>
                            <input type="password" name="current" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new" class="form-label">New Password</label>
                            <input type="password" name="new" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm" class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>