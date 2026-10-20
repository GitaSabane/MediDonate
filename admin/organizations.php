<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['org_id'])) {
    $orgId = (int)$_POST['org_id'];
    $status = $_POST['status'];
    $reason = sanitize($_POST['reason'] ?? '');
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        $_SESSION['error'] = 'CSRF validation failed.';
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE organizations SET status = ?, rejection_reason = ? WHERE id = ?");
            $stmt->execute([$status, $reason, $orgId]);
            $org = $pdo->prepare("SELECT user_id, org_name FROM organizations WHERE id = ?");
            $org->execute([$orgId]);
            $orgData = $org->fetch();
            $msg = $status == 'approved' ? "Your organization '{$orgData['org_name']}' has been approved." : "Your organization '{$orgData['org_name']}' was rejected. Reason: $reason";
            createNotification($orgData['user_id'], 'Organization Status', $msg, $status == 'approved' ? 'success' : 'danger', '/medidonate/organization/dashboard.php');
            logAudit($_SESSION['user_id'], 'Update Organization', 'organization', $orgId, "Set status to $status");
            $pdo->commit();
            $_SESSION['success'] = 'Organization updated.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed: ' . $e->getMessage();
        }
    }
    header('Location: organizations.php');
    exit;
}

$orgs = $pdo->query("SELECT o.*, u.full_name, u.email FROM organizations o 
                     JOIN users u ON o.user_id = u.id 
                     ORDER BY o.created_at DESC")->fetchAll();

$pageTitle = 'Manage Organizations';
include '../includes/header.php';
include '../includes/navbar.php';
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 p-0">
            <?php include '../includes/sidebar.php'; ?>
        </div>
        <div class="col-md-9 p-4">
            <h2>Organizations</h2>
            <?php echo displayAlerts(); ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orgs as $o): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['org_name']); ?></td>
                            <td><?php echo htmlspecialchars($o['contact_person']); ?><br><small><?php echo $o['email']; ?></small></td>
                            <td><?php echo $o['org_type']; ?></td>
                            <td><?php echo getStatusBadge($o['status']); ?></td>
                            <td><?php echo formatDate($o['created_at']); ?></td>
                            <td>
                                <?php if ($o['status'] == 'pending'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Proceed?')">
                                        <input type="hidden" name="org_id" value="<?php echo $o['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" name="status" value="approved" class="btn btn-sm btn-success">Approve</button>
                                        <button type="submit" name="status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Change status?')">
                                        <input type="hidden" name="org_id" value="<?php echo $o['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="approved" <?php if($o['status']=='approved') echo 'selected'; ?>>Approved</option>
                                            <option value="suspended" <?php if($o['status']=='suspended') echo 'selected'; ?>>Suspended</option>
                                            <option value="rejected" <?php if($o['status']=='rejected') echo 'selected'; ?>>Rejected</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>