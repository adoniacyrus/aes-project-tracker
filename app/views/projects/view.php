<div class="row row-cards mb-4">
    <!-- Project Title Header -->
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-md-items-center gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 p-0 bg-transparent">
                            <li class="breadcrumb-item"><a href="?page=projects" class="text-decoration-none">Projects</a></li>
                            <li class="breadcrumb-item active text-dark" aria-current="page"><?php echo e($project['project_code']); ?></li>
                        </ol>
                    </nav>
                    <h2 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary font-monospace fs-5 px-2.5 py-1.5"><?php echo e($project['project_code']); ?></span>
                        <?php echo e($project['project_name']); ?>
                    </h2>
                </div>
                <div class="d-flex gap-2">
                    <a href="?page=projects" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-left"></i> Back to List
                    </a>
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <a href="?page=projects-team&id=<?php echo $project['id']; ?>" class="btn btn-outline-primary d-flex align-items-center gap-2">
                            <i class="ti ti-users"></i> Manage Team
                        </a>
                        <a href="?page=projects-edit&id=<?php echo $project['id']; ?>" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="ti ti-edit"></i> Edit Details
                        </a>
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
                <a href="?page=tickets-create&project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-1 font-weight-medium">
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
                                    <th class="py-2.5">Assignee</th>
                                    <th class="py-2.5">Category</th>
                                    <th class="py-2.5">Priority</th>
                                    <th class="py-2.5">Status</th>
                                    <th class="py-2.5 px-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $tick): ?>
                                    <tr>
                                        <td class="px-3 font-monospace font-weight-semibold">#<?php echo $tick['id']; ?></td>
                                        <td>
                                            <a href="?page=tickets-view&id=<?php echo $tick['id']; ?>" class="text-decoration-none text-dark font-weight-medium">
                                                <?php echo e($tick['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php if ($tick['assignee_first']): ?>
                                                <?php echo e($tick['assignee_first'] . ' ' . $tick['assignee_last']); ?>
                                            <?php else: ?>
                                                <span class="text-muted-custom italic fs-8">Unassigned</span>
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
                                        <td>
                                            <span class="badge bg-secondary px-1.5 py-0.5 fs-8 rounded">
                                                <?php echo e($tick['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-3 text-end">
                                            <a href="?page=tickets-view&id=<?php echo $tick['id']; ?>" class="btn btn-outline-secondary btn-sm p-1 fs-8" title="View Ticket">
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
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Priority</span>
                    <div class="mt-1">
                        <?php 
                            $priorityClass = 'bg-secondary-subtle text-secondary';
                            if ($project['priority'] === 'critical') $priorityClass = 'bg-danger-subtle text-danger';
                            if ($project['priority'] === 'high') $priorityClass = 'bg-warning-subtle text-warning-emphasis';
                            if ($project['priority'] === 'medium') $priorityClass = 'bg-primary-subtle text-primary';
                        ?>
                        <span class="badge <?php echo $priorityClass; ?> text-capitalize px-2 py-1 fs-7">
                            <?php echo e($project['priority']); ?>
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
                        <?php echo e($project['creator_first'] . ' ' . $project['creator_last']); ?> 
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
                                    <?php echo strtoupper(substr($mem['first_name'], 0, 1) . substr($mem['last_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-fill leading-tight">
                                    <p class="mb-0 font-weight-semibold text-dark fs-7"><?php echo e($mem['first_name'] . ' ' . $mem['last_name']); ?></p>
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
