<?php $canViewFinancials = $canViewFinancials ?? can_view_project_financials(); ?>
    <?php require __DIR__ . '/_status_tabs.php'; ?>

    <div class="project-list-panel">
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
                            <?php if ($canViewFinancials): ?>
                                <th class="py-3">Project Cost</th>
                            <?php endif; ?>
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
                                        <a href="<?php echo route('projects-view', ['project_code' => $proj['project_code']]); ?>" class="text-decoration-none text-dark hover-primary">
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
                                <?php if ($canViewFinancials): ?>
                                    <td class="font-weight-semibold text-dark">
                                        <?php echo format_rs_currency($proj['project_cost'] ?? 0); ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge <?php echo project_status_badge_class($proj['status']); ?> px-2 py-1 fs-8 rounded">
                                        <?php echo e(project_display_status($proj['status'])); ?>
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
                                        <a href="<?php echo route('projects-view', ['project_code' => $proj['project_code']]); ?>" class="btn btn-outline-secondary btn-icon" title="View details">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                                            <button type="button" class="btn btn-outline-primary btn-icon" title="Edit project"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#projectEditModal"
                                                    data-code="<?php echo $proj['project_code']; ?>"
                                                    onclick="openProjectEditModal(this)">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-icon" title="Manage team members"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#projectMembersModal"
                                                    data-code="<?php echo $proj['project_code']; ?>"
                                                    onclick="openProjectMembersModal(this)">
                                                <i class="ti ti-users"></i>
                                            </button>
                                            <?php if ($proj['is_archived']): ?>
                                                <a href="<?php echo route('projects-archive', ['project_code' => $proj['project_code'], 'archive' => 0]); ?>" 
                                                   class="btn btn-outline-success btn-icon ajax-link" 
                                                   title="Restore project"
                                                   data-confirm="Are you sure you want to restore this project?">
                                                    <i class="ti ti-rotate-clockwise"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo route('projects-archive', ['project_code' => $proj['project_code'], 'archive' => 1]); ?>" 
                                                   class="btn btn-outline-danger btn-icon ajax-link" 
                                                   title="Archive project"
                                                   data-confirm="Are you sure you want to archive this project?">
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
                        <a class="page-link ajax-partial-link" href="<?php echo route('projects', ['q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $pageNum - 1]); ?>"><i class="ti ti-chevron-left fs-8"></i> Prev</a>
                    </li>
                    
                    <!-- Pages -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $pageNum) ? 'active' : ''; ?>">
                            <a class="page-link ajax-partial-link" href="<?php echo route('projects', ['q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $i]); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo ($pageNum >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link ajax-partial-link" href="<?php echo route('projects', ['q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $pageNum + 1]); ?>">Next <i class="ti ti-chevron-right fs-8"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
    </div>
