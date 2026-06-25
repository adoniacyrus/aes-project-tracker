<?php
$userRole = $_SESSION['user_role'] ?? '';
$canDiscuss = TicketWorkflowService::canViewDiscussion($userRole);
$canTeamChat = can_access_team_chat($userRole);
$showTeamChatWidget = $canTeamChat;
$showClientChatWidget = can_access_client_chat($userRole);
$canEditEstimation = can_edit_ticket_estimation($userRole);
$isAdmin = ($userRole === 'admin');
$canEditTicket = can_edit_ticket($userRole, $ticket);
?>
<style>
.attachment-preview-trigger {
    cursor: pointer;
}
.attachment-preview-trigger:hover .attachment-preview-thumb {
    opacity: 0.9;
}
</style>

<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-md-items-center gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 p-0 bg-transparent">
                            <li class="breadcrumb-item"><a href="<?php echo route('tickets'); ?>" class="text-decoration-none">Tickets</a></li>
                            <li class="breadcrumb-item active text-dark" aria-current="page">Ticket #<?php echo $ticket['id']; ?></li>
                        </ol>
                    </nav>
                    <h3 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                        <span class="badge bg-secondary-subtle text-secondary font-monospace fs-6">#<?php echo $ticket['id']; ?></span>
                        <?php echo e($ticket['title']); ?>
                    </h3>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo route('tickets'); ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
                    <?php if ($canEditTicket): ?>
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#ticketEditModal"
                                data-id="<?php echo $ticket['id']; ?>"
                                onclick="openTicketEditModal(this)">
                            <i class="ti ti-edit"></i> Edit Ticket
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">

        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-semibold">
                    <i class="ti ti-notes text-primary fs-4"></i> Description
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold px-2.5 py-1.5 fs-7 rounded-pill">
                    <?php echo e($ticket['category']); ?>
                </span>
            </div>
            <div class="card-body px-4 py-3">
                <p class="text-secondary leading-relaxed fs-6 mb-0" style="white-space: pre-line;"><?php echo e($ticket['description']); ?></p>
            </div>
        </div>

        <div id="ticket-attachments" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'attachments'])); ?>">
            <?php require __DIR__ . '/_attachments.php'; ?>
        </div>

        <div id="ticket-dynamic-content" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'dynamic'])); ?>">
            <?php require __DIR__ . '/_dynamic_content.php'; ?>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-12 col-lg-4 ticket-sidebar">
        <div id="ticket-dynamic-sidebar" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'sidebar'])); ?>">
            <?php require __DIR__ . '/_workflow_sidebar.php'; ?>
        </div>

        <?php require __DIR__ . '/_cost_estimation_card.php'; ?>

        <div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
            <div class="ticket-sidebar-card__head">
                <i class="ti ti-info-circle text-primary"></i>
                <span>Properties</span>
            </div>
            <div class="ticket-sidebar-card__body">
                <dl class="ticket-meta-grid mb-0">
                    <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                        <dt>Project</dt>
                        <dd><?php echo e($ticket['project_name']); ?> <span class="text-muted">(<?php echo e($ticket['project_code']); ?>)</span></dd>
                    </div>
                    <div class="ticket-meta-grid__item">
                        <dt>Priority</dt>
                        <dd>
                            <span class="badge bg-primary-subtle text-primary text-capitalize ticket-priority-badge"><?php echo e($ticket['priority']); ?></span>
                        </dd>
                    </div>
                    <div class="ticket-meta-grid__item">
                        <dt>Filed</dt>
                        <dd><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></dd>
                    </div>
                    <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                        <dt>Created by</dt>
                        <dd><?php echo e($ticket['creator_name']); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php if ($canEditTicket): ?>
