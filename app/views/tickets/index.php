<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar bg-primary-subtle text-primary rounded" style="width: 48px; height: 48px;">
                            <i class="ti ti-ticket fs-2"></i>
                        </span>
                    </div>
                    <div class="col">
                        <h4 class="mb-0 font-weight-semibold">Ticket Directory</h4>
                        <p class="text-secondary mb-0 fs-7">Track support queries, features requests, and bug reports across your projects.</p>
                    </div>
                    <div class="col-auto">
                        <a href="?page=tickets-create" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="ti ti-plus"></i> Create Ticket
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm border border-light">
    <!-- Header with Search & Filters -->
    <div class="card-header bg-transparent border-bottom py-3 px-4">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="page" value="tickets">
            
            <!-- Search bar -->
            <div class="col-lg-3 col-md-6 col-12">
                <label class="form-label fs-8 text-secondary font-weight-semibold">Search Title/Desc</label>
                <div class="input-group input-group-flat">
                    <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-search"></i></span>
                    <input type="text" name="q" class="form-control border-start-0 ps-1" placeholder="Search tickets..." value="<?php echo e($search); ?>">
                </div>
            </div>

            <!-- Project Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label fs-8 text-secondary font-weight-semibold">Project</label>
                <select name="project_id" class="form-select">
                    <option value="">All Projects</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo $projectId === (int)$p['id'] ? 'selected' : ''; ?>>
                            <?php echo e($p['project_name']); ?> (<?php echo e($p['project_code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label fs-8 text-secondary font-weight-semibold">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php 
                    $categories = ['Bug Fix', 'New Feature Request', 'Enhancement Request', 'Technical Support'];
                    foreach ($categories as $cat):
                    ?>
                        <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Priority Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label fs-8 text-secondary font-weight-semibold">Priority</label>
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="critical" <?php echo $priority === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="col-lg-2 col-md-3 col-6">
                <label class="form-label fs-8 text-secondary font-weight-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php 
                    $allStatuses = ['Open', 'Awaiting Admin Approval', 'Awaiting Payment', 'Approved', 'In Development', 'Resolved', 'Reopened', 'Closed', 'Rejected', 'On Hold'];
                    foreach ($allStatuses as $st):
                    ?>
                        <option value="<?php echo $st; ?>" <?php echo $status === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit and Clear -->
            <div class="col-lg-1 col-12 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-primary w-100 px-2 py-2">Filter</button>
                <?php if (!empty($search) || $projectId > 0 || !empty($category) || !empty($priority) || !empty($status)): ?>
                    <a href="?page=tickets" class="btn btn-outline-secondary btn-icon py-2" title="Clear Filters"><i class="ti ti-x"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table Content -->
    <div class="card-body p-0">
        <?php if (empty($tickets)): ?>
            <div class="p-5 text-center text-muted">
                <i class="ti ti-ticket-off fs-1 mb-2 text-secondary"></i>
                <p class="mb-0 fs-6">No tickets found matching your filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-vcenter card-table mb-0 fs-6 align-middle">
                    <thead>
                        <tr class="bg-light">
                            <th class="py-3 px-4" style="width: 100px;">Ticket ID</th>
                            <th class="py-3">Title</th>
                            <th class="py-3">Project</th>
                            <th class="py-3">Category</th>
                            <th class="py-3">Priority</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Assignee</th>
                            <th class="py-3">Due Date</th>
                            <th class="py-3 px-4 text-end" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $tick): ?>
                            <tr>
                                <td class="px-4 font-monospace font-weight-bold text-secondary">#<?php echo $tick['id']; ?></td>
                                <td>
                                    <div class="font-weight-semibold">
                                        <a href="?page=tickets-view&id=<?php echo $tick['id']; ?>" class="text-decoration-none text-dark hover-primary">
                                            <?php echo e($tick['title']); ?>
                                        </a>
                                    </div>
                                    <small class="text-muted text-wrap fs-8 text-truncate d-block" style="max-width: 250px;">
                                        <?php echo e(substr($tick['description'], 0, 80)); ?><?php echo strlen($tick['description']) > 80 ? '...' : ''; ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light border text-dark font-monospace fs-8">
                                        <?php echo e($tick['project_code']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $catIcon = 'circle';
                                        if ($tick['category'] === 'Bug Fix') $catIcon = 'bug';
                                        if ($tick['category'] === 'New Feature Request') $catIcon = 'plus-pills';
                                        if ($tick['category'] === 'Enhancement Request') $catIcon = 'stars';
                                        if ($tick['category'] === 'Technical Support') $catIcon = 'help';
                                    ?>
                                    <span class="d-flex align-items-center gap-1 fs-7 text-dark font-weight-medium">
                                        <i class="ti ti-<?php echo $catIcon; ?> text-secondary fs-5"></i>
                                        <?php echo e($tick['category']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $prioClass = 'bg-secondary-subtle text-secondary';
                                        if ($tick['priority'] === 'critical') $prioClass = 'bg-danger-subtle text-danger';
                                        if ($tick['priority'] === 'high') $prioClass = 'bg-warning-subtle text-warning-emphasis';
                                        if ($tick['priority'] === 'medium') $prioClass = 'bg-primary-subtle text-primary';
                                    ?>
                                    <span class="badge <?php echo $prioClass; ?> text-capitalize px-2 py-1 fs-8 rounded">
                                        <?php echo e($tick['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'bg-secondary';
                                        if ($tick['status'] === 'Open') $statusClass = 'bg-info text-white';
                                        if ($tick['status'] === 'Awaiting Admin Approval') $statusClass = 'bg-warning text-dark';
                                        if ($tick['status'] === 'Awaiting Payment') $statusClass = 'bg-secondary-subtle text-dark border';
                                        if ($tick['status'] === 'Approved') $statusClass = 'bg-success-subtle text-success border border-success-subtle';
                                        if ($tick['status'] === 'In Development') $statusClass = 'bg-primary text-white';
                                        if ($tick['status'] === 'Resolved') $statusClass = 'bg-success text-white';
                                        if ($tick['status'] === 'Reopened') $statusClass = 'bg-danger text-white';
                                        if ($tick['status'] === 'Closed') $statusClass = 'bg-dark text-white';
                                        if ($tick['status'] === 'Rejected') $statusClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                                        if ($tick['status'] === 'On Hold') $statusClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?> px-2 py-1 fs-8 rounded">
                                        <?php echo e($tick['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($tick['assignee_first']): ?>
                                        <div class="d-flex align-items-center gap-1.5 fs-7 font-weight-medium">
                                            <div class="avatar bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 24px; height: 24px; font-size: 9px;">
                                                <?php echo strtoupper(substr($tick['assignee_first'], 0, 1) . substr($tick['assignee_last'], 0, 1)); ?>
                                            </div>
                                            <span><?php echo e($tick['assignee_first'] . ' ' . substr($tick['assignee_last'], 0, 1)); ?>.</span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted-custom italic fs-8">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary fs-7">
                                    <?php echo $tick['due_date'] ? date('M d, Y', strtotime($tick['due_date'])) : '<span class="text-muted fs-8 italic">None</span>'; ?>
                                </td>
                                <td class="px-4 text-end">
                                    <a href="?page=tickets-view&id=<?php echo $tick['id']; ?>" class="btn btn-outline-secondary btn-icon" title="View Details">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-transparent border-top py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-secondary fs-7">
                Showing Page <strong><?php echo $pageNum; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo $totalTickets; ?> tickets)
            </span>
            <nav aria-label="Tickets Page Navigation">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Prev -->
                    <li class="page-item <?php echo ($pageNum <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=tickets&q=<?php echo urlencode($search); ?>&project_id=<?php echo $projectId; ?>&category=<?php echo urlencode($category); ?>&priority=<?php echo urlencode($priority); ?>&status=<?php echo urlencode($status); ?>&p=<?php echo $pageNum - 1; ?>"><i class="ti ti-chevron-left fs-8"></i> Prev</a>
                    </li>
                    
                    <!-- Pages -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $pageNum) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=tickets&q=<?php echo urlencode($search); ?>&project_id=<?php echo $projectId; ?>&category=<?php echo urlencode($category); ?>&priority=<?php echo urlencode($priority); ?>&status=<?php echo urlencode($status); ?>&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo ($pageNum >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=tickets&q=<?php echo urlencode($search); ?>&project_id=<?php echo $projectId; ?>&category=<?php echo urlencode($category); ?>&priority=<?php echo urlencode($priority); ?>&status=<?php echo urlencode($status); ?>&p=<?php echo $pageNum + 1; ?>">Next <i class="ti ti-chevron-right fs-8"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
