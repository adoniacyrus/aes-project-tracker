<!-- Top Navbar -->
<header class="navbar navbar-expand-md navbar-light bg-white border-bottom sticky-top py-2 px-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <!-- Left Side: Toggle Sidebar on Mobile & Breadcrumb -->
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary btn-icon d-lg-none" type="button" onclick="toggleSidebarMobile()" aria-label="Toggle navigation">
                <i class="ti ti-menu-2 fs-3"></i>
            </button>
            
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="d-none d-sm-inline-block">
                <ol class="breadcrumb mb-0 bg-transparent p-0">
                    <li class="breadcrumb-item"><a href="?page=dashboard" class="text-decoration-none text-muted-custom fs-6">Home</a></li>
                    <li class="breadcrumb-item active text-dark font-weight-medium fs-6" aria-current="page"><?php echo e($pageTitle ?? 'Dashboard'); ?></li>
                </ol>
            </nav>
        </div>

        <!-- Right Side: Notifications & User Profile Menu -->
        <div class="d-flex align-items-center gap-3">
            <!-- Notification Area Placeholder -->
            <div class="dropdown">
                <a href="#" class="nav-link text-secondary position-relative p-2 rounded-circle hover-bg-gray" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-bell fs-3"></i>
                    <span class="position-absolute top-1 start-75 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border border-light mt-2" aria-labelledby="notificationDropdown" style="width: 280px;">
                    <li class="dropdown-header font-weight-bold border-bottom pb-2">Notifications</li>
                    <li>
                        <a class="dropdown-item py-2 border-bottom" href="#">
                            <div class="d-flex align-items-start gap-2">
                                <div class="bg-primary text-white p-1 rounded-circle"><i class="ti ti-info-circle fs-6"></i></div>
                                <div>
                                    <p class="mb-0 fs-6 text-dark font-weight-medium">Welcome to the Tracker!</p>
                                    <small class="text-muted">Just now</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li class="text-center py-2">
                        <a href="#" class="text-decoration-none text-primary fs-7">View All Notifications</a>
                    </li>
                </ul>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle no-caret" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <!-- Initials Avatar -->
                    <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 36px; height: 36px; font-size: 14px;">
                        <?php 
                            $words = explode(' ', $_SESSION['user_name'] ?? 'U');
                            $initials = '';
                            foreach ($words as $w) {
                                $initials .= strtoupper(substr($w, 0, 1));
                            }
                            echo e(substr($initials, 0, 2));
                        ?>
                    </div>
                    <div class="d-none d-md-block text-start">
                        <p class="mb-0 fs-6 font-weight-semibold leading-tight"><?php echo e($_SESSION['user_name']); ?></p>
                        <small class="text-muted-custom text-uppercase fs-8" style="letter-spacing: 0.5px;"><?php echo e($_SESSION['user_role']); ?></small>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border border-light mt-2" aria-labelledby="userMenuDropdown">
                    <li class="px-3 py-2 border-bottom d-md-none">
                        <p class="mb-0 fs-6 font-weight-semibold text-dark"><?php echo e($_SESSION['user_name']); ?></p>
                        <small class="text-muted text-uppercase fs-8"><?php echo e($_SESSION['user_role']); ?></small>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="?page=profile"><i class="ti ti-user text-secondary"></i> My Profile</a></li>
                    <li><a class="dropdown-item d-flex align-items-center gap-2" href="?page=profile-change-password"><i class="ti ti-lock text-secondary"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger d-flex align-items-center gap-2" href="?page=logout"><i class="ti ti-logout text-danger"></i> Sign Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
