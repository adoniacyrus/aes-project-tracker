<?php
$userRole = $_SESSION['user_role'] ?? '';
$showTeamVisibility = ($userRole !== 'client');
?>
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
                        <?php if (!empty($canCreateTicket)): ?>
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ticketCreateModal">
                            <i class="ti ti-plus"></i> Create Ticket
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm border border-light">
    <!-- Header with Search & Filters -->
    <div class="card-header bg-transparent border-bottom py-3 px-4">
        <form method="GET" action="<?php echo route('tickets'); ?>" class="row g-3 ajax-filter-form" data-ajax-target="#tickets-ajax-content">
            <input type="hidden" name="partial" value="1">
            <input type="hidden" name="status" value="<?php echo e($status ?? ''); ?>">
            
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

            <!-- Submit and Clear -->
            <div class="col-lg-3 col-md-4 col-12 d-flex align-items-end">
                <div class="d-flex flex-nowrap align-items-center gap-2">
                    <button type="submit" class="btn btn-primary px-3 py-2 text-nowrap">Filter</button>
                    <?php
                        $clearFiltersUrl = route('tickets', ['partial' => 1]);
                        $clearFiltersTarget = '#tickets-ajax-content';
                        require __DIR__ . '/../partials/_clear_filters_link.php';
                    ?>
                </div>
            </div>
        </form>
    </div>

    <div id="tickets-ajax-content" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets', ['partial' => 1, 'q' => $search, 'project_id' => $projectId, 'category' => $category, 'priority' => $priority, 'status' => $status, 'p' => $pageNum])); ?>">
        <?php require __DIR__ . '/_list_content.php'; ?>
    </div>

    <!-- Ticket Creation Modal -->
    <?php if (!empty($canCreateTicket)): ?>
    <div class="modal fade" id="ticketCreateModal" tabindex="-1" aria-labelledby="ticketCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <form action="<?php echo route('tickets-create'); ?>" method="POST" enctype="multipart/form-data" class="modal-content ajax-form" data-ajax-reset="true" data-ajax-refresh="#tickets-ajax-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ticketCreateModalLabel"><i class="ti ti-ticket me-2"></i> Create New Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Project</label>
                            <select name="project_id" id="ticketProjectSelect" class="form-select" required>
                                <option value="">-- Choose Project --</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo e($p['project_name']); ?> (<?php echo e($p['project_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="Bug Fix">Bug Fix</option>
                                <option value="New Feature Request">New Feature Request</option>
                                <option value="Enhancement Request">Enhancement Request</option>
                                <option value="Technical Support">Technical Support</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Ticket Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Brief summary of the issue..." required>
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Description</label>
                            <textarea name="description" rows="5" class="form-control" placeholder="Explain the issue, steps to reproduce, or feature details..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                            <small class="text-muted fs-8">Images, screenshots, documents (max 10 MB each)</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                </div>
            </form>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
    function clearTicketTabLoading() {
        $('#tickets-ajax-content').removeClass('is-refreshing').attr('aria-busy', 'false');
        $('#ticketStatusTabs .project-status-tab').removeClass('is-loading pe-none');
    }

    $(document).on('click', '#ticketStatusTabs .ajax-partial-link', function(e) {
        const $link = $(this);
        if ($link.hasClass('active')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        $('#ticketStatusTabs .project-status-tab').removeClass('is-loading pe-none');
        $link.addClass('is-loading pe-none');
        $('#tickets-ajax-content').addClass('is-refreshing').attr('aria-busy', 'true');
    });

    $(document).on('ajax:content-updated', function(e, targetSelector, response) {
        if (targetSelector !== '#tickets-ajax-content') return;
        clearTicketTabLoading();
        if (!response || !response.refresh_url) return;
        try {
            const url = new URL(response.refresh_url, window.location.origin);
            $('form.ajax-filter-form input[name="status"]').val(url.searchParams.get('status') || '');
        } catch (err) {}
    });

    $(document).ajaxError(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('partial=1') !== -1 && /tickets/i.test(settings.url)) {
            clearTicketTabLoading();
        }
    });
</script>
