<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) { 
        $_SESSION['error'] = 'CSRF validation failed.'; 
    } else {
        $site_name = sanitize($_POST['site_name']);
        $site_email = sanitize($_POST['site_email']);
        $expiry_warning = (int)$_POST['expiry_warning_days'];
        
        $errors = [];
        if (!validateRequired($site_name)) $errors[] = 'Site name is required.';
        if (!validateRequired($site_email)) $errors[] = 'Site email is required.';
        elseif (!validateEmail($site_email)) $errors[] = 'Invalid email address.';
        if ($expiry_warning < 1 || $expiry_warning > 365) $errors[] = 'Expiry warning days must be between 1 and 365.';
        
        if (empty($errors)) {
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'site_name'")->execute([$site_name]);
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'site_email'")->execute([$site_email]);
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'expiry_warning_days'")->execute([$expiry_warning]);
            
            logAudit($_SESSION['user_id'], 'Update Settings', 'system', 0, 'Updated system settings');
            $_SESSION['success'] = 'Settings updated successfully.';
        } else {
            $_SESSION['error'] = implode('<br>', $errors);
        }
        
        header('Location: settings.php');
        exit;
    }
}

// Fetch current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'System Settings';
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
                    <h2 class="fw-bold text-dark mb-1">System Settings</h2>
                    <p class="text-muted mb-0">Manage core system configurations and operational parameters.</p>
                </div>
            </div>

            <?php echo displayAlerts(); ?>

            <!-- Settings Form Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">Site Name</label>
                            <input type="text" name="site_name" class="form-control rounded-3 py-2" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted small">Site Email</label>
                            <input type="email" name="site_email" class="form-control rounded-3 py-2" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-muted small">Expiry Warning Threshold (Days)</label>
                            <input type="number" name="expiry_warning_days" class="form-control rounded-3 py-2" min="1" max="365" value="<?php echo (int)($settings['expiry_warning_days'] ?? 30); ?>" required>
                            <div class="form-text text-muted">Number of days before expiration to flag medicines as expiring soon (1 - 365).</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success rounded-3 fw-semibold px-4 py-2">
                                <i class="bi bi-save me-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>