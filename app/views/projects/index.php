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
                                <a href="<?php echo route('projects'); ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                                    <i class="ti ti-folders"></i> View Active
                                </a>
                            <?php else: ?>
                                <a href="<?php echo route('projects', ['archived' => 1]); ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
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
                <a href="<?php echo route('projects', ['archived' => $archiveFilter]); ?>" class="btn btn-outline-secondary btn-icon" title="Clear Filters"><i class="ti ti-x"></i></a>
            <?php endif; ?>
        </form>
        
        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <div>
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2 font-weight-medium" data-bs-toggle="modal" data-bs-target="#projectCreateModal">
                    <i class="ti ti-plus"></i> New Project
                </button>
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
                                                   data-confirm="Are you sure you want to archive this project? It will be hidden from main dashboards.">
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
                        <a class="page-link" href="<?php echo route('projects', ['q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $pageNum - 1]); ?>"><i class="ti ti-chevron-left fs-8"></i> Prev</a>
                    </li>
                    
                    <!-- Pages -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $pageNum) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo route('projects', ['q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $i]); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo ($pageNum >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo route('projects', ['q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $pageNum + 1]); ?>">Next <i class="ti ti-chevron-right fs-8"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
<!-- Create Project Modal -->
<div class="modal fade" id="projectCreateModal" tabindex="-1" aria-labelledby="projectCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="<?php echo route('projects-create'); ?>" method="POST" class="modal-content ajax-form">
            <div class="modal-header">
                <h5 class="modal-title" id="projectCreateModalLabel"><i class="ti ti-folder-plus me-2"></i> Create New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label required">Project Name</label>
                        <input type="text" name="project_name" class="form-control" placeholder="e.g. Acme Corp Portal Integration" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Project Code</label>
                        <input type="text" name="project_code" class="form-control" placeholder="e.g. ACM-PORT" required>
                        <small class="text-muted fs-8">Unique uppercase code (max 20 chars)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="client_name" class="form-control" placeholder="e.g. Mark Spencer">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Organization Name</label>
                        <input type="text" name="organization_name" class="form-control" placeholder="e.g. Acme Corporation">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Project Type</label>
                        <select name="project_type" class="form-select">
                            <option value="Web Application" selected>Web Application</option>
                            <option value="Mobile App Development">Mobile App Development</option>
                            <option value="API Integration">API Integration</option>
                            <option value="SaaS Platform">SaaS Platform</option>
                            <option value="Consultancy & Research">Consultancy & Research</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Priority Level</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Technology Stack</label>
                        <input type="text" name="technology_stack" class="form-control" placeholder="e.g. PHP 8, Laravel, MySQL, Bootstrap 5">
                        <small class="text-muted fs-8">Comma-separated list of major languages, frameworks, or databases.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expected End Date</label>
                        <input type="date" name="expected_end_date" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Project Description</label>
                        <textarea name="project_description" rows="4" class="form-control" placeholder="Summarize project scope, deliverables, and major milestones..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Project Status</label>
                        <select name="status" class="form-select">
                            <option value="Proposal Received" selected>Proposal Received</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="projectEditModal" tabindex="-1" aria-labelledby="projectEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="projectEditForm" method="POST" class="modal-content ajax-form">
            <div class="modal-header">
                <h5 class="modal-title" id="projectEditModalLabel"><i class="ti ti-edit me-2"></i> Edit Project Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label required">Project Name</label>
                        <input type="text" name="project_name" id="editProjectName" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Project Code</label>
                        <input type="text" name="project_code" id="editProjectCode" class="form-control" required readonly>
                        <small class="text-muted fs-8">Project code cannot be changed once created.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="client_name" id="editClientName" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Organization Name</label>
                        <input type="text" name="organization_name" id="editOrganizationName" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Project Type</label>
                        <select name="project_type" id="editProjectType" class="form-select">
                            <option value="Web Application">Web Application</option>
                            <option value="Mobile App Development">Mobile App Development</option>
                            <option value="API Integration">API Integration</option>
                            <option value="SaaS Platform">SaaS Platform</option>
                            <option value="Consultancy & Research">Consultancy & Research</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Priority Level</label>
                        <select name="priority" id="editPriority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Technology Stack</label>
                        <input type="text" name="technology_stack" id="editTechnologyStack" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="editStartDate" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expected End Date</label>
                        <input type="date" name="expected_end_date" id="editExpectedEndDate" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Project Description</label>
                        <textarea name="project_description" id="editProjectDescription" rows="4" class="form-control"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Project Status</label>
                        <select name="status" id="editStatus" class="form-select">
                            <option value="Proposal Received">Proposal Received</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Team Members Modal -->
