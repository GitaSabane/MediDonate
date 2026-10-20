<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

// Handle Add Verifier Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_verifier'])) {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $password = $_POST['password'];
    $csrf = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf)) { 
        $_SESSION['error'] = 'CSRF validation failed.'; 
    } else {
        $errors = [];
        if (!validateRequired($full_name)) $errors[] = 'Full name is required.';
        if (!validateRequired($email)) $errors[] = 'Email is required.';
        elseif (!validateEmail($email)) $errors[] = 'Invalid email address.';
        if ($phone && !validatePhone($phone)) $errors[] = 'Invalid phone number.';
        if (!validatePassword($password)) $errors[] = 'Password must contain at least 8 characters.';
        
        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email already exists.';
        
        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, address, city, status) VALUES (?, ?, ?, ?, 'verifier', ?, ?, 'active')");
            $stmt->execute([$full_name, $email, $phone, $hashed, $address, $city]);
            
            logAudit($_SESSION['user_id'], 'Add Verifier', 'user', $pdo->lastInsertId(), "Added verifier $email");
            $_SESSION['success'] = 'Verifier added successfully.';
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
    }
    header('Location: verifiers.php');
    exit;
}

// Fetch all verifiers
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'verifier' ORDER BY created_at DESC");
$verifiers = $stmt->fetchAll();

$pageTitle = 'Manage Verifiers';
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
                    <h2 class="fw-bold text-dark mb-1">Manage Verifiers</h2>
                    <p class="text-muted mb-0">Add and manage authorized verification personnel.</p>
                </div>
                <button type="button" class="btn btn-success rounded-3 fw-semibold px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addVerifierModal">
                    <i class="bi bi-person-plus-fill me-2"></i> Add Verifier
                </button>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Verifiers List Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-uppercase fs-7">
                                <tr>
                                    <th class="py-3 rounded-start">Full Name</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Phone</th>
                                    <th class="py-3">City</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 rounded-end text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($verifiers)): ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No verifiers found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($verifiers as $v): ?>
                                        <tr>
                                            <td class="fw-semibold text-dark"><?php echo htmlspecialchars($v['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($v['email']); ?></td>
                                            <td><?php echo htmlspecialchars($v['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($v['city'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success fw-medium px-2 py-1">
                                                    <?php echo ucfirst($v['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="edit-user.php?id=<?php echo $v['id']; ?>" class="btn btn-sm btn-outline-primary rounded-3 px-2 py-1">
                                                    <i class="bi bi-pencil-fill"></i>
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

<!-- Add Verifier Modal -->
<div class="modal fade" id="addVerifierModal" tabindex="-1" aria-labelledby="addVerifierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="addVerifierModalLabel">Add New Verifier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Full Name</label>
                        <input type="text" name="full_name" class="form-control rounded-3 py-2" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Email Address</label>
                        <input type="email" name="email" class="form-control rounded-3 py-2" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Phone Number</label>
                        <input type="text" name="phone" class="form-control rounded-3 py-2">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">City</label>
                        <input type="text" name="city" class="form-control rounded-3 py-2">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Address</label>
                        <textarea name="address" class="form-control rounded-3" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small">Password (Min 8 characters)</label>
                        <input type="password" name="password" class="form-control rounded-3 py-2" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_verifier" class="btn btn-success rounded-3 px-4 fw-semibold">Save Verifier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>