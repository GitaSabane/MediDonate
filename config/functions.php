<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail.php';

// --- Utility ---
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function redirect($url, $msg = '', $type = 'success') {
    if ($msg) $_SESSION[$type] = $msg;
    header("Location: $url");
    exit;
}

function displayAlerts() {
    $output = '';
    foreach (['success', 'error', 'info', 'warning'] as $type) {
        if (isset($_SESSION[$type])) {
            $output .= "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                {$_SESSION[$type]}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
            unset($_SESSION[$type]);
        }
    }
    return $output;
}

// --- Validation Helpers ---
function validateRequired($value) {
    return !empty(trim($value));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[0-9+\-\(\)\s]{7,20}$/', $phone);
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function validateConfirm($password, $confirm) {
    return $password === $confirm;
}

function validateLength($value, $min, $max = null) {
    $len = strlen(trim($value));
    if ($len < $min) return false;
    if ($max !== null && $len > $max) return false;
    return true;
}

function validateNumeric($value) {
    return is_numeric($value);
}

function validatePositive($value) {
    return is_numeric($value) && $value > 0;
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateExpiry($expiry) {
    if (!validateDate($expiry)) return false;
    return strtotime($expiry) >= strtotime(date('Y-m-d'));
}

function validateFile($file, $allowedTypes = ['image/jpeg','image/png','image/gif','application/pdf'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxSize) return false;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    return in_array($mime, $allowedTypes);
}

// --- Notification ---
function createNotification($userId, $title, $message, $type = 'info', $link = null) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $type, $title, $message, $link]);
}

function getUnreadCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function markNotificationsRead($userId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    return $stmt->execute([$userId]);
}

// --- Audit ---
function logAudit($userId, $action, $entityType = null, $entityId = null, $description = null) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $action, $entityType, $entityId, $description, $ip]);
}

// --- Expiry ---
function updateExpiryStatus($medicineId = null) {
    global $pdo;
    if ($medicineId) {
        $stmt = $pdo->prepare("UPDATE medicines SET expiry_status = 
            CASE 
                WHEN expiry_date < CURDATE() THEN 'expired'
                WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                ELSE 'valid'
            END
            WHERE id = ?");
        return $stmt->execute([$medicineId]);
    } else {
        $stmt = $pdo->prepare("UPDATE medicines SET expiry_status = 
            CASE 
                WHEN expiry_date < CURDATE() THEN 'expired'
                WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                ELSE 'valid'
            END");
        return $stmt->execute();
    }
}

function checkAllExpiry() {
    global $pdo;
    updateExpiryStatus();
    $stmt = $pdo->prepare("SELECT id, name, expiry_date, donor_id FROM medicines WHERE expiry_status = 'expiring_soon' AND expiry_date > CURDATE()");
    $stmt->execute();
    $medicines = $stmt->fetchAll();
    foreach ($medicines as $med) {
        createNotification($med['donor_id'], 'Medicine Expiring Soon', "Your medicine '{$med['name']}' will expire on {$med['expiry_date']}.", 'warning', '/medidonate/donor/my-donations.php');
    }
}

// --- Inventory ---
function getInventory($medicineId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE medicine_id = ?");
    $stmt->execute([$medicineId]);
    return $stmt->fetch();
}

function updateInventory($medicineId, $total, $available, $reserved, $distributed) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE inventory SET total_quantity = ?, available_quantity = ?, reserved_quantity = ?, distributed_quantity = ? WHERE medicine_id = ?");
    return $stmt->execute([$total, $available, $reserved, $distributed, $medicineId]);
}

function adjustInventoryOnVerification($medicineId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT quantity FROM medicines WHERE id = ? AND verification_status = 'verified'");
    $stmt->execute([$medicineId]);
    $med = $stmt->fetch();
    if ($med) {
        $inv = getInventory($medicineId);
        if (!$inv) {
            $stmt = $pdo->prepare("INSERT INTO inventory (medicine_id, total_quantity, available_quantity, reserved_quantity, distributed_quantity) VALUES (?, ?, ?, 0, 0)");
            return $stmt->execute([$medicineId, $med['quantity'], $med['quantity']]);
        } else {
            $newTotal = $inv['total_quantity'] + $med['quantity'];
            $newAvailable = $inv['available_quantity'] + $med['quantity'];
            return updateInventory($medicineId, $newTotal, $newAvailable, $inv['reserved_quantity'], $inv['distributed_quantity']);
        }
    }
    return false;
}

function adjustInventoryOnRequestApproval($requestId, $approvedQty) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT medicine_id FROM medicine_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();
    if (!$req) return false;
    $medicineId = $req['medicine_id'];
    $inv = getInventory($medicineId);
    if (!$inv || $inv['available_quantity'] < $approvedQty) return false;
    $newAvailable = $inv['available_quantity'] - $approvedQty;
    $newReserved = $inv['reserved_quantity'] + $approvedQty;
    return updateInventory($medicineId, $inv['total_quantity'], $newAvailable, $newReserved, $inv['distributed_quantity']);
}

// --- Email ---
function sendEmail($to, $subject, $body, $altBody = '') {
    global $mail;
    try {
        $mail->clearAddresses();
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

// --- Upload ---
function uploadFile($file, $targetDir, $allowedTypes = ['image/jpeg','image/png','image/gif','application/pdf'], $maxSize = 5242880) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > $maxSize) return false;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedTypes)) return false;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . '.' . $ext;
    $dest = rtrim($targetDir, '/') . '/' . $newName;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $newName;
    return false;
}

// --- Date formatting ---
function formatDate($date) {
    return date('d M Y', strtotime($date));
}
function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}
function getStatusBadge($status) {
    $map = [
        'pending' => 'warning',
        'verified' => 'success',
        'rejected' => 'danger',
        'more_info' => 'info',
        'approved' => 'success',
        'partially_approved' => 'info',
        'expired' => 'danger',
        'expiring_soon' => 'warning',
        'valid' => 'success',
        'active' => 'success',
        'inactive' => 'secondary',
        'suspended' => 'danger',
        'awaiting_distribution' => 'secondary',
        'picked_up' => 'info',
        'delivered' => 'primary',
        'received' => 'warning',
        'completed' => 'success',
        'Normal' => 'secondary',
        'Urgent' => 'warning',
        'Very Urgent' => 'danger',
    ];
    $class = $map[$status] ?? 'secondary';
    return "<span class='badge bg-$class'>$status</span>";
}
?>