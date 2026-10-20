<?php
$role = getUserRole();
$base = "../$role/";
?>

<div class="d-flex flex-column gap-1">
    <div class="px-3 pb-2 text-uppercase fs-7 fw-bold text-muted tracking-wider">Navigation</div>
    
    <ul class="nav nav-pills flex-column gap-1 mb-auto">
        <li class="nav-item">
            <a href="<?php echo $base; ?>dashboard.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        
        <?php if ($role == 'donor'): ?>
            <li><a href="<?php echo $base; ?>donate-medicine.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'donate-medicine.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-plus-circle me-2"></i> Donate Medicine</a></li>
            <li><a href="<?php echo $base; ?>my-donations.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'my-donations.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-box-seam me-2"></i> My Donations</a></li>
            
        <?php elseif ($role == 'organization'): ?>
            <li><a href="<?php echo $base; ?>medicines.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'medicines.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-search me-2"></i> Search Medicines</a></li>
            <li><a href="<?php echo $base; ?>my-requests.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'my-requests.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-list-ul me-2"></i> My Requests</a></li>
            
        <?php elseif ($role == 'verifier'): ?>
            <li><a href="<?php echo $base; ?>pending-medicines.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'pending-medicines.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-clock-history me-2"></i> Pending Medicines</a></li>
            <li><a href="<?php echo $base; ?>verification-history.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'verification-history.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-clock me-2"></i> History</a></li>
            <li><a href="<?php echo $base; ?>requests.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'requests.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-file-earmark-text me-2"></i> Requests</a></li>
            <li><a href="<?php echo $base; ?>expiring-medicines.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'expiring-medicines.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-exclamation-triangle me-2"></i> Expiring</a></li>
            
        <?php elseif ($role == 'admin'): ?>
            <li><a href="<?php echo $base; ?>users.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-people me-2"></i> Users</a></li>
            <li><a href="<?php echo $base; ?>organizations.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'organizations.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-building me-2"></i> Organizations</a></li>
            <li><a href="<?php echo $base; ?>medicines.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'medicines.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-capsule me-2"></i> Medicines</a></li>
            <li><a href="<?php echo $base; ?>requests.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'requests.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-card-list me-2"></i> Requests</a></li>
            <li><a href="<?php echo $base; ?>verifiers.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'verifiers.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-person-badge me-2"></i> Verifiers</a></li>
            <li><a href="<?php echo $base; ?>reports.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-bar-chart me-2"></i> Reports</a></li>
            <li><a href="<?php echo $base; ?>audit-logs.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'audit-logs.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-journal me-2"></i> Audit Logs</a></li>
            <li><a href="<?php echo $base; ?>settings.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-gear me-2"></i> Settings</a></li>
        <?php endif; ?>

        <hr class="text-muted my-2">
        
        <li><a href="<?php echo $base; ?>profile.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-person me-2"></i> Profile</a></li>
        <li><a href="<?php echo $base; ?>change-password.php" class="nav-link fw-semibold rounded-3 py-2 px-3 <?php echo basename($_SERVER['PHP_SELF']) == 'change-password.php' ? 'bg-success text-white shadow-sm' : 'link-dark'; ?>"><i class="bi bi-key me-2"></i> Change Password</a></li>
    </ul>
</div>

<style>
    .nav-pills .nav-link:hover:not(.bg-success) {
        background-color: var(--bs-light);
        color: var(--bs-success) !important;
    }
</style>