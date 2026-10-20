<?php
session_start();
require_once __DIR__ . '/database.php';

// Set no-cache headers for protected pages
function setNoCacheHeaders() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user data
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Get user role
function getUserRole() {
    $user = getCurrentUser();
    return $user ? $user['role'] : null;
}

// Require login – redirects to login if not authenticated
function requireLogin() {
    setNoCacheHeaders();
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Please login to access this page.';
        header('Location: /medidonate/login.php');
        exit;
    }
    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);
}

// Require specific role – redirects to index if not
function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: /medidonate/index.php');
        exit;
    }
}

// Redirect based on role after login
function redirectBasedOnRole($role) {
    switch ($role) {
        case 'admin': header('Location: /medidonate/admin/dashboard.php'); break;
        case 'verifier': header('Location: /medidonate/verifier/dashboard.php'); break;
        case 'donor': header('Location: /medidonate/donor/dashboard.php'); break;
        case 'organization': header('Location: /medidonate/organization/dashboard.php'); break;
        default: header('Location: /medidonate/index.php'); break;
    }
    exit;
}

// CSRF token generation
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>