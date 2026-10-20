
<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';

setNoCacheHeaders();

$adminEmail = 'admin123@gmail.com';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (!validatePassword($password)) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    if (!validateConfirm($password, $confirm)) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check whether the admin account exists.
        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? AND role = 'admin'"
        );
        $stmt->execute([$adminEmail]);
        $admin = $stmt->fetch();

        if (!$admin) {
            $_SESSION['error'] =
                'Admin account not found. Check the email and role in your database.';
        } else {
            // Use the same hashing method as reset-password.php.
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "UPDATE users
                 SET password_hash = ?, status = 'active'
                 WHERE id = ? AND role = 'admin'"
            );

            $stmt->execute([$hashed, $admin['id']]);

            // Verify the updated password hash.
            $stmt = $pdo->prepare(
                "SELECT password_hash FROM users WHERE id = ?"
            );
            $stmt->execute([$admin['id']]);
            $updatedAdmin = $stmt->fetch();

            if ($updatedAdmin &&
                password_verify($password, $updatedAdmin['password_hash'])) {
                $_SESSION['success'] =
                    'Admin password updated successfully. Please login.';
                header('Location: login.php');
                exit;
            } else {
                $_SESSION['error'] =
                    'Password update could not be verified.';
            }
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$pageTitle = 'Reset Admin Password - MediDonate';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center text-success">
                        Reset Admin Password
                    </h3>

                    <p class="text-muted text-center">
                        Admin: <?php echo htmlspecialchars($adminEmail); ?>
                    </p>

                    <?php echo displayAlerts(); ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                New Password
                            </label>
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="form-control"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                Confirm Password
                            </label>
                            <input type="password"
                                   name="confirm_password"
                                   id="confirm_password"
                                   class="form-control"
                                   required>
                        </div>

                        <button type="submit"
                                class="btn btn-success w-100">
                            Update Admin Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
