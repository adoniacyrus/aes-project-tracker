<?php if (($_SESSION['user_role'] ?? '') !== 'client'): ?>
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

    <?php endif; ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Quick Actions Panel -->
    <div class="col-12 col-lg-4 d-flex flex-column gap-4">
        <?php if (($_SESSION['user_role'] ?? '') === 'client'): ?>
        <div class="card kpi-card shadow-sm border-0">
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
        <?php endif; ?>
        <div class="card shadow-sm border-light<?php echo (($_SESSION['user_role'] ?? '') !== 'client') ? ' h-100' : ''; ?>">
            <div class="card-header">
                <i class="ti ti-adjustments-horizontal me-1"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <button type="button"
                                class="btn btn-primary text-start d-flex align-items-center gap-2 py-2.5"
                                data-bs-toggle="modal"
                                data-bs-target="#projectCreateModal">
                            <i class="ti ti-folder-plus fs-4"></i>
                            <div>
                                <div class="font-weight-semibold fs-6">Create New Project</div>
                                <small class="text-light opacity-75 fs-8">Start new software workspace</small>
                            </div>
                        </button>
                        <button type="button"
                                class="btn btn-outline-secondary text-start d-flex align-items-center gap-2 py-2.5"
                                data-bs-toggle="modal"
                                data-bs-target="#userCreateModal">
                            <i class="ti ti-user-plus fs-4"></i>
                            <div>
                                <div class="font-weight-semibold fs-6">Create New User</div>
                                <small class="text-secondary fs-8">Add developer, client, or intern</small>
                            </div>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo route('tickets-create'); ?>" class="btn btn-primary text-start d-flex align-items-center gap-2 py-2.5">
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

    <!-- Main Content Panel -->
    <div class="col-12 col-lg-8">
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <!-- Activity Log Feed for Admin -->
            <div class="card h-100 shadow-sm border-light">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="ti ti-file-text me-1"></i> Recent Activity Feed</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2">Auditing Enabled</span>
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
                                                <?php if ($log['full_name']): ?>
                                                    <?php echo e($log['full_name']); ?>
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

        <?php elseif (($_SESSION['user_role'] ?? '') === 'developer'): ?>
            <!-- Developer Dashboard Widgets -->
            <div class="card h-100 shadow-sm border-light">
                <div class="card-header border-bottom-0 pb-0 bg-transparent">
                    <ul class="nav nav-tabs card-header-tabs" id="devTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dev-projects-tab" data-bs-toggle="tab" data-bs-target="#dev-projects" type="button" role="tab" aria-controls="dev-projects" aria-selected="true">
                                <i class="ti ti-folders me-1"></i> My Assigned Projects
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dev-tickets-tab" data-bs-toggle="tab" data-bs-target="#dev-tickets" type="button" role="tab" aria-controls="dev-tickets" aria-selected="false">
                                <i class="ti ti-ticket me-1"></i> My Assigned Tickets
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dev-tasks-tab" data-bs-toggle="tab" data-bs-target="#dev-tasks" type="button" role="tab" aria-controls="dev-tasks" aria-selected="false">
                                <i class="ti ti-checkbox me-1"></i> My Pending Tasks
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dev-recent-tickets-tab" data-bs-toggle="tab" data-bs-target="#dev-recent-tickets" type="button" role="tab" aria-controls="dev-recent-tickets" aria-selected="false">
                                <i class="ti ti-history me-1"></i> Recently Updated Tickets
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="dev-deadlines-tab" data-bs-toggle="tab" data-bs-target="#dev-deadlines" type="button" role="tab" aria-controls="dev-deadlines" aria-selected="false">
                                <i class="ti ti-alarm me-1"></i> Upcoming Deadlines
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="devTabsContent">
                        <!-- My Assigned Projects -->
                        <div class="tab-pane fade show active" id="dev-projects" role="tabpanel" aria-labelledby="dev-projects-tab">
                            <?php if (empty($developerAssignedProjects)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-folder-off fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No assigned projects found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Code</th>
                                                <th>Project Name</th>
                                                <th>Status</th>
                                                <th class="text-end px-3">Expected End</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($developerAssignedProjects as $proj): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium">
                                                        <a href="<?php echo route('projects-view', ['project_code' => $proj['project_code']]); ?>" class="text-decoration-none font-weight-bold"><?php echo e($proj['project_code']); ?></a>
                                                    </td>
                                                    <td><?php echo e($proj['project_name']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo project_status_badge_class($proj['status']); ?> border border-opacity-25">
                                                            <?php echo e(project_display_status($proj['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end px-3 text-secondary">
                                                        <?php echo $proj['expected_end_date'] ? date('M d, Y', strtotime($proj['expected_end_date'])) : 'N/A'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- My Assigned Tickets -->
                        <div class="tab-pane fade" id="dev-tickets" role="tabpanel" aria-labelledby="dev-tickets-tab">
                            <?php if (empty($developerAssignedTickets)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-ticket-off fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No active assigned tickets found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Ticket</th>
                                                <th>Title</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th class="text-end px-3">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($developerAssignedTickets as $tick): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium">
                                                        <a href="<?php echo route('tickets-view', ['ticket_code' => $tick['project_code'] . '-' . $tick['id']]); ?>" class="text-decoration-none font-weight-bold"><?php echo e($tick['project_code'] . '-' . $tick['id']); ?></a>
                                                    </td>
                                                    <td><?php echo e($tick['title']); ?></td>
                                                    <td>
                                                        <?php 
                                                            $priClass = 'bg-secondary-subtle text-secondary';
                                                            if ($tick['priority'] === 'critical') $priClass = 'bg-danger text-white';
                                                            elseif ($tick['priority'] === 'high') $priClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                            elseif ($tick['priority'] === 'medium') $priClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                                                            elseif ($tick['priority'] === 'low') $priClass = 'bg-success-subtle text-success border border-success-subtle';
                                                        ?>
                                                        <span class="badge <?php echo $priClass; ?> text-uppercase"><?php echo e($tick['priority']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $statusClass = 'bg-secondary-subtle text-secondary';
                                                            if (in_array($tick['status'], ['Open', 'Reopened'])) $statusClass = 'bg-success-subtle text-success border border-success-subtle';
                                                            if ($tick['status'] === 'In Development') $statusClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                                            if ($tick['status'] === 'Resolved') $statusClass = 'bg-info-subtle text-info border border-info-subtle';
                                                        ?>
                                                        <span class="badge <?php echo $statusClass; ?>"><?php echo e($tick['status']); ?></span>
                                                    </td>
                                                    <td class="text-end px-3 text-secondary">
                                                        <?php echo $tick['due_date'] ? date('M d, Y', strtotime($tick['due_date'])) : 'No due date'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- My Pending Tasks (AJAX) -->
                        <div class="tab-pane fade" id="dev-tasks" role="tabpanel" aria-labelledby="dev-tasks-tab">
                            <div id="dev-tasks-container">
                                <!-- Asynchronously loaded -->
                            </div>
                        </div>

                        <!-- Recently Updated Tickets (AJAX) -->
                        <div class="tab-pane fade" id="dev-recent-tickets" role="tabpanel" aria-labelledby="dev-recent-tickets-tab">
                            <div id="dev-recent-tickets-container">
                                <!-- Asynchronously loaded -->
                            </div>
                        </div>

                        <!-- Upcoming Deadlines -->
                        <div class="tab-pane fade" id="dev-deadlines" role="tabpanel" aria-labelledby="dev-deadlines-tab">
                            <?php if (empty($upcomingDeadlines)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-calendar-off fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No upcoming ticket deadlines.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Ticket</th>
                                                <th>Title</th>
                                                <th class="text-end px-3">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcomingDeadlines as $tick): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium">
                                                        <a href="<?php echo route('tickets-view', ['ticket_code' => $tick['project_code'] . '-' . $tick['id']]); ?>" class="text-decoration-none font-weight-bold"><?php echo e($tick['project_code'] . '-' . $tick['id']); ?></a>
                                                    </td>
                                                    <td><?php echo e($tick['title']); ?></td>
                                                    <td class="text-end px-3 font-weight-semibold text-danger">
                                                        <?php echo date('M d, Y', strtotime($tick['due_date'])); ?>
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

        <?php elseif (($_SESSION['user_role'] ?? '') === 'intern'): ?>
            <!-- Intern Dashboard Widgets -->
            <div class="card h-100 shadow-sm border-light">
                <div class="card-header border-bottom-0 pb-0 bg-transparent">
                    <ul class="nav nav-tabs card-header-tabs" id="internTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="intern-tasks-tab" data-bs-toggle="tab" data-bs-target="#intern-tasks" type="button" role="tab" aria-controls="intern-tasks" aria-selected="true">
                                <i class="ti ti-checkbox me-1"></i> Assigned Tasks
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="intern-tickets-tab" data-bs-toggle="tab" data-bs-target="#intern-tickets" type="button" role="tab" aria-controls="intern-tickets" aria-selected="false">
                                <i class="ti ti-ticket me-1"></i> Assigned Tickets
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="intern-pending-tab" data-bs-toggle="tab" data-bs-target="#intern-pending" type="button" role="tab" aria-controls="intern-pending" aria-selected="false">
                                <i class="ti ti-alert-circle me-1"></i> Pending Work
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="intern-deadlines-tab" data-bs-toggle="tab" data-bs-target="#intern-deadlines" type="button" role="tab" aria-controls="intern-deadlines" aria-selected="false">
                                <i class="ti ti-alarm me-1"></i> Upcoming Deadlines
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="internTabsContent">
                        <!-- Assigned Tasks -->
                        <div class="tab-pane fade show active" id="intern-tasks" role="tabpanel" aria-labelledby="intern-tasks-tab">
                            <?php if (empty($internAssignedTasks)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-checkbox fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No assigned tasks found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Task Name</th>
                                                <th>Ticket</th>
                                                <th>Status</th>
                                                <th class="text-end px-3">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($internAssignedTasks as $task): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium"><?php echo e($task['task_name']); ?></td>
                                                    <td>
                                                        <a href="<?php echo route('tickets-view', ['ticket_code' => $task['project_code'] . '-' . $task['ticket_id']]); ?>" class="text-decoration-none"><?php echo e($task['ticket_title']); ?></a>
                                                        <span class="text-secondary fs-8">(<?php echo e($task['project_code'] . '-' . $task['ticket_id']); ?>)</span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $taskBadge = 'bg-secondary-subtle text-secondary';
                                                            if ($task['status'] === 'Completed') $taskBadge = 'bg-success-subtle text-success border border-success-subtle';
                                                            if ($task['status'] === 'In Progress') $taskBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                                            if ($task['status'] === 'Pending') $taskBadge = 'bg-warning-subtle text-warning border border-warning-subtle';
                                                            if ($task['status'] === 'Blocked') $taskBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                        ?>
                                                        <span class="badge <?php echo $taskBadge; ?>"><?php echo e($task['status']); ?></span>
                                                    </td>
                                                    <td class="text-end px-3 text-secondary">
                                                        <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Assigned Tickets -->
                        <div class="tab-pane fade" id="intern-tickets" role="tabpanel" aria-labelledby="intern-tickets-tab">
                            <?php if (empty($internAssignedTickets)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-ticket-off fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No active assigned tickets found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Ticket</th>
                                                <th>Title</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th class="text-end px-3">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($internAssignedTickets as $tick): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium">
                                                        <a href="<?php echo route('tickets-view', ['ticket_code' => $tick['project_code'] . '-' . $tick['id']]); ?>" class="text-decoration-none font-weight-bold"><?php echo e($tick['project_code'] . '-' . $tick['id']); ?></a>
                                                    </td>
                                                    <td><?php echo e($tick['title']); ?></td>
                                                    <td>
                                                        <?php 
                                                            $priClass = 'bg-secondary-subtle text-secondary';
                                                            if ($tick['priority'] === 'critical') $priClass = 'bg-danger text-white';
                                                            elseif ($tick['priority'] === 'high') $priClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                            elseif ($tick['priority'] === 'medium') $priClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                                                            elseif ($tick['priority'] === 'low') $priClass = 'bg-success-subtle text-success border border-success-subtle';
                                                        ?>
                                                        <span class="badge <?php echo $priClass; ?> text-uppercase"><?php echo e($tick['priority']); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $statusClass = 'bg-secondary-subtle text-secondary';
                                                            if (in_array($tick['status'], ['Open', 'Reopened'])) $statusClass = 'bg-success-subtle text-success border border-success-subtle';
                                                            if ($tick['status'] === 'In Development') $statusClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                                                            if ($tick['status'] === 'Resolved') $statusClass = 'bg-info-subtle text-info border border-info-subtle';
                                                        ?>
                                                        <span class="badge <?php echo $statusClass; ?>"><?php echo e($tick['status']); ?></span>
                                                    </td>
                                                    <td class="text-end px-3 text-secondary">
                                                        <?php echo $tick['due_date'] ? date('M d, Y', strtotime($tick['due_date'])) : 'No due date'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Pending Work -->
                        <div class="tab-pane fade" id="intern-pending" role="tabpanel" aria-labelledby="intern-pending-tab">
                            <?php if (empty($internPendingWork)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-checkbox fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No pending tasks or work items.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Task Name</th>
                                                <th>Ticket</th>
                                                <th>Status</th>
                                                <th class="text-end px-3">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($internPendingWork as $task): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium"><?php echo e($task['task_name']); ?></td>
                                                    <td>
                                                        <a href="<?php echo route('tickets-view', ['ticket_code' => $task['project_code'] . '-' . $task['ticket_id']]); ?>" class="text-decoration-none"><?php echo e($task['ticket_title']); ?></a>
                                                        <span class="text-secondary fs-8">(<?php echo e($task['project_code'] . '-' . $task['ticket_id']); ?>)</span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                            $taskBadge = 'bg-secondary-subtle text-secondary';
                                                            if ($task['status'] === 'In Progress') $taskBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                                            if ($task['status'] === 'Pending') $taskBadge = 'bg-warning-subtle text-warning border border-warning-subtle';
                                                            if ($task['status'] === 'Blocked') $taskBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                                                        ?>
                                                        <span class="badge <?php echo $taskBadge; ?>"><?php echo e($task['status']); ?></span>
                                                    </td>
                                                    <td class="text-end px-3 text-secondary">
                                                        <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Upcoming Deadlines -->
                        <div class="tab-pane fade" id="intern-deadlines" role="tabpanel" aria-labelledby="intern-deadlines-tab">
                            <?php if (empty($upcomingDeadlines)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-calendar-off fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No upcoming ticket deadlines.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Ticket</th>
                                                <th>Title</th>
                                                <th class="text-end px-3">Due Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcomingDeadlines as $tick): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium">
                                                        <a href="<?php echo route('tickets-view', ['ticket_code' => $tick['project_code'] . '-' . $tick['id']]); ?>" class="text-decoration-none font-weight-bold"><?php echo e($tick['project_code'] . '-' . $tick['id']); ?></a>
                                                    </td>
                                                    <td><?php echo e($tick['title']); ?></td>
                                                    <td class="text-end px-3 font-weight-semibold text-danger">
                                                        <?php echo date('M d, Y', strtotime($tick['due_date'])); ?>
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

        <?php elseif (($_SESSION['user_role'] ?? '') === 'client'): ?>
            <!-- Client Dashboard Widgets -->
            <div class="card h-100 shadow-sm border-light">
                <div class="card-header border-bottom-0 pb-0 bg-transparent">
                    <ul class="nav nav-tabs card-header-tabs" id="clientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="client-projects-tab" data-bs-toggle="tab" data-bs-target="#client-projects" type="button" role="tab" aria-controls="client-projects" aria-selected="true">
                                <i class="ti ti-folders me-1"></i> My Active Projects
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="client-recent-tickets-tab" data-bs-toggle="tab" data-bs-target="#client-recent-tickets" type="button" role="tab" aria-controls="client-recent-tickets" aria-selected="false">
                                <i class="ti ti-history me-1"></i> Recent Project Updates
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="client-discussions-tab" data-bs-toggle="tab" data-bs-target="#client-discussions" type="button" role="tab" aria-controls="client-discussions" aria-selected="false">
                                <i class="ti ti-messages me-1"></i> Commercial Discussions
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content" id="clientTabsContent">
                        <!-- My Active Projects -->
                        <div class="tab-pane fade show active" id="client-projects" role="tabpanel" aria-labelledby="client-projects-tab">
                            <?php if (empty($clientActiveProjects)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-folder-off fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No active projects found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Code</th>
                                                <th>Project Name</th>
                                                <th>Status</th>
                                                <th class="text-end px-3">Expected End</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($clientActiveProjects as $proj): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium">
                                                        <a href="<?php echo route('projects-view', ['project_code' => $proj['project_code']]); ?>" class="text-decoration-none font-weight-bold"><?php echo e($proj['project_code']); ?></a>
                                                    </td>
                                                    <td><?php echo e($proj['project_name']); ?></td>
                                                    <td>
                                                        <span class="badge <?php echo project_status_badge_class($proj['status']); ?> border border-opacity-25">
                                                            <?php echo e(project_display_status($proj['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end px-3 text-secondary">
                                                        <?php echo $proj['expected_end_date'] ? date('M d, Y', strtotime($proj['expected_end_date'])) : 'N/A'; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Recent Project Updates (AJAX) -->
                        <div class="tab-pane fade" id="client-recent-tickets" role="tabpanel" aria-labelledby="client-recent-tickets-tab">
                            <div id="client-recent-tickets-container">
                                <!-- Asynchronously loaded -->
                            </div>
                        </div>

                        <!-- Commercial Discussions -->
                        <div class="tab-pane fade" id="client-discussions" role="tabpanel" aria-labelledby="client-discussions-tab">
                            <?php if (empty($clientCommercialDiscussions)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="ti ti-messages fs-2 mb-2 text-secondary"></i>
                                    <p class="mb-0">No recent commercial discussions.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                                        <thead>
                                            <tr class="bg-light">
                                                <th class="px-3">Ticket</th>
                                                <th>Message</th>
                                                <th>By</th>
                                                <th class="text-end px-3">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($clientCommercialDiscussions as $disc): ?>
                                                <tr>
                                                    <td class="px-3 font-weight-medium">
                                                        <a href="<?php echo route('tickets-view', ['ticket_code' => $disc['project_code'] . '-' . $disc['ticket_id']]); ?>" class="text-decoration-none font-weight-bold"><?php echo e($disc['project_code'] . '-' . $disc['ticket_id']); ?></a>
                                                        <div class="fs-8 text-secondary text-wrap" style="max-width: 150px;"><?php echo e($disc['ticket_title']); ?></div>
                                                    </td>
                                                    <td class="text-secondary text-wrap" style="max-width: 300px;"><?php echo e(mb_strimwidth($disc['message'], 0, 100, '...')); ?></td>
                                                    <td>
                                                        <?php echo e($disc['full_name']); ?>
                                                        <div class="fs-8 text-secondary text-uppercase"><?php echo e($disc['role']); ?></div>
                                                    </td>
                                                    <td class="text-end px-3 text-secondary">
                                                        <?php echo date('M d, H:i', strtotime($disc['created_at'])); ?>
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
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    // Function to load a widget via AJAX
    function loadWidget(widgetName, containerId, renderCallback) {
        var container = $('#' + containerId);
        // Show spinner & skeleton loading state
        container.html(`
            <div class="p-4 text-center">
                <div class="spinner-border text-primary spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="placeholder-glow mt-3">
                    <div class="placeholder col-12 mb-2" style="height: 35px; border-radius: 4px;"></div>
                    <div class="placeholder col-12 mb-2" style="height: 35px; border-radius: 4px;"></div>
                    <div class="placeholder col-12" style="height: 35px; border-radius: 4px;"></div>
                </div>
            </div>
        `);

        $.ajax({
            url: '<?php echo route("dashboard"); ?>',
            method: 'GET',
            data: { widget: widgetName },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderCallback(container, response.data);
                } else {
                    container.html('<div class="p-4"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle me-1"></i> ' + (response.message || 'Failed to load data.') + '</div></div>');
                }
            },
            error: function() {
                container.html('<div class="p-4"><div class="alert alert-danger mb-0"><i class="ti ti-alert-triangle me-1"></i> A connection error occurred.</div></div>');
            }
        });
    }

    // Render Recent Project Updates (Developer, Client)
    function renderRecentTickets(container, data) {
        if (!data || data.length === 0) {
            container.html('<div class="p-4 text-center text-muted"><i class="ti ti-database-off fs-2 mb-2"></i><p class="mb-0">No recent project updates found.</p></div>');
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-hover table-vcenter card-table mb-0 fs-7">';
        html += '<thead><tr class="bg-light"><th class="px-3">Ticket</th><th>Title</th><th>Status</th><th class="text-end px-3">Last Updated</th></tr></thead><tbody>';
        data.forEach(function(ticket) {
            var badgeClass = getStatusBadgeClass(ticket.status);
            var ticketCode = ticket.project_code + '-' + ticket.id;
            var ticketUrl = '<?php echo BASE_URL; ?>/tickets/' + ticketCode;
            html += '<tr>';
            html += '<td class="px-3 font-weight-medium"><a href="' + ticketUrl + '" class="text-decoration-none font-weight-bold">' + ticketCode + '</a></td>';
            html += '<td>' + escapeHtml(ticket.title) + '</td>';
            html += '<td><span class="badge ' + badgeClass + '">' + ticket.status + '</span></td>';
            html += '<td class="text-end px-3 text-secondary">' + formatDate(ticket.updated_at) + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        container.html(html);
    }

    // Render Pending Tasks (Developer)
    function renderPendingTasks(container, data) {
        if (!data || data.length === 0) {
            container.html('<div class="p-4 text-center text-muted"><i class="ti ti-database-off fs-2 mb-2"></i><p class="mb-0">No pending tasks assigned.</p></div>');
            return;
        }
        var html = '<div class="table-responsive"><table class="table table-hover table-vcenter card-table mb-0 fs-7">';
        html += '<thead><tr class="bg-light"><th class="px-3">Task Name</th><th>Ticket</th><th>Status</th><th class="text-end px-3">Due Date</th></tr></thead><tbody>';
        data.forEach(function(task) {
            var statusBadge = getTaskStatusBadgeClass(task.status);
            var ticketCode = task.project_code + '-' + task.ticket_id;
            var ticketUrl = '<?php echo BASE_URL; ?>/tickets/' + ticketCode;
            html += '<tr>';
            html += '<td class="px-3 font-weight-medium">' + escapeHtml(task.task_name) + '</td>';
            html += '<td><a href="' + ticketUrl + '" class="text-decoration-none">' + escapeHtml(task.ticket_title) + '</a> <span class="text-secondary fs-8">(' + ticketCode + ')</span></td>';
            html += '<td><span class="badge ' + statusBadge + '">' + task.status + '</span></td>';
            html += '<td class="text-end px-3 text-secondary">' + (task.due_date ? formatDateOnly(task.due_date) : 'No due date') + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
        container.html(html);
    }

    // Helpers
    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(str).html();
    }

    function getStatusBadgeClass(status) {
        switch(status) {
            case 'Open': return 'bg-success-subtle text-success border border-success-subtle';
            case 'In Development': return 'bg-primary-subtle text-primary border border-primary-subtle';
            case 'Resolved': return 'bg-info-subtle text-info border border-info-subtle';
            case 'Closed': return 'bg-secondary-subtle text-secondary border border-secondary-subtle';
            case 'Awaiting Admin Approval': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'Awaiting Client Review': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'Awaiting Payment': return 'bg-danger-subtle text-danger border border-danger-subtle';
            case 'Payment Confirmed': return 'bg-success-subtle text-success border border-success-subtle';
            case 'Rejected': return 'bg-danger-subtle text-danger border border-danger-subtle';
            default: return 'bg-secondary-subtle text-secondary';
        }
    }

    function getTaskStatusBadgeClass(status) {
        switch(status) {
            case 'Pending': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'In Progress': return 'bg-primary-subtle text-primary border border-primary-subtle';
            case 'Completed': return 'bg-success-subtle text-success border border-success-subtle';
            case 'Blocked': return 'bg-danger-subtle text-danger border border-danger-subtle';
            default: return 'bg-secondary-subtle text-secondary';
        }
    }

    function getPriorityBadgeClass(priority) {
        switch(priority ? priority.toLowerCase() : '') {
            case 'critical': return 'bg-danger text-white';
            case 'high': return 'bg-danger-subtle text-danger border border-danger-subtle';
            case 'medium': return 'bg-warning-subtle text-warning border border-warning-subtle';
            case 'low': return 'bg-success-subtle text-success border border-success-subtle';
            default: return 'bg-secondary-subtle text-secondary';
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        var date = new Date(dateStr);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var day = date.getDate();
        var month = months[date.getMonth()];
        var hour = date.getHours().toString().padStart(2, '0');
        var min = date.getMinutes().toString().padStart(2, '0');
        return month + ' ' + day + ', ' + hour + ':' + min;
    }

    function formatDateOnly(dateStr) {
        if (!dateStr) return '';
        var date = new Date(dateStr);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[date.getMonth()] + ' ' + date.getDate();
    }

    // Trigger loads depending on tab showing or page load
    <?php if (($_SESSION['user_role'] ?? '') === 'developer'): ?>
        // Load default tab and register click handlers
        loadWidget('pending_tasks', 'dev-tasks-container', renderPendingTasks);

        $('button[data-bs-target="#dev-tasks"]').on('shown.bs.tab', function () {
            loadWidget('pending_tasks', 'dev-tasks-container', renderPendingTasks);
        });
        $('button[data-bs-target="#dev-recent-tickets"]').on('shown.bs.tab', function () {
            loadWidget('recent_tickets', 'dev-recent-tickets-container', renderRecentTickets);
        });
    <?php elseif (($_SESSION['user_role'] ?? '') === 'client'): ?>
        $('button[data-bs-target="#client-recent-tickets"]').on('shown.bs.tab', function () {
            loadWidget('recent_tickets', 'client-recent-tickets-container', renderRecentTickets);
        });
    <?php endif; ?>
});
</script>
<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
    <?php
    $projectCreateAjaxReload = true;
    require __DIR__ . '/../projects/_create_modal.php';

    $userCreateModalTitle = 'Create New User';
    $userCreateAjaxReload = true;
    require __DIR__ . '/../users/_create_modal.php';
    ?>
<?php endif; ?>
