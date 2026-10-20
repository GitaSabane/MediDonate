<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['distribution_id'])) {
    $distId = (int)$_POST['distribution_id'];
    $user = getCurrentUser();
    $stmt = $pdo->prepare("SELECT d.*, r.organization_id FROM distributions d 
                           JOIN medicine_requests r ON d.request_id = r.id 
                           WHERE d.id = ? AND r.organization_id = (SELECT id FROM organizations WHERE user_id = ?)");
    $stmt->execute([$distId, $user['id']]);
    $dist = $stmt->fetch();
    if (!$dist) {
        $_SESSION['error'] = 'Distribution not found or not yours.';
    } elseif ($dist['status'] == 'awaiting_distribution') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE distributions SET status = 'received', confirmation_date = NOW() WHERE id = ?");
            $stmt->execute([$distId]);
            // Update inventory: reduce reserved, increase distributed? Actually inventory is updated when distribution is created. But we need to adjust distributed? Usually when distribution is created, we already moved from reserved to distributed? Actually in our design, we adjust inventory when distribution is created (not on confirmation). But we can leave it.
            // Notify verifiers/admins
            $notify = $pdo->query("SELECT id FROM users WHERE role IN ('verifier','admin')")->fetchAll();
            foreach ($notify as $n) {
                createNotification($n['id'], 'Receipt Confirmed', "Organization confirmed receipt for distribution #$distId.", 'info');
            }
            logAudit($user['id'], 'Confirm Receipt', 'distribution', $distId, 'Organization confirmed receipt');
            $pdo->commit();
            $_SESSION['success'] = 'Receipt confirmed.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Failed to confirm: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Invalid distribution status.';
    }
}
header('Location: my-requests.php');
exit;