<?php
/**
 * Server-side validation helper for MediDonate
 * All functions return true on success, or a string error message on failure.
 */

function validateRequired($value, $fieldName = 'Field') {
    $value = trim($value);
    if (empty($value)) {
        return "$fieldName is required.";
    }
    return true;
}

function validateName($value, $fieldName = 'Name', $min = 2, $max = 100) {
    $result = validateRequired($value, $fieldName);
    if ($result !== true) return $result;
    $len = strlen(trim($value));
    if ($len < $min) return "$fieldName must be at least $min characters.";
    if ($len > $max) return "$fieldName must not exceed $max characters.";
    // Allow letters, spaces, hyphens, apostrophes, dots
    if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $value)) {
        return "$fieldName may only contain letters, spaces, hyphens, apostrophes, and dots.";
    }
    return true;
}

function validateEmail($email) {
    $email = trim($email);
    if (empty($email)) return "Email address is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Please enter a valid email address (e.g., user@example.com).";
    }
    if (strlen($email) > 255) return "Email address is too long.";
    return true;
}

function validatePhone($phone, $required = true) {
    $phone = trim($phone);
    if (!$required && empty($phone)) return true;
    if (empty($phone)) return "Phone number is required.";
    // Remove any non-digit characters
    $digits = preg_replace('/\D/', '', $phone);
    if (!preg_match('/^[6-9]\d{9}$/', $digits)) {
        return "Please enter a valid Indian mobile number (10 digits, starting with 6-9).";
    }
    return true;
}

function validatePassword($password, $fieldName = 'Password') {
    if (empty($password)) return "$fieldName is required.";
    if (strlen($password) < 8) return "$fieldName must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) return "$fieldName must contain at least one uppercase letter.";
    if (!preg_match('/[a-z]/', $password)) return "$fieldName must contain at least one lowercase letter.";
    if (!preg_match('/[0-9]/', $password)) return "$fieldName must contain at least one number.";
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return "$fieldName must contain at least one special character.";
    return true;
}

function validateConfirmPassword($password, $confirm) {
    if ($password !== $confirm) return "Passwords do not match.";
    return true;
}

function validateQuantity($quantity, $min = 1, $max = null) {
    if (!is_numeric($quantity) || $quantity < 0) return "Quantity must be a positive number.";
    $qty = (int)$quantity;
    if ($qty < $min) return "Quantity must be at least $min.";
    if ($max !== null && $qty > $max) return "Quantity cannot exceed $max.";
    return true;
}

function validateDate($date, $fieldName = 'Date', $allowPast = false) {
    if (empty($date)) return "$fieldName is required.";
    $timestamp = strtotime($date);
    if ($timestamp === false) return "Please enter a valid date for $fieldName.";
    if (!$allowPast && $timestamp < time()) {
        return "$fieldName cannot be in the past.";
    }
    return true;
}

function validateDateRange($from, $to, $fromLabel = 'From', $toLabel = 'To') {
    $fromTs = strtotime($from);
    $toTs = strtotime($to);
    if ($fromTs === false || $toTs === false) return "Invalid date format.";
    if ($fromTs > $toTs) return "$fromLabel date cannot be later than $toLabel date.";
    return true;
}

function validateExpiry($expiry, $manufacturing = null) {
    $result = validateDate($expiry, 'Expiry date', false);
    if ($result !== true) return $result;
    if ($manufacturing) {
        $mfg = strtotime($manufacturing);
        $exp = strtotime($expiry);
        if ($mfg !== false && $exp < $mfg) {
            return "Expiry date cannot be before manufacturing date.";
        }
    }
    return true;
}

function validateFile($file, $allowedTypes = ['image/jpeg','image/png','image/gif','image/webp'], $maxSize = 5*1024*1024, $required = true) {
    if (!$required && ($file['error'] === UPLOAD_ERR_NO_FILE)) return true;
    if ($file['error'] !== UPLOAD_ERR_OK) return "File upload failed. Error code: " . $file['error'];
    if ($file['size'] > $maxSize) return "File size exceeds " . ($maxSize/1024/1024) . " MB limit.";
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedTypes)) {
        $exts = implode(', ', array_map(function($t){ return str_replace('image/', '', $t); }, $allowedTypes));
        return "Only " . strtoupper($exts) . " files are allowed.";
    }
    return true;
}

function validateSelect($value, $allowed, $fieldName = 'Selection') {
    if (!in_array($value, $allowed)) return "Invalid selection for $fieldName.";
    return true;
}

function validateTextLength($value, $min = 0, $max = 65535, $fieldName = 'Text') {
    $len = strlen(trim($value));
    if ($min > 0 && $len < $min) return "$fieldName must be at least $min characters.";
    if ($max > 0 && $len > $max) return "$fieldName must not exceed $max characters.";
    return true;
}

// CSRF helper
function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return "CSRF validation failed. Please try again.";
    }
    return true;
}

// Generic sanitization
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}