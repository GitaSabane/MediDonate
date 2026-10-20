<nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm sticky-top py-3">
    <div class="container">
        <!-- Brand Logo -->
        <a class="navbar-brand fw-bold text-white fs-4 d-flex align-items-center gap-2" href="/medidonate/index.php">
            <span class="bg-white text-success p-2 rounded-3 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 38px; height: 38px;">
                <i class="bi bi-heart-pulse-fill fs-5"></i>
            </span>
            <span>MediDonate</span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link px-3 py-2 fw-medium text-white rounded-pill hover-bg-light" href="/medidonate/index.php">Home</a>
                </li>
                
                <?php if (!isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 fw-medium text-white rounded-pill" href="/medidonate/login.php">Login</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light text-success px-4 py-2 rounded-pill shadow-sm fw-semibold" href="/medidonate/register.php">Register</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 bg-success-subtle bg-opacity-25 px-3 py-2 rounded-pill text-white fw-medium shadow-sm border border-light border-opacity-25" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle text-white fs-5"></i>
                            <span><?php $user = getCurrentUser(); echo htmlspecialchars($user['full_name']); ?></span>
                            <span class="badge bg-danger rounded-pill ms-1" id="notifBadge"><?php echo getUnreadCount($user['id']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 p-2 mt-2" style="min-width: 220px;">
                            <li>
                                <a class="dropdown-item rounded-3 py-2 text-dark d-flex align-items-center gap-2" href="/medidonate/<?php echo $user['role']; ?>/dashboard.php">
                                    <i class="bi bi-speedometer2 text-success"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 text-dark d-flex align-items-center gap-2" href="/medidonate/<?php echo $user['role']; ?>/profile.php">
                                    <i class="bi bi-person text-success"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 text-dark d-flex align-items-center justify-content-between" href="/medidonate/<?php echo $user['role']; ?>/notifications.php">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-bell text-success"></i> Notifications
                                    </span>
                                    <span class="badge bg-secondary rounded-pill" id="notifBadgeSmall"><?php echo getUnreadCount($user['id']); ?></span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 text-danger d-flex align-items-center gap-2" href="/medidonate/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Extra Helper Style for Hover States -->
<style>
    .dropdown-item:hover {
        background-color: #f4fbf7 !important;
        color: #198754 !important;
    }
    .navbar-nav .nav-link:hover {
        color: #d1e7dd !important;
    }
</style>