<!-- Edit Ticket Modal -->
<div class="modal fade" id="ticketEditModal" tabindex="-1" aria-labelledby="ticketEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="ticketEditForm" method="POST" class="modal-content ajax-form" data-ajax-refresh="#ticket-dynamic-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketEditModalLabel"><i class="ti ti-edit me-2"></i> Edit Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required font-weight-semibold">Associated Project</label>
                        <select name="project_id" id="editProjectSelect" class="form-select" required>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required font-weight-semibold">Category</label>
                        <select name="category" id="editCategorySelect" class="form-select" required <?php echo $userRole !== 'admin' ? 'disabled' : ''; ?>>
                            <option value="Bug Fix">Bug Fix</option>
                            <option value="New Feature Request">New Feature Request</option>
                            <option value="Enhancement Request">Enhancement Request</option>
                            <option value="Technical Support">Technical Support</option>
                        </select>
                        <?php if ($userRole !== 'admin'): ?>
                            <input type="hidden" name="category" id="editCategoryHidden">
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label required font-weight-semibold">Ticket Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label required font-weight-semibold">Description</label>
                        <textarea name="description" id="editDescription" rows="6" class="form-control" required></textarea>
                    </div>
                    <div class="col-md-6 col-6">
                        <label class="form-label font-weight-semibold">Priority</label>
                        <select name="priority" id="editPriority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-6">
                        <label class="form-label font-weight-semibold">Due Date</label>
                        <input type="date" name="due_date" id="editDueDate" class="form-control">
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold">Status</label>
                        <select name="status" id="editStatus" class="form-select">
                            <?php foreach (TicketWorkflowService::getSimplifiedStatuses() as $st): ?>
                                <option value="<?php echo e($st); ?>"><?php echo e($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="status" id="editStatusHidden">
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTicketEditModal(button) {
    const id = button.dataset.id;
    const form = document.getElementById('ticketEditForm');
    
    showLoader();
    $.ajax({
        url: '<?php echo route("tickets-edit", ["id" => "__ID__"]); ?>'.replace('__ID__', id),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            hideLoader();
            if (response && response.success) {
                const ticket = response.ticket;

                form.action = '<?php echo route("tickets-edit", ["id" => "__ID__"]); ?>'.replace('__ID__', ticket.id);
                
                // Populate projects select
                const projectSelect = document.getElementById('editProjectSelect');
                projectSelect.innerHTML = '';
                response.projects.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = `${p.project_name} (${p.project_code})`;
                    if (parseInt(p.id) === parseInt(ticket.project_id)) opt.selected = true;
                    projectSelect.appendChild(opt);
                });

                // Populate category
                const catSelect = document.getElementById('editCategorySelect');
                if (catSelect) {
                    catSelect.value = ticket.category || 'Bug Fix';
                }
                const catHidden = document.getElementById('editCategoryHidden');
                if (catHidden) {
                    catHidden.value = ticket.category || 'Bug Fix';
                }

                // Populate title, description, priority, due_date
                document.getElementById('editTitle').value = ticket.title || '';
                document.getElementById('editDescription').value = ticket.description || '';
                document.getElementById('editPriority').value = ticket.priority || 'medium';
                document.getElementById('editDueDate').value = ticket.due_date ? ticket.due_date.substring(0, 10) : '';

                // Populate status
                const statusSelect = document.getElementById('editStatus');
                if (statusSelect) {
                    statusSelect.value = ticket.display_status || ticket.status || 'Initiated';
                }
                const statusHidden = document.getElementById('editStatusHidden');
                if (statusHidden) {
                    statusHidden.value = ticket.status || 'Open';
                }
            } else {
                showToast(response.message || 'Failed to fetch ticket details.', 'danger');
            }
        },
        error: function() {
            hideLoader();
            showToast('Failed to fetch ticket details.', 'danger');
        }
    });
}
</script>
<?php endif; ?>

<!-- Attachment Preview Modal -->
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1" aria-labelledby="attachmentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-3">
                <div class="d-flex align-items-center gap-2 min-w-0 flex-fill">
                    <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" id="attachmentPreviewPrev" aria-label="Previous attachment">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="min-w-0 flex-fill text-center px-2">
                        <h5 class="modal-title fs-6 mb-0 text-truncate" id="attachmentPreviewModalLabel"></h5>
                        <small class="text-muted fs-8" id="attachmentPreviewMeta"></small>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" id="attachmentPreviewNext" aria-label="Next attachment">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4 bg-light-subtle" id="attachmentPreviewBody" style="min-height: 280px;">
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Loading preview...
                </div>
            </div>
            <div class="modal-footer py-2 justify-content-between">
                <small class="text-muted fs-8" id="attachmentPreviewCounter"></small>
                <div class="d-flex gap-2">
                    <a href="#" id="attachmentPreviewDownload" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">
                        <i class="ti ti-external-link"></i> Open in New Tab
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
