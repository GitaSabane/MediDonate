<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/functions.php';
$pageTitle = 'MediDonate - Give Unused Medicines a Purpose';

// Fetch statistics
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total_donations FROM medicines");
$stats['total_donations'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as verified_donations FROM medicines WHERE verification_status = 'verified'");
$stats['verified_donations'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as registered_orgs FROM organizations WHERE status = 'approved'");
$stats['registered_orgs'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as completed_redistributions FROM distributions WHERE status = 'completed'");
$stats['completed_redistributions'] = $stmt->fetchColumn();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<!-- Custom Styling with Fully Transparent Hero & Glassmorphic Cards -->
<style>
    body {
        position: relative;
        overflow-x: hidden;
        background-color: #f8f9fa; /* Clean light background base */
    }

    /* Background Animation Container */
    .animated-bg-shapes {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
        z-index: -1;
        pointer-events: none;
    }

    /* Individual Shape Styles */
    .shape {
        position: absolute;
        opacity: 0.55;
        animation: floatShape 18s infinite linear;
    }

    /* Dotted Circles */
    .shape-dot-grid {
        width: 120px;
        height: 120px;
        background-image: radial-gradient(#198754 25%, transparent 25%);
        background-size: 16px 16px;
        border-radius: 50%;
    }

    /* Dashed Borders */
    .shape-dashed-circle {
        width: 100px;
        height: 100px;
        border: 3px dashed #0dcaf0;
        border-radius: 50%;
    }

    .shape-dashed-square {
        width: 90px;
        height: 90px;
        border: 3px dashed #ffc107;
        border-radius: 20px;
    }

    .shape-dashed-ring {
        width: 140px;
        height: 140px;
        border: 4px dashed #20c997;
        border-radius: 50%;
    }

    /* Floating Keyframes */
    @keyframes floatShape {
        0% {
            transform: translateY(0px) translateX(0px) rotate(0deg);
        }
        50% {
            transform: translateY(-140px) translateX(70px) rotate(180deg);
        }
        100% {
            transform: translateY(0px) translateX(0px) rotate(360deg);
        }
    }

    /* Random positioning and animation delays for shapes */
    .shape:nth-child(1) { top: 10%; left: 5%; animation-duration: 22s; }
    .shape:nth-child(2) { top: 25%; right: 8%; animation-duration: 15s; animation-delay: 2s; }
    .shape:nth-child(3) { top: 50%; left: 2%; animation-duration: 20s; animation-delay: 4s; }
    .shape:nth-child(4) { top: 65%; right: 5%; animation-duration: 25s; animation-delay: 1s; }
    .shape:nth-child(5) { top: 85%; left: 15%; animation-duration: 17s; animation-delay: 3s; }
    .shape:nth-child(6) { top: 40%; right: 20%; animation-duration: 19s; animation-delay: 5s; }

    /* Completely Transparent Hero Section (No background box) */
    .hero-section {
        background: transparent !important;
        border: none !important;
        backdrop-filter: none !important;
    }

    /* Transparent Image Wrapper */
    .hero-image-wrapper {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* Glassmorphism for Stats & Other Cards */
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(255, 255, 255, 0.6);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.1)!important;
        background: rgba(255, 255, 255, 0.9);
    }
    .step-icon-box {
        width: 80px;
        height: 80px;
        background-color: rgba(232, 245, 233, 0.85);
        color: #198754;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 1rem;
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .step-card:hover .step-icon-box {
        background-color: #198754;
        color: #fff;
    }
    .benefit-container {
        background: rgba(255, 255, 255, 0.75) !important;
        border: 1px solid rgba(255, 255, 255, 0.6);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .benefit-item {
        padding: 1rem;
        font-weight: 500;
        color: #333;
    }
    .safety-alert {
        background: rgba(255, 243, 205, 0.9) !important;
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
        border: 1px solid rgba(255, 238, 186, 0.6) !important;
    }
</style>

<!-- Animated Background Dashed & Dotted Shapes -->
<div class="animated-bg-shapes">
    <div class="shape shape-dot-grid" style="color: #198754;"></div>
    <div class="shape shape-dashed-circle"></div>
    <div class="shape shape-dashed-square"></div>
    <div class="shape shape-dot-grid" style="background-image: radial-gradient(#0d6efd 25%, transparent 25%);"></div>
    <div class="shape shape-dashed-ring"></div>
    <div class="shape shape-dashed-circle" style="border-color: #d63384;"></div>
</div>

<div class="container my-5 position-relative">
    <!-- Hero Section -->
    <div class="row align-items-center g-5 py-5 px-2 mb-5 hero-section">
        <div class="col-lg-6">
            <span class="badge bg-success bg-opacity-15 text-success px-3 py-2 rounded-pill mb-3 fw-semibold">
                <i class="bi bi-heart-fill me-1"></i> Bridging Gaps in Healthcare
            </span>
            <h1 class="display-4 fw-bold text-dark mb-3">Give Unused Medicines a Purpose</h1>
            <p class="lead text-muted mb-4">MediDonate connects donors with verified organizations to redistribute unused medicines safely and efficiently, reducing waste and saving lives.</p>
            <div class="d-grid gap-3 d-md-flex">
                <a href="register.php?role=donor" class="btn btn-success btn-lg px-4 shadow-sm">
                    <i class="bi bi-capsule me-2"></i>Donate Medicine
                </a>
                <a href="register.php?role=organization" class="btn btn-outline-success btn-lg px-4">
                    <i class="bi bi-building-check me-2"></i>Request Medicine
                </a>
            </div>
        </div>
        <div class="col-lg-6 text-center hero-image-wrapper">
            <img src="assets/images/hero.svg" class="img-fluid drop-shadow" alt="MediDonate" style="max-height: 380px;">
        </div>
    </div>

    <!-- Stats Section -->
    <div class="row text-center g-4 py-4 mb-5">
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm h-100 py-3">
                <div class="card-body">
                    <h2 class="text-success fw-bold display-6 mb-1"><?php echo $stats['total_donations']; ?></h2>
                    <p class="text-muted mb-0 fw-medium">Total Donations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm h-100 py-3">
                <div class="card-body">
                    <h2 class="text-success fw-bold display-6 mb-1"><?php echo $stats['verified_donations']; ?></h2>
                    <p class="text-muted mb-0 fw-medium">Verified Donations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm h-100 py-3">
                <div class="card-body">
                    <h2 class="text-success fw-bold display-6 mb-1"><?php echo $stats['registered_orgs']; ?></h2>
                    <p class="text-muted mb-0 fw-medium">Registered Organizations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card stat-card shadow-sm h-100 py-3">
                <div class="card-body">
                    <h2 class="text-success fw-bold display-6 mb-1"><?php echo $stats['completed_redistributions']; ?></h2>
                    <p class="text-muted mb-0 fw-medium">Completed Redistributions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="row py-5 mb-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold">How It Works</h2>
            <p class="text-muted">Simple, transparent steps to make your unused medicines count.</p>
        </div>
        <div class="col-md-4 text-center step-card px-4 mb-4 mb-md-0">
            <div class="step-icon-box shadow-sm">
                <i class="bi bi-box-seam fs-2"></i>
            </div>
            <h5 class="fw-bold mb-3">1. Donate</h5>
            <p class="text-muted">Donors submit unused medicines with comprehensive details and clear images.</p>
        </div>
        <div class="col-md-4 text-center step-card px-4 mb-4 mb-md-0">
            <div class="step-icon-box shadow-sm">
                <i class="bi bi-check-circle fs-2"></i>
            </div>
            <h5 class="fw-bold mb-3">2. Verify</h5>
            <p class="text-muted">Authorized verifiers review and approve medical supplies to guarantee safety and efficacy.</p>
        </div>
        <div class="col-md-4 text-center step-card px-4">
            <div class="step-icon-box shadow-sm">
                <i class="bi bi-hand-thumbs-up fs-2"></i>
            </div>
            <h5 class="fw-bold mb-3">3. Request & Distribute</h5>
            <p class="text-muted">Verified organizations securely request, receive, and confirm successful distribution.</p>
        </div>
    </div>

    <!-- Benefits -->
    <div class="row py-4 px-3 benefit-container rounded-4 shadow-sm align-items-center mb-5 text-center text-md-start">
        <div class="col-md-3 benefit-item mb-3 mb-md-0">
            <i class="bi bi-recycle text-success fs-4 me-2 align-middle"></i> Reduce medicine wastage
        </div>
        <div class="col-md-3 benefit-item mb-3 mb-md-0">
            <i class="bi bi-shield-check text-success fs-4 me-2 align-middle"></i> Connect with verified orgs
        </div>
        <div class="col-md-3 benefit-item mb-3 mb-md-0">
            <i class="bi bi-clock text-success fs-4 me-2 align-middle"></i> Monitor expiry dates
        </div>
        <div class="col-md-3 benefit-item">
            <i class="bi bi-diagram-3 text-success fs-4 me-2 align-middle"></i> Maintain traceability
        </div>
    </div>

    <!-- Safety Notice -->
    <div class="alert safety-alert shadow-sm rounded-3 mt-4 p-4" role="alert">
        <div class="d-flex">
            <div class="me-3 fs-3 text-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
            <div>
                <h5 class="alert-heading fw-bold mb-1">Safety Notice</h5>
                <p class="mb-0 text-secondary">All medicines require verification by authorized personnel. This platform does not diagnose, prescribe, or certify medical safety. Final acceptance must be performed by an authorized verifier/organization according to applicable laws.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>