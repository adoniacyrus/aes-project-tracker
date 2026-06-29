    <?php require __DIR__ . '/_status_tabs.php'; ?>

    <div class="ticket-list-panel">
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
                            <th class="py-3 px-4">Title</th>
                            <th class="py-3">Project</th>
                            <th class="py-3">Category</th>
                            <th class="py-3">Priority</th>
                            <th class="py-3">Status</th>
                            <?php if ($showTeamVisibility ?? false): ?><th class="py-3">Team Access</th><?php endif; ?>
                            <th class="py-3">Due Date</th>
                            <th class="py-3 px-4 text-end" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $tick): ?>
                            <tr>
                                <td class="px-4">
                                    <div class="font-weight-semibold">
                                        <a href="<?php echo route('tickets-view', ['id' => $tick['id'], 'project_code' => $tick['project_code']]); ?>" class="text-decoration-none text-dark hover-primary">
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
                                        $displayStatus = ticket_display_status($tick);
                                        $statusClass = ticket_display_status_badge_class($displayStatus);
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?> px-2 py-1 fs-8 rounded">
                                        <?php echo e($displayStatus); ?>
                                    </span>
                                </td>
                                <?php if ($showTeamVisibility ?? false): ?>
                                <td>
                                    <?php if (is_ticket_visible_to_project_team($tick)): ?>
                                        <span class="badge bg-success-subtle text-success border fs-8">Visible</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border fs-8">Hidden</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="text-secondary fs-7">
                                    <?php echo $tick['due_date'] ? date('M d, Y', strtotime($tick['due_date'])) : '<span class="text-muted fs-8 italic">None</span>'; ?>
                                </td>
                                <td class="px-4 text-end">
                                    <a href="<?php echo route('tickets-view', ['id' => $tick['id'], 'project_code' => $tick['project_code']]); ?>" class="btn btn-outline-secondary btn-icon" title="View Details">
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

    <?php if (($totalPages ?? 1) > 1): ?>
        <div class="card-footer bg-transparent border-top py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-secondary fs-7">
                Showing Page <strong><?php echo $pageNum; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo $totalTickets; ?> tickets)
            </span>
            <nav aria-label="Tickets Page Navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo ($pageNum <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link ajax-partial-link" href="<?php echo route('tickets', ['q' => $search, 'project_id' => $projectId, 'category' => $category, 'priority' => $priority, 'status' => $status, 'p' => $pageNum - 1]); ?>"><i class="ti ti-chevron-left fs-8"></i> Prev</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $pageNum) ? 'active' : ''; ?>">
                            <a class="page-link ajax-partial-link" href="<?php echo route('tickets', ['q' => $search, 'project_id' => $projectId, 'category' => $category, 'priority' => $priority, 'status' => $status, 'p' => $i]); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($pageNum >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link ajax-partial-link" href="<?php echo route('tickets', ['q' => $search, 'project_id' => $projectId, 'category' => $category, 'priority' => $priority, 'status' => $status, 'p' => $pageNum + 1]); ?>">Next <i class="ti ti-chevron-right fs-8"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
    </div>
