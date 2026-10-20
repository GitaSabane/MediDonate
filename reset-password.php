<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';
setNoCacheHeaders();

$token = $_GET['token'] ?? '';
if (!$token) { $_SESSION['error'] = 'Invalid token.'; header('Location: forgot-password.php'); exit; }

$stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();
if (!$reset) { $_SESSION['error'] = 'Invalid or expired token.'; header('Location: forgot-password.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    $errors = [];
    if (!validatePassword($password)) $errors[] = 'Password must contain at least 8 characters.';
    if (!validateConfirm($password, $confirm)) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$hashed, $reset['email']]);
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        $_SESSION['success'] = 'Password reset successful. Please login.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$pageTitle = 'Reset Password - MediDonate';
include 'includes/header.php';
include 'includes/navbar.php';
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center text-success">Reset Password</h3>
                    <?php echo displayAlerts(); ?>
                    <form method="POST" id="resetForm" novalidate>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password (min 8 chars) *</label>
                            <input type="password" name="password" class="form-control" id="password" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>