<div class="modal fade" id="projectMembersModal" tabindex="-1" aria-labelledby="projectMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectMembersModalLabel"><i class="ti ti-users me-2"></i> Manage Team Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add Member Form -->
                <form id="projectAddMemberForm" class="ajax-form mb-4" method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="add">
                    <label class="form-label required font-weight-semibold">Assign New Team Member</label>
                    <div class="d-flex gap-2">
                        <select name="user_id" id="availableUsersSelect" class="form-select" required>
                            <option value="">Select a user...</option>
                        </select>
                        <button type="submit" class="btn btn-primary px-3">Assign</button>
                    </div>
                </form>

                <!-- Current Members List -->
                <h6 class="font-weight-bold mb-2">Current Assigned Members</h6>
                <div class="border rounded" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-3 py-2">Name</th>
                                <th class="py-2">Role</th>
                                <th class="pe-3 py-2 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="projectMembersTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openProjectEditModal(button) {
        const code = button.dataset.code;
        const form = document.getElementById('projectEditForm');
        
        showLoader();
        $.ajax({
            url: '<?php echo route("projects-edit", ["project_code" => "__CODE__"]); ?>'.replace('__CODE__', code),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoader();
                if (response && response.success) {
                    const proj = response.project;
                    form.action = '<?php echo route("projects-edit", ["project_code" => "__CODE__"]); ?>'.replace('__CODE__', proj.project_code);
                    document.getElementById('editProjectName').value = proj.project_name || '';
                    document.getElementById('editProjectCode').value = proj.project_code || '';
                    document.getElementById('editClientName').value = proj.client_name || '';
                    document.getElementById('editOrganizationName').value = proj.organization_name || '';
                    document.getElementById('editProjectDescription').value = proj.project_description || '';
                    document.getElementById('editProjectType').value = proj.project_type || 'Web Application';
                    document.getElementById('editTechnologyStack').value = proj.technology_stack || '';
                    document.getElementById('editStartDate').value = proj.start_date || '';
                    document.getElementById('editExpectedEndDate').value = proj.expected_end_date || '';
                    document.getElementById('editPriority').value = proj.priority || 'medium';
                    document.getElementById('editStatus').value = proj.status || 'Proposal Received';
                } else {
                    showToast(response.message || 'Failed to fetch project details.', 'danger');
                }
            },
            error: function() {
                hideLoader();
                showToast('Failed to fetch project details.', 'danger');
            }
        });
    }

    function openProjectMembersModal(button) {
        const code = button.dataset.code;
        const select = document.getElementById('availableUsersSelect');
        const tableBody = document.getElementById('projectMembersTableBody');
        const form = document.getElementById('projectAddMemberForm');
        
        form.action = '<?php echo route("projects-team", ["project_code" => "__CODE__"]); ?>'.replace('__CODE__', code);

        showLoader();
        $.ajax({
            url: '<?php echo route("projects-team", ["project_code" => "__CODE__"]); ?>'.replace('__CODE__', code),
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                hideLoader();
                if (response && response.success) {
                    select.innerHTML = '<option value="">Select a user...</option>';
                    response.availableUsers.forEach(function(user) {
                        select.innerHTML += `<option value="${user.id}">${user.full_name} (${user.role})</option>`;
                    });

                    tableBody.innerHTML = '';
                    if (response.members.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="3" class="text-center text-muted p-3">No assigned team members.</td></tr>';
                    } else {
                        response.members.forEach(function(user) {
                            const removeUrl = '<?php echo route("projects-team", ["project_code" => "__CODE__"]); ?>'.replace('__CODE__', code);
                            tableBody.innerHTML += `
                                <tr>
                                    <td class="ps-3 py-2">
                                        <div class="font-weight-semibold text-dark">${user.full_name}</div>
                                        <div class="text-muted fs-8">${user.email}</div>
                                    </td>
                                    <td><span class="badge badge-role badge-${user.role} text-uppercase" style="font-size: 10px;">${user.role}</span></td>
                                    <td class="pe-3 py-2 text-end">
                                        <form class="ajax-form d-inline" method="POST" action="${removeUrl}">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="user_id" value="${user.user_id}">
                                            <button type="submit" class="btn btn-outline-danger btn-icon border-0" style="width:28px; height:28px; padding:0;" title="Remove Member">
                                                <i class="ti ti-user-minus"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                } else {
                    showToast(response.message || 'Failed to load team data.', 'danger');
                }
            },
            error: function() {
                hideLoader();
                showToast('Failed to load team data.', 'danger');
            }
        });
    }
</script>
<?php endif; ?>
