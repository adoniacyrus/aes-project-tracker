<div class="row row-cards mb-4">
    <!-- Project Title Header -->
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-md-items-center gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 p-0 bg-transparent">
                            <li class="breadcrumb-item"><a href="<?php echo route('projects'); ?>" class="text-decoration-none">Projects</a></li>
                            <li class="breadcrumb-item active text-dark" aria-current="page"><?php echo e($project['project_code']); ?></li>
                        </ol>
                    </nav>
                    <h2 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary font-monospace fs-5 px-2.5 py-1.5"><?php echo e($project['project_code']); ?></span>
                        <?php echo e($project['project_name']); ?>
                    </h2>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo route('projects'); ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-left"></i> Back to List
                    </a>
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#projectMembersModal"
                                data-code="<?php echo $project['project_code']; ?>"
                                onclick="openProjectMembersModal(this)">
                            <i class="ti ti-users"></i> Manage Team
                        </button>
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#projectEditModal"
                                data-code="<?php echo $project['project_code']; ?>"
                                onclick="openProjectEditModal(this)">
                            <i class="ti ti-edit"></i> Edit Details
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Description, Tech Stack, Tickets -->
    <div class="col-12 col-lg-8">
        <!-- Overview -->
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-book-open me-2 text-primary fs-4"></i> Overview & Scope
            </div>
            <div class="card-body px-4 py-3">
                <h5 class="font-weight-semibold text-dark">Scope / Description</h5>
                <p class="text-secondary leading-relaxed fs-6">
                    <?php echo nl2br(e($project['project_description'] ?: 'No detailed scope description provided for this project.')); ?>
                </p>

                <hr class="my-4 text-muted opacity-25">

                <h5 class="font-weight-semibold text-dark mb-2">Technology Stack</h5>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                    $stack = array_filter(array_map('trim', explode(',', $project['technology_stack'] ?? '')));
                    if (empty($stack)):
                    ?>
                        <span class="text-muted italic fs-7">No technologies defined.</span>
                    <?php else: 
                        foreach ($stack as $tech):
                    ?>
                        <span class="badge bg-light border text-dark font-weight-medium px-2.5 py-1.5 fs-7 rounded-pill">
                            <i class="ti ti-brand-open-source text-secondary me-1"></i><?php echo e($tech); ?>
                        </span>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </div>
            </div>
        </div>

        <!-- Project Tickets -->
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2">
                    <i class="ti ti-ticket text-primary fs-4"></i> Recent Project Tickets
                </span>
                <a href="<?php echo route('tickets-create', ['project_id' => $project['id']]); ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-1 font-weight-medium">
                    <i class="ti ti-plus"></i> Create Ticket
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($tickets)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="ti ti-ticket-off fs-1 mb-2 text-secondary"></i>
                        <p class="mb-0 fs-7">No tickets filed for this project yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter card-table mb-0 fs-7 align-middle">
                            <thead>
                                <tr class="bg-light">
                                    <th class="py-2.5 px-3">Ticket ID</th>
                                    <th class="py-2.5">Title</th>
                                    <th class="py-2.5">Team Access</th>
                                    <th class="py-2.5">Category</th>
                                    <th class="py-2.5">Priority</th>
                                    <?php if ($canViewFinancials ?? can_view_project_financials()): ?>
                                        <th class="py-2.5">Ticket Cost</th>
                                    <?php endif; ?>
                                    <th class="py-2.5">Status</th>
                                    <th class="py-2.5 px-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $tick): ?>
                                    <tr>
                                        <td class="px-3 font-monospace font-weight-semibold">#<?php echo $tick['id']; ?></td>
                                        <td>
                                            <a href="<?php echo route('tickets-view', ['id' => $tick['id'], 'title' => $tick['title']]); ?>" class="text-decoration-none text-dark font-weight-medium">
                                                <?php echo e($tick['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if (is_ticket_visible_to_project_team($tick)): ?>
                                                <span class="badge bg-success-subtle text-success border fs-8">Visible</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border fs-8">Hidden</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light border text-dark font-weight-normal fs-8">
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
                                            <span class="badge <?php echo $prioClass; ?> text-capitalize px-1.5 py-0.5 fs-8">
                                                <?php echo e($tick['priority']); ?>
                                            </span>
                                        </td>
                                        <?php if ($canViewFinancials ?? can_view_project_financials()): ?>
                                            <td class="font-weight-medium">
                                                <?php if (!empty($tick['estimated_cost'])): ?>
                                                    <?php echo format_rs_currency($tick['estimated_cost']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted fs-8">—</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="badge bg-secondary px-1.5 py-0.5 fs-8 rounded">
                                                <?php echo e($tick['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 text-end">
                                            <a href="<?php echo route('tickets-view', ['id' => $tick['id'], 'title' => $tick['title']]); ?>" class="btn btn-outline-secondary btn-sm p-1 fs-8" title="View Ticket">
                                                <i class="ti ti-eye"></i> View
                                            </a>
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

    <!-- Right Column: Metadata, Dates, Members -->
    <div class="col-12 col-lg-4">
        <?php $canViewFinancials = $canViewFinancials ?? can_view_project_financials(); ?>
        <?php if ($canViewFinancials): ?>
        <!-- Financial Summary -->
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-currency-rupee text-primary me-2 fs-4"></i> Financial Summary
            </div>
            <div class="card-body px-4 py-3">
                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Project Cost</span>
                    <p class="mb-0 font-weight-bold text-dark fs-5 mt-1">
                        <?php echo format_rs_currency($project['project_cost'] ?? 0); ?>
                    </p>
                </div>
                <div class="mb-0">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Total Ticket Cost</span>
                    <p class="mb-0 font-weight-bold text-success fs-5 mt-1">
                        <?php echo format_rs_currency($totalTicketRevenue ?? 0); ?>
                    </p>
                    <small class="text-muted fs-8">Sum of approved ticket estimated costs</small>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Project Stats & Dates -->
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-info-circle text-primary me-2 fs-4"></i> Project Metadata
            </div>
            <div class="card-body px-4 py-3">
                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Current Status</span>
                    <div class="mt-1">
                        <?php 
                            $statusClass = 'bg-secondary';
                            if ($project['status'] === 'Proposal Received') $statusClass = 'bg-info text-white';
                            if ($project['status'] === 'In Progress') $statusClass = 'bg-primary text-white';
                            if ($project['status'] === 'Maintenance') $statusClass = 'bg-purple text-white'; // Custom styling
                            if ($project['status'] === 'On Hold') $statusClass = 'bg-warning text-dark';
                            if ($project['status'] === 'Cancelled') $statusClass = 'bg-secondary text-white';
                            if ($project['status'] === 'Completed') $statusClass = 'bg-success text-white';
                        ?>
                        <span class="badge <?php echo $statusClass; ?> px-2.5 py-1.5 fs-7 rounded" <?php echo ($project['status'] === 'Maintenance') ? 'style="background-color: #6610f2 !important; color: white;"' : ''; ?>>
                            <?php echo e($project['status']); ?>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Client Information</span>
                    <div class="mt-1">
                        <p class="mb-0 font-weight-semibold text-dark fs-6"><?php echo e($project['client_name'] ?: 'N/A'); ?></p>
                        <small class="text-muted-custom fs-7"><?php echo e($project['organization_name'] ?: 'AES'); ?></small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Start Date</span>
                        <p class="mb-0 font-weight-medium fs-6 text-dark mt-1">
                            <?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : '<span class="text-muted fs-7">N/A</span>'; ?>
                        </p>
                    </div>
                    <div class="col-6">
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Due Date</span>
                        <p class="mb-0 font-weight-medium fs-6 text-dark mt-1">
                            <?php echo $project['expected_end_date'] ? date('M d, Y', strtotime($project['expected_end_date'])) : '<span class="text-muted fs-7">N/A</span>'; ?>
                        </p>
                    </div>
                </div>

                <div class="mb-0">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Created By</span>
                    <p class="mb-0 fs-7 text-muted mt-1">
                        <?php echo e($project['creator_name']); ?> 
                        on <?php echo date('M d, Y', strtotime($project['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Project Team Members -->
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2">
                    <i class="ti ti-users text-primary fs-4"></i> Team Mappings
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold rounded px-2 py-1 fs-8">
                    <?php echo count($members); ?>
                </span>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($members)): ?>
                    <p class="text-muted italic text-center mb-0 py-2 fs-7">No team members assigned.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($members as $mem): ?>
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="avatar bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 32px; height: 32px; font-size: 11px;">
                                    <?php echo user_initials($mem['full_name']); ?>
                                </div>
                                <div class="flex-fill leading-tight">
                                    <p class="mb-0 font-weight-semibold text-dark fs-7"><?php echo e($mem['full_name']); ?></p>
                                    <small class="text-muted fs-8 text-capitalize"><?php echo e($mem['role']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
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
                    <div class="col-md-8">
                        <label class="form-label">Project Code</label>
                        <input type="text" id="editProjectCode" class="form-control" readonly disabled>
                        <small class="text-muted fs-8">Project code is assigned at creation and cannot be changed.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Client Sponsor Name</label>
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
                                        ${window.buildProjectMemberRemoveCell(user, removeUrl)}
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    initTooltipsIn(tableBody);
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
