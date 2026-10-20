<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('donor');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $type = sanitize($_POST['type']);
    $strength = sanitize($_POST['strength']);
    $quantity = (int)$_POST['quantity'];
    $unit = sanitize($_POST['unit']);
    $manufacturing_date = $_POST['manufacturing_date'];
    $expiry_date = $_POST['expiry_date'];
    $batch = sanitize($_POST['batch']);
    $manufacturer = sanitize($_POST['manufacturer']);
    $packaging = sanitize($_POST['packaging']);
    $storage = sanitize($_POST['storage']);

    // Server-side validation
    $errors = [];
    if (!validateRequired($name)) $errors[] = 'Medicine name is required.';
    if (!validatePositive($quantity)) $errors[] = 'Quantity must be greater than 0.';
    if (!validateRequired($expiry_date)) $errors[] = 'Expiry date is required.';
    elseif (!validateDate($expiry_date)) $errors[] = 'Invalid expiry date format.';
    elseif (!validateExpiry($expiry_date)) $errors[] = 'This medicine is expired and cannot be donated.';
    if ($manufacturing_date && !validateDate($manufacturing_date)) $errors[] = 'Invalid manufacturing date.';
    if ($manufacturing_date && $expiry_date && strtotime($manufacturing_date) > strtotime($expiry_date)) $errors[] = 'Manufacturing date cannot be after expiry date.';
    // Warn if expiring soon (but not an error)
    $expiringSoon = false;
    if (validateDate($expiry_date) && strtotime($expiry_date) <= strtotime('+30 days')) $expiringSoon = true;

    // File upload validation
    $images = [];
    if (isset($_FILES['images'])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {
            if ($_FILES['images']['error'][$key] == UPLOAD_ERR_OK) {
                $file = ['name' => $_FILES['images']['name'][$key], 'tmp_name' => $tmp, 'size' => $_FILES['images']['size'][$key], 'error' => $_FILES['images']['error'][$key]];
                if (!validateFile($file)) $errors[] = 'Invalid image file (JPEG/PNG/GIF, max 5MB)';
                else {
                    $uploaded = uploadFile($file, '../uploads/medicines/');
                    if ($uploaded) $images[] = $uploaded;
                }
            }
        }
    }
    $imagesStr = implode(',', $images);

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO medicines (donor_id, name, type, strength, quantity, unit, manufacturing_date, expiry_date, batch_number, manufacturer, packaging_condition, storage_condition, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user['id'], $name, $type, $strength, $quantity, $unit, $manufacturing_date, $expiry_date, $batch, $manufacturer, $packaging, $storage, $imagesStr]);
            $medId = $pdo->lastInsertId();
            updateExpiryStatus($medId);

            // Notifications
            $verifiers = $pdo->query("SELECT id FROM users WHERE role = 'verifier'")->fetchAll();
            foreach ($verifiers as $v) {
                createNotification($v['id'], 'New Donation', "A new medicine '$name' needs verification.", 'info', '/medidonate/verifier/pending-medicines.php');
            }
            $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
            foreach ($admins as $a) {
                createNotification($a['id'], 'New Donation', "Donor submitted '$name'.", 'info', '/medidonate/admin/medicines.php');
            }
            createNotification($user['id'], 'Donation Submitted', "Your donation '$name' has been submitted successfully.", 'success', '/medidonate/donor/my-donations.php');
            if ($expiringSoon) {
                createNotification($user['id'], 'Expiry Warning', "Your donation '$name' expires within 30 days. It will be reviewed.", 'warning', '/medidonate/donor/my-donations.php');
            }
            sendEmail($user['email'], 'Donation Submitted', "<p>Your donation '$name' has been submitted.</p>");
            $pdo->commit();
            $_SESSION['success'] = 'Donation submitted successfully.';
            header('Location: my-donations.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to submit donation: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$pageTitle = 'Donate Medicine';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0"><?php include '../includes/sidebar.php'; ?></div>
        <div class="col-md-9 p-4">
            <h2>Donate Medicine</h2>
            <?php echo displayAlerts(); ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="donateForm" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Medicine Name *</label>
                                <input type="text" name="name" class="form-control" id="name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type *</label>
                                <select name="type" class="form-select" id="type" required>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Capsule">Capsule</option>
                                    <option value="Syrup">Syrup</option>
                                    <option value="Cream">Cream</option>
                                    <option value="Drops">Drops</option>
                                    <option value="Injection">Injection</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="strength" class="form-label">Strength</label>
                                <input type="text" name="strength" class="form-control" id="strength">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input type="number" name="quantity" class="form-control" id="quantity" min="1" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="unit" class="form-label">Unit *</label>
                                <select name="unit" class="form-select" id="unit" required>
                                    <option value="Strip">Strip</option>
                                    <option value="Bottle">Bottle</option>
                                    <option value="Pack">Pack</option>
                                    <option value="Vial">Vial</option>
                                    <option value="Tube">Tube</option>
                                    <option value="Other">Other</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="manufacturing_date" class="form-label">Manufacturing Date</label>
                                <input type="date" name="manufacturing_date" class="form-control" id="manufacturing_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date *</label>
                                <input type="date" name="expiry_date" class="form-control" id="expiry_date" required>
                                <div class="invalid-feedback"></div>
                                <div id="expiryWarning" class="text-warning small" style="display:none;">This medicine expires within 30 days.</div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="batch" class="form-label">Batch Number</label>
                                <input type="text" name="batch" class="form-control" id="batch">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="manufacturer" class="form-label">Manufacturer</label>
                                <input type="text" name="manufacturer" class="form-control" id="manufacturer">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="packaging" class="form-label">Packaging Condition</label>
                            <textarea name="packaging" class="form-control" id="packaging" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="storage" class="form-label">Storage Condition</label>
                            <textarea name="storage" class="form-control" id="storage" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="images" class="form-label">Upload Images (max 5, JPEG/PNG, max 5MB)</label>
                            <input type="file" name="images[]" class="form-control" id="images" multiple accept="image/*">
                            <div class="invalid-feedback"></div>
                        </div>
                        <button type="submit" class="btn btn-success">Submit Donation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>