<div class="row row-deck row-cards g-4 mb-4">
    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
        <!-- ADMIN DASHBOARD WIDGETS -->
        <!-- Total Projects -->
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Total Projects</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['total_projects'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-primary text-white">
                        <i class="ti ti-folders fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7">
                    <a href="<?php echo route('projects'); ?>" class="text-decoration-none text-primary">View Portfolio <i class="ti ti-arrow-narrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Active Projects -->
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Active Projects</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['active_projects'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-warning text-dark">
                        <i class="ti ti-loader fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7 text-secondary">
                    Currently in progress
                </div>
            </div>
        </div>

        <!-- Completed Projects -->
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Completed Projects</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['completed_projects'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-success text-white">
                        <i class="ti ti-circle-check fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7 text-secondary">
                    Delivered successfully
                </div>
            </div>
        </div>

        <!-- Open Tickets -->
        <div class="col-sm-6 col-xl-6">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Open Tickets</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['open_tickets'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-danger text-white">
                        <i class="ti ti-ticket fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7">
                    <a href="<?php echo route('tickets'); ?>" class="text-decoration-none text-danger">Resolve Support Issues <i class="ti ti-arrow-narrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Closed Tickets -->
        <div class="col-sm-6 col-xl-6">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Closed & Rejected Tickets</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['closed_tickets'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-dark text-white">
                        <i class="ti ti-circle-x fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7 text-secondary">
                    Resolved/Archived items
                </div>
            </div>
        </div>

    <?php elseif (in_array($_SESSION['user_role'] ?? '', ['developer', 'intern'])): ?>
        <!-- DEVELOPER & INTERN DASHBOARD WIDGETS -->
        <!-- Assigned Projects -->
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">My Projects</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['assigned_projects'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-primary text-white">
                        <i class="ti ti-folders fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7">
                    <a href="<?php echo route('projects'); ?>" class="text-decoration-none text-primary">View Assigned Projects <i class="ti ti-arrow-narrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Assigned Tickets -->
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">My Active Tickets</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['assigned_tickets'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-danger text-white">
                        <i class="ti ti-ticket fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7">
                    <a href="<?php echo route('tickets'); ?>" class="text-decoration-none text-danger">Resolve My Tickets <i class="ti ti-arrow-narrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Pending Tasks -->
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">My Pending Tasks</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['pending_tasks'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-success text-white">
                        <i class="ti ti-checkbox fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7">
                    <a href="<?php echo route('tasks'); ?>" class="text-decoration-none text-success">My Tasks Checklist <i class="ti ti-arrow-narrow-right"></i></a>
                </div>
            </div>
        </div>

    <?php elseif (($_SESSION['user_role'] ?? '') === 'client'): ?>
        <!-- CLIENT DASHBOARD WIDGETS -->
        <!-- Active Projects -->
        <div class="col-sm-6 col-xl-6">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">My Active Projects</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['active_projects'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-primary text-white">
                        <i class="ti ti-folders fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7">
                    <a href="<?php echo route('projects'); ?>" class="text-decoration-none text-primary">Track Projects <i class="ti ti-arrow-narrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Open Tickets -->
        <div class="col-sm-6 col-xl-6">
            <div class="card h-100 kpi-card shadow-sm border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Open Support Tickets</span>
                        <h3 class="font-weight-bold mb-0 mt-1"><?php echo (int)($stats['open_tickets'] ?? 0); ?></h3>
                    </div>
                    <div class="kpi-icon bg-danger text-white">
                        <i class="ti ti-ticket fs-3"></i>
                    </div>
                </div>
                <div class="mt-3 fs-7">
                    <a href="<?php echo route('tickets'); ?>" class="text-decoration-none text-danger">View Support Tickets <i class="ti ti-arrow-narrow-right"></i></a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <!-- Quick Actions Panel -->
    <div class="col-12 col-lg-4">
        <div class="card h-100 shadow-sm border-light">
            <div class="card-header">
                <i class="ti ti-adjustments-horizontal me-1"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo route('profile'); ?>" class="btn btn-outline-primary text-start d-flex align-items-center gap-2 py-2.5">
                        <i class="ti ti-user-edit fs-4"></i>
                        <div>
                            <div class="font-weight-semibold fs-6">Update Profile</div>
                            <small class="text-secondary fs-8">Edit contact details</small>
                        </div>
                    </a>
                    
                    <a href="<?php echo route('profile-change-password'); ?>" class="btn btn-outline-primary text-start d-flex align-items-center gap-2 py-2.5">
                        <i class="ti ti-key fs-4"></i>
                        <div>
                            <div class="font-weight-semibold fs-6">Change Password</div>
                            <small class="text-secondary fs-8">Update account credentials</small>
                        </div>
                    </a>
                    
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <a href="<?php echo route('projects-create'); ?>" class="btn btn-primary text-start d-flex align-items-center gap-2 py-2.5 mt-2">
                            <i class="ti ti-folder-plus fs-4"></i>
                            <div>
                                <div class="font-weight-semibold fs-6">Create New Project</div>
                                <small class="text-light opacity-75 fs-8">Start new software workspace</small>
                            </div>
                        </a>
                        <a href="<?php echo route('users-create'); ?>" class="btn btn-outline-secondary text-start d-flex align-items-center gap-2 py-2.5">
                            <i class="ti ti-user-plus fs-4"></i>
                            <div>
                                <div class="font-weight-semibold fs-6">Create New User</div>
                                <small class="text-secondary fs-8">Add developer, client, or intern</small>
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo route('tickets-create'); ?>" class="btn btn-primary text-start d-flex align-items-center gap-2 py-2.5 mt-2">
                            <i class="ti ti-plus fs-4"></i>
                            <div>
                                <div class="font-weight-semibold fs-6">Create Support Ticket</div>
                                <small class="text-light opacity-75 fs-8">File a bug or feature request</small>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log Feed -->
    <div class="col-12 col-lg-8">
        <div class="card h-100 shadow-sm border-light">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="ti ti-file-text me-1"></i> Recent Activity Feed</span>
                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2">Auditing Enabled</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentLogs)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="ti ti-database-off fs-1 mb-2 text-secondary"></i>
                        <p class="mb-0">No recent activities logged.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                            <thead>
                                <tr class="bg-light">
                                    <th class="py-2 px-3">User</th>
                                    <th class="py-2">Action</th>
                                    <th class="py-2">Details</th>
                                    <th class="py-2">IP Address</th>
                                    <th class="py-2 px-3 text-end">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogs as $log): ?>
                                    <tr>
                                        <td class="px-3 font-weight-medium">
                                            <?php if ($log['first_name']): ?>
                                                <?php echo e($log['first_name'] . ' ' . $log['last_name']); ?>
                                                <div class="fs-8 text-secondary text-uppercase"><?php echo e($log['role']); ?></div>
                                            <?php else: ?>
                                                <span class="text-secondary"><?php echo e($log['email'] ?? 'System/Guest'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $actionClass = 'bg-secondary-subtle text-secondary';
                                                if (str_contains($log['action'], 'success')) $actionClass = 'bg-success-subtle text-success';
                                                if (str_contains($log['action'], 'failed')) $actionClass = 'bg-danger-subtle text-danger';
                                                if (str_contains($log['action'], 'create')) $actionClass = 'bg-primary-subtle text-primary';
                                                if (str_contains($log['action'], 'change') || str_contains($log['action'], 'reset')) $actionClass = 'bg-warning-subtle text-warning';
                                            ?>
                                            <span class="badge rounded-pill <?php echo $actionClass; ?> px-2 py-1 fs-8">
                                                <?php echo e(str_replace('_', ' ', $log['action'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-secondary text-wrap" style="max-width: 200px;"><?php echo e($log['details']); ?></td>
                                        <td class="text-secondary font-monospace"><?php echo e($log['ip_address']); ?></td>
                                        <td class="px-3 text-end text-secondary">
                                            <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>