<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';
setNoCacheHeaders();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    if (!validateRequired($email)) {
        $_SESSION['error'] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $_SESSION['error'] = 'Enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);
            $resetLink = "http://localhost/medidonate/reset-password.php?token=$token";
            sendEmail($email, 'Password Reset Request', "<p>Click <a href='$resetLink'>here</a> to reset your password.</p>");
            $_SESSION['success'] = 'Password reset link sent to your email.';
        } else {
            $_SESSION['error'] = 'Email not found.';
        }
    }
    header('Location: forgot-password.php');
    exit;
}

$pageTitle = 'Forgot Password - MediDonate';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container-fluid bg-light d-flex align-items-center justify-content-center py-5" style="flex: 1;">
    <div class="row w-100 justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <div class="card-body p-2">
                    <div class="text-center mb-4">
                        <div class="bg-success-subtle text-success d-inline-flex p-3 rounded-3 mb-3">
                            <i class="bi bi-key-fill fs-3"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">Forgot Password</h3>
                        <p class="text-muted small mb-0">Enter your email address to receive a password reset link.</p>
                    </div>

                    <?php echo displayAlerts(); ?>

                    <form method="POST" id="forgotForm" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold text-muted small">Email Address *</label>
                            <input type="email" name="email" class="form-control rounded-3 py-2" id="email" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 rounded-3 fw-semibold py-2 shadow-sm">Send Reset Link</button>
                    </form>

                    <div class="mt-4 text-center">
                        <a href="login.php" class="text-success text-decoration-none fw-semibold small">
                            <i class="bi bi-arrow-left me-1"></i> Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>