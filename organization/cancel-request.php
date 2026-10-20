<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/functions.php';
requireRole('organization');

$id = (int)$_GET['id'];
$user = getCurrentUser();
$stmt = $pdo->prepare("SELECT r.* FROM medicine_requests r 
                       JOIN organizations o ON r.organization_id = o.id 
                       WHERE r.id = ? AND o.user_id = ? AND r.status = 'pending'");
$stmt->execute([$id, $user['id']]);
$req = $stmt->fetch();
if (!$req) {
    $_SESSION['error'] = 'Request not found or cannot be cancelled.';
} else {
    $stmt = $pdo->prepare("UPDATE medicine_requests SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);
    logAudit($user['id'], 'Cancel Request', 'medicine_request', $id, 'Organization cancelled request');
    $_SESSION['success'] = 'Request cancelled.';
}
header('Location: my-requests.php');
exit;