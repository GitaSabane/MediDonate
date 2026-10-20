<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';

setNoCacheHeaders();

if (isLoggedIn()) {
    redirectBasedOnRole(getUserRole());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $errors = [];

    if (!validateRequired($email)) {
        $errors[] = 'Email is required.';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!validateRequired($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] !== 'active') {
                $_SESSION['error'] = 'Your account is not active.';
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];

                logAudit(
                    $user['id'],
                    'Login',
                    'user',
                    $user['id'],
                    'User logged in'
                );

                redirectBasedOnRole($user['role']);
            }
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    header('Location: login.php');
    exit;
}

$pageTitle = 'Login - MediDonate';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<style>
/* ================================
    MEDIDONATE CLEAN LOGIN
================================ */

body {
    position: relative;
    overflow-x: hidden;
    background-color: #f8f9fa;
}

/* Background Animation Container for Shapes */
.animated-bg-shapes {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    z-index: -1;
    pointer-events: none;
}

/* Individual Shape Styles */
.shape {
    position: absolute;
    opacity: 0.50;
    animation: floatShape 18s infinite linear;
}

/* Dotted Circles */
.shape-dot-grid {
    width: 120px;
    height: 120px;
    background-image: radial-gradient(#198754 25%, transparent 25%);
    background-size: 16px 16px;
    border-radius: 50%;
}

/* Dashed Borders */
.shape-dashed-circle {
    width: 100px;
    height: 100px;
    border: 3px dashed #0dcaf0;
    border-radius: 50%;
}

.shape-dashed-square {
    width: 90px;
    height: 90px;
    border: 3px dashed #ffc107;
    border-radius: 20px;
}

.shape-dashed-ring {
    width: 140px;
    height: 140px;
    border: 4px dashed #20c997;
    border-radius: 50%;
}

/* Floating Keyframes */
@keyframes floatShape {
    0% {
        transform: translateY(0px) translateX(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-140px) translateX(70px) rotate(180deg);
    }
    100% {
        transform: translateY(0px) translateX(0px) rotate(360deg);
    }
}

/* Random positioning and animation delays for shapes */
.shape:nth-child(1) { top: 10%; left: 5%; animation-duration: 22s; }
.shape:nth-child(2) { top: 25%; right: 8%; animation-duration: 15s; animation-delay: 2s; }
.shape:nth-child(3) { top: 50%; left: 2%; animation-duration: 20s; animation-delay: 4s; }
.shape:nth-child(4) { top: 65%; right: 5%; animation-duration: 25s; animation-delay: 1s; }
.shape:nth-child(5) { top: 85%; left: 15%; animation-duration: 17s; animation-delay: 3s; }
.shape:nth-child(6) { top: 40%; right: 20%; animation-duration: 19s; animation-delay: 5s; }

.login-wrapper {
    min-height: 80vh;
    padding: 40px 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
}

.login-card {
    width: 100%;
    max-width: 480px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-radius: 24px;
    padding: 40px;
    box-shadow: 0 15px 35px rgba(16, 92, 54, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.9);
}

.form-heading {
    color: #173e2b;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 8px;
    text-align: center;
}

.form-description {
    color: #718579;
    font-size: 14px;
    text-align: center;
    margin-bottom: 25px;
}

.form-label {
    color: #294c37;
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 8px;
}

.input-wrap {
    position: relative;
}

.input-wrap .form-control {
    height: 48px;
    border: 1px solid #dce9df;
    background: #fbfdfb;
    border-radius: 12px;
    padding: 12px 15px;
    color: #183c2c;
    font-size: 14px;
    box-shadow: none;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}

.input-wrap .form-control:focus {
    border-color: #168653;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(22, 134, 83, 0.1);
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #668373;
    padding: 4px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
}

.password-toggle:hover {
    color: #168653;
}

.form-options {
    display: flex;
    justify-content: flex-end;
    margin: 4px 0 20px;
}

.forgot-link {
    font-size: 12px;
    font-weight: 700;
    color: #168653;
    text-decoration: none;
}

.forgot-link:hover {
    text-decoration: underline;
}

.login-button {
    width: 100%;
    min-height: 48px;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(110deg, #168653, #087247);
    color: #fff;
    font-size: 14px;
    font-weight: 800;
    box-shadow: 0 8px 20px rgba(22, 134, 83, 0.18);
    transition: transform 0.2s, box-shadow 0.2s;
}

.login-button:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(22, 134, 83, 0.27);
}

.register-prompt {
    text-align: center;
    color: #829087;
    font-size: 13px;
    margin-top: 20px;
}

.register-prompt a {
    color: #168653;
    text-decoration: none;
    font-weight: 800;
}

.register-prompt a:hover {
    text-decoration: underline;
}
</style>

<!-- Animated Background Dashed & Dotted Shapes -->
<div class="animated-bg-shapes">
    <div class="shape shape-dot-grid" style="color: #198754;"></div>
    <div class="shape shape-dashed-circle"></div>
    <div class="shape shape-dashed-square"></div>
    <div class="shape shape-dot-grid" style="background-image: radial-gradient(#0d6efd 25%, transparent 25%);"></div>
    <div class="shape shape-dashed-ring"></div>
    <div class="shape shape-dashed-circle" style="border-color: #d63384;"></div>
</div>

<main class="login-wrapper">
    <div class="login-card">
        <h2 class="form-heading">Welcome Back</h2>
        <p class="form-description">Sign in to continue to MediDonate</p>

        <?php echo displayAlerts(); ?>

        <form method="POST" id="loginForm" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-wrap">
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        placeholder="you@example.com"
                        maxlength="254"
                        required
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['email'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>"
                    >
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Enter your password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Show password"
                    >
                        SHOW
                    </button>
                </div>
            </div>

            <div class="form-options">
                <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="login-button" id="loginButton">
                Sign In
            </button>
        </form>

        <div class="register-prompt">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</main>

<script>
(function () {
    'use strict';

    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');

    toggle.addEventListener('click', function () {
        const isHidden = password.type === 'password';
        password.type = isHidden ? 'text' : 'password';
        toggle.textContent = isHidden ? 'HIDE' : 'SHOW';
    });
})();
</script>

<?php include 'includes/footer.php'; ?>