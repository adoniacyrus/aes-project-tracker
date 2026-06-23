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
        <form method="GET" action="<?php echo route('projects'); ?>" class="d-flex flex-fill max-width-md align-items-center gap-2 ajax-filter-form" data-ajax-target="#projects-ajax-content">
            <input type="hidden" name="partial" value="1">
            <input type="hidden" name="archived" value="<?php echo $archiveFilter; ?>">
            
            <div class="input-group input-group-flat">
                <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-1" placeholder="Search by name, code, client..." value="<?php echo e($search); ?>">
            </div>
            
            <select name="status" class="form-select max-width-xs">
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

    <div id="projects-ajax-content" data-ajax-container data-ajax-refresh-url="<?php echo e(route('projects', ['partial' => 1, 'q' => $search, 'status' => $statusFilter, 'archived' => $archiveFilter, 'p' => $pageNum])); ?>">
        <?php $canViewFinancials = $canViewFinancials ?? can_view_project_financials(); ?>
        <?php require __DIR__ . '/_list_content.php'; ?>
    </div>
</div>

<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
<!-- Create Project Modal -->
<div class="modal fade" id="projectCreateModal" tabindex="-1" aria-labelledby="projectCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="<?php echo route('projects-create'); ?>" method="POST" class="modal-content ajax-form" data-ajax-reset="true" data-ajax-refresh="#projects-ajax-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectCreateModalLabel"><i class="ti ti-folder-plus me-2"></i> Create New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label required">Project Name</label>
                        <input type="text" name="project_name" class="form-control" placeholder="e.g. Acme Corp Portal Integration" required>
                    </div>
                    <div class="col-md-12">
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
                        <label class="form-label required">Project Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="project_cost" class="form-control" placeholder="150000" min="0.01" step="0.01" required>
                        </div>
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
        <form id="projectEditForm" method="POST" class="modal-content ajax-form" data-ajax-refresh="#projects-ajax-content">
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
                        <label class="form-label required">Project Cost</label>
                        <div class="input-group">
                            <span class="input-group-text">Rs.</span>
                            <input type="number" name="project_cost" id="editProjectCost" class="form-control" min="0.01" step="0.01" required>
                        </div>
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
                    document.getElementById('editProjectCost').value = proj.project_cost || '';
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
        $('#projectMembersModal').data('project-code', code);
        reloadProjectMembersModal(code);
    }

    function reloadProjectMembersModal(code) {
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
