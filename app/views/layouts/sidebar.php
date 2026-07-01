<!-- Sidebar -->
<aside class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark-custom border-end" id="sidebar">
    <div class="container-fluid px-3 flex-lg-column align-items-start">
        <!-- Logo / Brand Title -->
        <div class="navbar-brand d-flex align-items-center justify-content-between py-2">
            <a href="<?php echo route('dashboard'); ?>" class="text-white text-decoration-none d-flex align-items-center gap-2">
                <i class="ti ti-activity text-primary"></i>
                <span class="font-weight-bold logo-text">AES Tracker</span>
            </a>
            <!-- Mobile Menu Toggle Button (inside sidebar) -->
            <button class="navbar-toggler d-lg-none border-0 text-white p-0" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <i class="ti ti-menu-2 fs-2"></i>
            </button>
        </div>

        <!-- Sidebar Navigation Items -->
        <div class="collapse navbar-collapse flex-column align-items-stretch mt-lg-2" id="sidebarCollapse">
            <ul class="navbar-nav flex-column gap-1">
                <!-- Dashboard Link -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded <?php echo is_active_page('dashboard') ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="<?php echo route('dashboard'); ?>">
                        <i class="ti ti-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- Management Section -->
                <li class="nav-item-header text-uppercase text-muted-custom font-weight-bold fs-8 mt-2 mb-1 px-3">Management</li>
                
                <!-- Projects Link -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded <?php echo is_active_page('projects', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="<?php echo route('projects'); ?>">
                        <i class="ti ti-folders"></i>
                        <span>Projects</span>
                    </a>
                </li>

                <!-- Tickets Link -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded <?php echo is_active_page('tickets', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="<?php echo route('tickets'); ?>">
                        <i class="ti ti-ticket"></i>
                        <span>Tickets</span>
                    </a>
                </li>

                <!-- Tasks Link (Client doesn't see Tasks menu) -->
                <?php if (($_SESSION['user_role'] ?? '') !== 'client'): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded <?php echo is_active_page('tasks', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="<?php echo route('tasks'); ?>">
                            <i class="ti ti-checkbox"></i>
                            <span>My Tasks</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- User Management (Admin Only) -->
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <li class="nav-item-header text-uppercase text-muted-custom font-weight-bold fs-8 mt-2 mb-1 px-3">Administration</li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded <?php echo is_active_page('users', false) ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="<?php echo route('users'); ?>">
                            <i class="ti ti-users"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- User Account / Profile -->
                <li class="nav-item-header text-uppercase text-muted-custom font-weight-bold fs-8 mt-2 mb-1 px-3">Account</li>
                
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded <?php echo is_active_page('profile') ? 'active text-white bg-primary' : 'text-light-custom'; ?>" href="<?php echo route('profile'); ?>">
                        <i class="ti ti-user"></i>
                        <span>Manage Profile</span>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item mt-3">
                    <a class="nav-link d-flex align-items-center gap-2 py-2 px-3 rounded text-danger-custom" href="<?php echo route('logout'); ?>">
                        <i class="ti ti-logout"></i>
                        <span>Sign Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
