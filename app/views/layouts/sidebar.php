<!-- Sidebar -->
<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark-custom border-end" id="sidebar">
    <div class="container-fluid px-3 flex-lg-column align-items-stretch">
        <!-- Logo / Brand Title -->
        <div class="navbar-brand d-flex align-items-center justify-content-between py-3">
            <a href="?page=dashboard" class="text-white text-decoration-none d-flex align-items-center gap-2">
                <i class="ti ti-activity fs-3 text-primary"></i>
                <span class="font-weight-bold fs-4 logo-text">AES Tracker</span>
            </a>
            <!-- Mobile Menu Toggle Button (inside sidebar) -->
            <button class="navbar-toggler d-lg-none border-0 text-white p-0" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <i class="ti ti-menu-2 fs-2"></i>
            </button>
        </div>

        <!-- Sidebar Navigation Items -->
        <div class="collapse navbar-collapse flex-column align-items-stretch mt-lg-3" id="sidebarCollapse">
            <ul class="navbar-nav flex-column gap-1">
                <!-- Dashboard Link -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded <?php echo is_active_page('dashboard') ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="?page=dashboard">
                        <i class="ti ti-dashboard fs-4"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Management Section -->
                <li class="nav-item-header text-uppercase text-muted-custom font-weight-bold fs-7 mt-3 mb-1 px-3">Management</li>
                
                <!-- Projects Link -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded <?php echo is_active_page('projects', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="?page=projects">
                        <i class="ti ti-folders fs-4"></i>
                        <span>Projects</span>
                    </a>
                </li>

                <!-- Tickets Link -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded <?php echo is_active_page('tickets', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="?page=tickets">
                        <i class="ti ti-ticket fs-4"></i>
                        <span>Tickets</span>
                    </a>
                </li>

                <!-- Tasks Link (Client doesn't see Tasks menu) -->
                <?php if (($_SESSION['user_role'] ?? '') !== 'client'): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded <?php echo is_active_page('tasks', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="?page=tasks">
                            <i class="ti ti-checkbox fs-4"></i>
                            <span>My Tasks</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- User Management (Admin Only) -->
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <li class="nav-item-header text-uppercase text-muted-custom font-weight-bold fs-7 mt-3 mb-1 px-3">Administration</li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded <?php echo is_active_page('users', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="?page=users">
                            <i class="ti ti-users fs-4"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- User Account / Profile -->
                <li class="nav-item-header text-uppercase text-muted-custom font-weight-bold fs-7 mt-3 mb-1 px-3">Account</li>
                
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded <?php echo is_active_page('profile') ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="?page=profile">
                        <i class="ti ti-user fs-4"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded <?php echo is_active_page('profile-change-password') ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="?page=profile-change-password">
                        <i class="ti ti-lock-open fs-4"></i>
                        <span>Change Password</span>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item mt-4">
                    <a class="nav-link d-flex align-items-center gap-3 py-2.5 px-3 rounded text-danger-custom" href="?page=logout">
                        <i class="ti ti-logout fs-4"></i>
                        <span>Sign Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
