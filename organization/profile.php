<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT * FROM organizations WHERE user_id = ?");
$stmt->execute([$user['id']]);
$org = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $org_name = sanitize($_POST['org_name']);
    $contact_person = sanitize($_POST['contact_person']);
    $org_address = sanitize($_POST['org_address']);
    $org_city = sanitize($_POST['org_city']);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ?, city = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $address, $city, $user['id']]);
        $stmt = $pdo->prepare("UPDATE organizations SET org_name = ?, contact_person = ?, address = ?, city = ? WHERE user_id = ?");
        $stmt->execute([$org_name, $contact_person, $org_address, $org_city, $user['id']]);
        $pdo->commit();
        $_SESSION['success'] = 'Profile updated.';
        header('Location: profile.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Update failed: ' . $e->getMessage();
    }
}

$pageTitle = 'Profile';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Organization Profile</h2>
            <?php echo displayAlerts(); ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <h5>User Information</h5>
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name (Contact)</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email (cannot change)</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">User Address</label>
                            <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="city" class="form-label">User City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city']); ?>">
                        </div>
                        <hr>
                        <h5>Organization Details</h5>
                        <div class="mb-3">
                            <label for="org_name" class="form-label">Organization Name</label>
                            <input type="text" name="org_name" class="form-control" value="<?php echo htmlspecialchars($org['org_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($org['contact_person']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="org_address" class="form-label">Organization Address</label>
                            <input type="text" name="org_address" class="form-control" value="<?php echo htmlspecialchars($org['address']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="org_city" class="form-label">Organization City</label>
                            <input type="text" name="org_city" class="form-control" value="<?php echo htmlspecialchars($org['city']); ?>">
                        </div>
                        <button type="submit" class="btn btn-success">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>