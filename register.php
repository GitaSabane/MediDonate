<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';

setNoCacheHeaders();

if (isLoggedIn()) redirectBasedOnRole(getUserRole());

$role = $_GET['role'] ?? 'donor';
if (!in_array($role, ['donor', 'organization'])) $role = 'donor';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $role = sanitize($_POST['role']);

    // Server-side validation
    $errors = [];
    if (!validateRequired($full_name)) $errors[] = 'Full name is required.';
    if (!validateRequired($email)) $errors[] = 'Email is required.';
    elseif (!validateEmail($email)) $errors[] = 'Enter a valid email address.';
    if ($phone && !validatePhone($phone)) $errors[] = 'Enter a valid phone number.';
    if (!validatePassword($password)) $errors[] = 'Password must contain at least 8 characters.';
    if (!validateConfirm($password, $confirm)) $errors[] = 'Passwords do not match.';
    if ($address && !validateLength($address, 1, 255)) $errors[] = 'Address too long.';
    if ($city && !validateLength($city, 1, 100)) $errors[] = 'City too long.';

    if ($role == 'organization') {
        $org_name = sanitize($_POST['org_name']);
        $org_type = sanitize($_POST['org_type']);
        $reg_number = sanitize($_POST['reg_number']);
        $contact_person = sanitize($_POST['contact_person']);
        $org_address = sanitize($_POST['org_address']);
        $org_city = sanitize($_POST['org_city']);
        if (!validateRequired($org_name)) $errors[] = 'Organization name is required.';
        if (!validateRequired($reg_number)) $errors[] = 'Registration number is required.';
        if (!validateRequired($contact_person)) $errors[] = 'Contact person is required.';
        if (!validateRequired($org_address)) $errors[] = 'Organization address is required.';
        if (!validateRequired($org_city)) $errors[] = 'Organization city is required.';
        // File validation
        if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] == UPLOAD_ERR_OK) {
            if (!validateFile($_FILES['verification_doc'])) $errors[] = 'Invalid verification document. Allowed: PDF, JPEG, PNG, max 5MB.';
        }
    }

    if (empty($errors)) {
        // Check duplicate email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Email already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, address, city) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email, $phone, $hashed, $role, $address, $city]);
                $userId = $pdo->lastInsertId();

                if ($role == 'organization') {
                    $docName = null;
                    if (isset($_FILES['verification_doc']) && $_FILES['verification_doc']['error'] == UPLOAD_ERR_OK) {
                        $docName = uploadFile($_FILES['verification_doc'], 'uploads/organizations/');
                    }
                    $stmt = $pdo->prepare("INSERT INTO organizations (user_id, org_name, org_type, registration_number, contact_person, address, city, verification_doc, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([$userId, $org_name, $org_type, $reg_number, $contact_person, $org_address, $org_city, $docName]);
                    // Notify admin
                    $admins = $pdo->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
                    foreach ($admins as $admin) {
                        createNotification($admin['id'], 'New Organization Registration', "Organization '$org_name' is awaiting approval.", 'info', '/medidonate/admin/organizations.php');
                    }
                }
                $pdo->commit();
                $_SESSION['success'] = 'Registration successful. Please login.';
                sendEmail($email, 'Welcome to MediDonate', "<h3>Welcome $full_name</h3><p>Your account has been created. You can now login.</p>");
                header('Location: login.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Registration failed: ' . $e->getMessage();
            }
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$pageTitle = 'Register - MediDonate';
include 'includes/header.php';
include 'includes/navbar.php';
?>
<style>
    /* Force error messages to be strictly hidden by default */
    .invalid-feedback {
        display: none !important;
    }
    /* Show error message only when .is-invalid class is present */
    .form-control.is-invalid ~ .invalid-feedback,
    .form-select.is-invalid ~ .invalid-feedback {
        display: block !important;
    }
    /* Remove default Bootstrap icons */
    .form-control.is-valid, .form-control.is-invalid,
    .form-select.is-valid, .form-select.is-invalid {
        background-image: none !important;
    }
</style>
<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="text-center text-success">Register as <?php echo ucfirst($role); ?></h3>
                    <?php echo displayAlerts(); ?>
                    <form method="POST" enctype="multipart/form-data" id="registerForm" novalidate>
                        <input type="hidden" name="role" value="<?php echo $role; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label"><b>Full Name *</b></label>
                                <input type="text" name="full_name" class="form-control" id="full_name" required>
                                <div class="invalid-feedback">Full name is required.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label"><b>Email *</b></label>
                                <input type="email" name="email" class="form-control" id="email" required>
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label"><b>Phone</b></label>
                            <!-- Directly calling the validation function on input/change -->
                            <input type="tel" name="phone" class="form-control" id="phone" oninput="validatePhoneField(this)" onchange="validatePhoneField(this)">
                            <div class="invalid-feedback">Please enter a valid 10-digit mobile number.</div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label"><b>Password (min 8 chars) *</b></label>
                                <input type="password" name="password" class="form-control" id="password" required>
                                <div class="invalid-feedback">Password must be at least 8 characters.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label"><b>Confirm Password *</b></label>
                                <input type="password" name="confirm_password" class="form-control" id="confirm_password" required>
                                <div class="invalid-feedback">Passwords must match.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label"><b>Address</b></label>
                            <input type="text" name="address" class="form-control" id="address">
                            <div class="invalid-feedback">Address is too long.</div>
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label"><b>City</b></label>
                            <input type="text" name="city" class="form-control" id="city">
                            <div class="invalid-feedback">City name is too long.</div>
                        </div>

                        <?php if ($role == 'organization'): ?>
                        <hr>
                        <h5><b>Organization Details</b></h5>
                        <div class="mb-3">
                            <label for="org_name" class="form-label"><b>Organization Name *</b></label>
                            <input type="text" name="org_name" class="form-control" id="org_name" required>
                            <div class="invalid-feedback">Organization name is required.</div>
                        </div>
                        <div class="mb-3">
                            <label for="org_type" class="form-label"><b>Organization Type</b></label>
                            <select name="org_type" class="form-select" id="org_type">
                                <option value="NGO">NGO</option>
                                <option value="Charity">Charity</option>
                                <option value="Hospital">Hospital</option>
                                <option value="Clinic">Clinic</option>
                                <option value="Trust">Trust</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reg_number" class="form-label"><b>Registration Number *</b></label>
                            <input type="text" name="reg_number" class="form-control" id="reg_number" required>
                            <div class="invalid-feedback">Registration number is required.</div>
                        </div>
                        <div class="mb-3">
                            <label for="contact_person" class="form-label"><b>Contact Person *</b></label>
                            <input type="text" name="contact_person" class="form-control" id="contact_person" required>
                            <div class="invalid-feedback">Contact person is required.</div>
                        </div>
                        <div class="mb-3">
                            <label for="org_address" class="form-label"><b>Organization Address *</b></label>
                            <input type="text" name="org_address" class="form-control" id="org_address" required>
                            <div class="invalid-feedback">Organization address is required.</div>
                        </div>
                        <div class="mb-3">
                            <label for="org_city" class="form-label"><b>City *</b></label>
                            <input type="text" name="org_city" class="form-control" id="org_city" required>
                            <div class="invalid-feedback">City is required.</div>
                        </div>
                        <div class="mb-3">
                            <label for="verification_doc" class="form-label"><b>Verification Document (PDF/Image, max 5MB)</b></label>
                            <input type="file" name="verification_doc" class="form-control" id="verification_doc" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="invalid-feedback">Invalid verification document.</div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>
                    <div class="mt-3 text-center">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dedicated inline validation function for the phone field
function validatePhoneField(input) {
    const val = input.value.trim();

    // If phone is empty, since it's optional, keep it neutral
    if (val === '') {
        input.classList.remove('is-valid', 'is-invalid');
        return true;
    }

    // Strictly check for exactly 10 digits
    const phoneRegex = /^[0-9]{10}$/;
    if (phoneRegex.test(val)) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        return false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', function(event) {
        const phoneInput = document.getElementById('phone');
        if (phoneInput && phoneInput.value.trim() !== '') {
            if (!validatePhoneField(phoneInput)) {
                event.preventDefault();
                event.stopPropagation();
            }
        }
    }, false);
});
</script>

<?php include 'includes/footer.php'; ?>