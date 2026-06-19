<div class="row row-cards mb-4">
    <!-- Quick Statistics Summary -->
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar bg-primary-subtle text-primary rounded" style="width: 48px; height: 48px;">
                            <i class="ti ti-folders fs-2"></i>
                        </span>
                    </div>
                    <div class="col">
                        <h4 class="mb-0 font-weight-semibold">Projects Portfolio</h4>
                        <p class="text-secondary mb-0 fs-7">
                            <?php if ($archiveFilter): ?>
                                Viewing archived project histories and backups.
                            <?php else: ?>
                                Managing active client client-engagements and software packages.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <div class="col-auto">
                            <?php if ($archiveFilter): ?>
                                <a href="?page=projects" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                                    <i class="ti ti-folders"></i> View Active
                                </a>
                            <?php else: ?>
                                <a href="?page=projects&archived=1" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                                    <i class="ti ti-archive"></i> View Archive
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm border border-light">
    <!-- Header with Search and Actions -->
    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-md-items-center gap-3">
        <form method="GET" action="" class="d-flex flex-fill max-width-md align-items-center gap-2">
            <input type="hidden" name="page" value="projects">
            <input type="hidden" name="archived" value="<?php echo $archiveFilter; ?>">
            
            <div class="input-group input-group-flat">
                <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-1" placeholder="Search by name, code, client..." value="<?php echo e($search); ?>">
            </div>
            
            <select name="status" class="form-select max-width-xs" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php 
                $statuses = ['Proposal Received', 'In Progress', 'Maintenance', 'On Hold', 'Cancelled', 'Completed'];
                foreach ($statuses as $st):
                ?>
                    <option value="<?php echo $st; ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-primary px-3">Search</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="?page=projects&archived=<?php echo $archiveFilter; ?>" class="btn btn-outline-secondary btn-icon" title="Clear Filters"><i class="ti ti-x"></i></a>
            <?php endif; ?>
        </form>
        
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <div>
                <a href="?page=projects-create" class="btn btn-primary d-flex align-items-center gap-2 font-weight-medium">
                    <i class="ti ti-plus"></i> New Project
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Table content -->
    <div class="card-body p-0">
        <?php if (empty($projects)): ?>
            <div class="p-5 text-center text-muted">
                <i class="ti ti-folders fs-1 mb-2 text-secondary"></i>
                <p class="mb-0 fs-6">No projects found matching your search filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-vcenter card-table mb-0 fs-6 align-middle">
                    <thead>
                        <tr class="bg-light">
                            <th class="py-3 px-4" style="width: 120px;">Code</th>
                            <th class="py-3">Project Name</th>
                            <th class="py-3">Client / Organization</th>
                            <th class="py-3">Priority</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-center">Team</th>
                            <th class="py-3 text-center">Tickets</th>
                            <th class="py-3 px-4 text-end" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $proj): ?>
                            <tr>
                                <td class="px-4 font-monospace font-weight-bold text-primary">
                                    <?php echo e($proj['project_code']); ?>
                                </td>
                                <td>
                                    <div class="font-weight-semibold">
                                        <a href="?page=projects-view&id=<?php echo $proj['id']; ?>" class="text-decoration-none text-dark hover-primary">
                                            <?php echo e($proj['project_name']); ?>
                                        </a>
                                    </div>
                                    <div class="text-muted fs-8 text-truncate" style="max-width: 250px;">
                                        <?php echo e($proj['project_description'] ?: 'No description provided.'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo e($proj['client_name'] ?: 'Internal'); ?>
                                    <div class="fs-8 text-muted"><?php echo e($proj['organization_name'] ?: 'AES'); ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $priorityClass = 'bg-secondary-subtle text-secondary';
                                        if ($proj['priority'] === 'critical') $priorityClass = 'bg-danger-subtle text-danger';
                                        if ($proj['priority'] === 'high') $priorityClass = 'bg-warning-subtle text-warning-emphasis';
                                        if ($proj['priority'] === 'medium') $priorityClass = 'bg-primary-subtle text-primary';
                                    ?>
                                    <span class="badge <?php echo $priorityClass; ?> text-capitalize px-2 py-1 fs-8 rounded">
                                        <?php echo e($proj['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'bg-secondary';
                                        if ($proj['status'] === 'Proposal Received') $statusClass = 'bg-info text-white';
                                        if ($proj['status'] === 'In Progress') $statusClass = 'bg-primary text-white';
                                        if ($proj['status'] === 'Maintenance') $projStatusStyle = 'style="background-color: #6f42c1 !important;"'; // Purple
                                        if ($proj['status'] === 'On Hold') $statusClass = 'bg-warning text-dark';
                                        if ($proj['status'] === 'Cancelled') $statusClass = 'bg-secondary text-white';
                                        if ($proj['status'] === 'Completed') $statusClass = 'bg-success text-white';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?> px-2 py-1 fs-8 rounded" <?php echo ($proj['status'] === 'Maintenance') ? 'style="background-color: #6610f2 !important; color: white;"' : ''; ?>>
                                        <?php echo e($proj['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center text-secondary">
                                    <span class="badge bg-light border text-dark font-weight-medium rounded-circle px-2 py-1 fs-8">
                                        <?php echo (int)$proj['member_count']; ?>
                                    </span>
                                </td>
                                <td class="text-center text-secondary">
                                    <span class="badge bg-light border text-dark font-weight-medium rounded-circle px-2 py-1 fs-8">
                                        <?php echo (int)$proj['ticket_count']; ?>
                                    </span>
                                </td>
                                <td class="px-4 text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="?page=projects-view&id=<?php echo $proj['id']; ?>" class="btn btn-outline-secondary btn-icon" title="View details">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                                            <a href="?page=projects-edit&id=<?php echo $proj['id']; ?>" class="btn btn-outline-primary btn-icon" title="Edit project">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                            <a href="?page=projects-team&id=<?php echo $proj['id']; ?>" class="btn btn-outline-info btn-icon" title="Manage team">
                                                <i class="ti ti-users"></i>
                                            </a>
                                            <?php if ($proj['is_archived']): ?>
                                                <a href="?page=projects-archive&id=<?php echo $proj['id']; ?>&archive=0" 
                                                   class="btn btn-outline-success btn-icon" 
                                                   title="Restore project"
                                                   onclick="return confirm('Are you sure you want to restore this project?');">
                                                    <i class="ti ti-rotate-clockwise"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?page=projects-archive&id=<?php echo $proj['id']; ?>&archive=1" 
                                                   class="btn btn-outline-danger btn-icon" 
                                                   title="Archive project"
                                                   onclick="return confirm('Are you sure you want to archive this project? It will be hidden from main dashboards.');">
                                                    <i class="ti ti-archive"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination controls -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-transparent border-top py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-secondary fs-7">
                Showing Page <strong><?php echo $pageNum; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo $totalProjects; ?> projects)
            </span>
            <nav aria-label="Projects Page Navigation">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Prev -->
                    <li class="page-item <?php echo ($pageNum <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=projects&q=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&archived=<?php echo $archiveFilter; ?>&p=<?php echo $pageNum - 1; ?>"><i class="ti ti-chevron-left fs-8"></i> Prev</a>
                    </li>
                    
                    <!-- Pages -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $pageNum) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=projects&q=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&archived=<?php echo $archiveFilter; ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo ($pageNum >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=projects&q=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&archived=<?php echo $archiveFilter; ?>&p=<?php echo $pageNum + 1; ?>">Next <i class="ti ti-chevron-right fs-8"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
