<?php
$userRole = $_SESSION['user_role'] ?? '';
$canDiscuss = TicketWorkflowService::canViewDiscussion($userRole);
$canTeamChat = can_access_team_chat($userRole);
$showTeamChatWidget = $canTeamChat;
$showClientChatWidget = can_access_client_chat($userRole);
$showAdminDevChatWidget = can_access_admin_dev_chat($userRole, $ticket, (int)($_SESSION['user_id'] ?? 0));
$canEditEstimation = can_edit_ticket_estimation($userRole);
$canViewCostEstimation = can_view_ticket_cost_estimation($userRole);
$isAdmin = ($userRole === 'admin');
$canEditTicket = can_edit_ticket($userRole, $ticket);
$canViewReviewComment = can_view_latest_review_comment($userRole, $ticket, (int)($_SESSION['user_id'] ?? 0));
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

<div class="row g-4 ticket-workspace">
    <div class="col-12 col-lg-8 ticket-workspace-main">

        <div id="ticket-information" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'ticket-info'])); ?>">
            <?php require __DIR__ . '/_ticket_information.php'; ?>
        </div>

        <?php if ($canViewReviewComment): ?>
        <div id="ticket-latest-review-comment" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'review-comment'])); ?>">
            <?php require __DIR__ . '/_latest_review_comment.php'; ?>
        </div>
        <?php endif; ?>

        <div id="ticket-attachments" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'attachments'])); ?>">
            <?php require __DIR__ . '/_attachments.php'; ?>
        </div>

        <div id="ticket-dynamic-content" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'dynamic'])); ?>">
            <?php require __DIR__ . '/_dynamic_content.php'; ?>
        </div>

        <?php require __DIR__ . '/_workflow_history.php'; ?>
    </div>

    <div class="col-12 col-lg-4 ticket-sidebar">
        <div id="ticket-workflow" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'workflow'])); ?>">
            <?php require __DIR__ . '/_workflow_card.php'; ?>
        </div>

        <?php if ($canViewCostEstimation): ?>
            <?php require __DIR__ . '/_cost_estimation_card.php'; ?>
        <?php endif; ?>

        <?php if ($userRole !== 'client'): ?>
        <div id="ticket-assigned-team" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'assigned-team'])); ?>">
            <?php require __DIR__ . '/_assigned_team_card.php'; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php if ($canEditTicket): ?>
<!-- Edit Ticket Modal -->
<div class="modal fade" id="ticketEditModal" tabindex="-1" aria-labelledby="ticketEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="ticketEditForm" method="POST" class="modal-content ajax-form">
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

<?php if (can_request_admin_clarification($userRole, $ticket, (int)($_SESSION['user_id'] ?? 0))): ?>
<div class="modal fade" id="requestAdminClarificationModal" tabindex="-1" aria-labelledby="requestAdminClarificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="requestAdminClarificationForm"
              method="POST"
              action="<?php echo route('tickets-request-admin-clarification', ['id' => $ticket['id']]); ?>"
              class="modal-content ajax-form"
              data-ajax-reset="1">
            <div class="modal-header">
                <h5 class="modal-title" id="requestAdminClarificationModalLabel"><i class="ti ti-message-question me-2"></i> Request Admin Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                <p class="text-muted small mb-3">Ask the admin for suggestions or clarification. Your message will be posted to the admin–developer discussion.</p>
                <label for="adminClarificationCommentInput" class="form-label font-weight-semibold">Message <span class="text-danger">*</span></label>
                <textarea name="clarification_comment"
                          id="adminClarificationCommentInput"
                          rows="4"
                          class="form-control"
                          required
                          placeholder="Need clarification on the login flow requirements.&#10;Should we support SSO for this release?"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Send to Admin</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (can_submit_ticket_for_review($userRole, $ticket, (int)($_SESSION['user_id'] ?? 0))): ?>
<div class="modal fade" id="submitForReviewModal" tabindex="-1" aria-labelledby="submitForReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="submitForReviewForm"
              method="POST"
              action="<?php echo route('tickets-submit-review', ['id' => $ticket['id']]); ?>"
              class="modal-content ajax-form"
              data-ajax-reset="1">
            <div class="modal-header">
                <h5 class="modal-title" id="submitForReviewModalLabel"><i class="ti ti-send me-2"></i> Submit for Admin Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                <label for="resolutionCommentInput" class="form-label font-weight-semibold">Resolution Comment <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="resolution_comment"
                          id="resolutionCommentInput"
                          rows="4"
                          class="form-control"
                          placeholder="Login issue resolved.&#10;Added validation.&#10;Tested successfully."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Submit for Review</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="modal fade" id="adminReviewModal" tabindex="-1" aria-labelledby="adminReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div id="adminReviewModalContent">
            <div class="modal-content">
                <div class="modal-body text-center py-5 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Loading review details...
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    const $modal = $('#adminReviewModal');
    if (!$modal.length) return;

    const ticketId = <?php echo (int)$ticket['id']; ?>;
    const approveUrl = <?php echo json_encode(route('tickets-approve-review', ['id' => $ticket['id']])); ?>;
    const returnUrl = <?php echo json_encode(route('tickets-return-development', ['id' => $ticket['id']])); ?>;

    function getCsrfToken() {
        return window.AES_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
    }

    function postReviewAction($trigger, url, data, confirmMessage) {
        const send = function() {
            showLoader();
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    hideLoader();
                    handleAjaxSuccess($trigger, response);
                    if (response && response.success) {
                        $modal.modal('hide');
                    }
                },
                error: function(xhr) {
                    hideLoader();
                    let errorMessage = 'An error occurred while processing your request.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.message) errorMessage = response.message;
                    } catch (e) {}
                    showToast(errorMessage, 'danger');
                }
            });
        };

        if (confirmMessage) {
            aesConfirmAction(confirmMessage, send);
            return;
        }
        send();
    }

    $modal.on('show.bs.modal', function(event) {
        const $trigger = $(event.relatedTarget);
        const loadUrl = $trigger.attr('data-load-url');
        const $content = $('#adminReviewModalContent');
        if (!loadUrl || !$content.length) return;

        $content.html(
            '<div class="modal-content">' +
                '<div class="modal-body text-center py-5 text-muted">' +
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                    'Loading review details...' +
                '</div>' +
            '</div>'
        );

        const url = loadUrl.indexOf('partial=') !== -1 ? loadUrl : loadUrl + (loadUrl.indexOf('?') !== -1 ? '&' : '?') + 'partial=1';
        $.getJSON(url, function(response) {
            if (response && response.html !== undefined) {
                $content.html(response.html);
            } else {
                $content.html(
                    '<div class="modal-content">' +
                        '<div class="modal-body"><div class="alert alert-danger mb-0">Failed to load admin review details.</div></div>' +
                    '</div>'
                );
            }
        }).fail(function() {
            $content.html(
                '<div class="modal-content">' +
                    '<div class="modal-body"><div class="alert alert-danger mb-0">Failed to load admin review details.</div></div>' +
                '</div>'
            );
        });
    });

    $(document).on('click', '#adminReviewApproveBtn', function() {
        postReviewAction($(this), approveUrl, {
            csrf_token: getCsrfToken(),
            ticket_id: ticketId
        }, 'Approve this ticket and mark it as Completed?');
    });

    $(document).on('click', '#adminReviewReturnBtn', function() {
        postReviewAction($(this), returnUrl, {
            csrf_token: getCsrfToken(),
            ticket_id: ticketId,
            review_comment: $('#adminReviewCommentInput').val()
        });
    });
})();
</script>

<div class="modal fade" id="adminGuidanceReviewModal" tabindex="-1" aria-labelledby="adminGuidanceReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div id="adminGuidanceReviewModalContent">
            <div class="modal-content">
                <div class="modal-body text-center py-5 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Loading review details...
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    const $modal = $('#adminGuidanceReviewModal');
    if (!$modal.length) return;

    const ticketId = <?php echo (int)$ticket['id']; ?>;
    const respondUrl = <?php echo json_encode(route('tickets-respond-admin-guidance', ['id' => $ticket['id']])); ?>;

    function getCsrfToken() {
        return window.AES_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
    }

    $modal.on('show.bs.modal', function(event) {
        const $trigger = $(event.relatedTarget);
        const loadUrl = $trigger.attr('data-load-url');
        const $content = $('#adminGuidanceReviewModalContent');
        if (!loadUrl || !$content.length) return;

        $content.html(
            '<div class="modal-content">' +
                '<div class="modal-body text-center py-5 text-muted">' +
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                    'Loading review details...' +
                '</div>' +
            '</div>'
        );

        const url = loadUrl.indexOf('partial=') !== -1 ? loadUrl : loadUrl + (loadUrl.indexOf('?') !== -1 ? '&' : '?') + 'partial=1';
        $.getJSON(url, function(response) {
            if (response && response.html !== undefined) {
                $content.html(response.html);
            } else {
                $content.html(
                    '<div class="modal-content">' +
                        '<div class="modal-body"><div class="alert alert-danger mb-0">Failed to load admin review details.</div></div>' +
                    '</div>'
                );
            }
        }).fail(function() {
            $content.html(
                '<div class="modal-content">' +
                    '<div class="modal-body"><div class="alert alert-danger mb-0">Failed to load admin review details.</div></div>' +
                '</div>'
            );
        });
    });

    $(document).on('click', '#adminGuidanceRespondBtn', function() {
        const $trigger = $(this);
        const comment = ($('#adminGuidanceResponseInput').val() || '').trim();
        if (!comment) {
            showToast('Please enter a response for the development team.', 'danger');
            return;
        }

        showLoader();
        $.ajax({
            url: respondUrl,
            type: 'POST',
            data: {
                csrf_token: getCsrfToken(),
                ticket_id: ticketId,
                guidance_response_comment: comment
            },
            dataType: 'json',
            success: function(response) {
                hideLoader();
                handleAjaxSuccess($trigger, response);
                if (response && response.success) {
                    $modal.modal('hide');
                }
            },
            error: function(xhr) {
                hideLoader();
                let errorMessage = 'An error occurred while processing your request.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) errorMessage = response.message;
                } catch (e) {}
                showToast(errorMessage, 'danger');
            }
        });
    });
})();
</script>
<?php endif; ?>

<?php if ($isAdmin): ?>
<div class="modal fade" id="reclassifyTicketModal" tabindex="-1" aria-labelledby="reclassifyTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="reclassifyTicketForm"
              method="POST"
              action="<?php echo route('tickets-reclassify', ['id' => $ticket['id']]); ?>"
              class="modal-content ajax-form"
              data-ajax-reset="1"
              data-confirm="Reclassify this ticket? Workflow and visibility will be updated for the new category.">
            <div class="modal-header">
                <h5 class="modal-title" id="reclassifyTicketModalLabel"><i class="ti ti-switch-horizontal me-2"></i> Reclassify Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                <label for="reclassifyCategorySelect" class="form-label font-weight-semibold">New Category</label>
                <select name="category" id="reclassifyCategorySelect" class="form-select" required>
                    <?php foreach (TicketWorkflowService::getReclassifyCategoryOptions() as $value => $label): ?>
                        <option value="<?php echo e($value); ?>" <?php echo ($ticket['category'] ?? '') === $value ? 'selected' : ''; ?>>
                            <?php echo e($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-secondary fs-7 mt-3 mb-0">
                    Reclassifying back to <strong>Bug Fix</strong> restores project team visibility automatically.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Reclassify Ticket</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="workflowAssignDevelopersModal" tabindex="-1" aria-labelledby="workflowAssignDevelopersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div id="workflowAssignDevelopersModalContent">
            <div class="modal-content">
                <div class="modal-body text-center py-5 text-muted">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Loading team members...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const $modal = $('#workflowAssignDevelopersModal');
    if (!$modal.length) return;

    $modal.on('show.bs.modal', function() {
        const $btn = $('#workflowAssignDevelopersBtn');
        const loadUrl = $btn.attr('data-load-url');
        const $content = $('#workflowAssignDevelopersModalContent');
        if (!loadUrl || !$content.length) return;

        $content.html(
            '<div class="modal-content">' +
                '<div class="modal-body text-center py-5 text-muted">' +
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                    'Loading team members...' +
                '</div>' +
            '</div>'
        );

        const url = loadUrl.indexOf('partial=') !== -1 ? loadUrl : loadUrl + (loadUrl.indexOf('?') !== -1 ? '&' : '?') + 'partial=1';
        $.getJSON(url, function(response) {
            if (response && response.html !== undefined) {
                $content.html(response.html);
            } else {
                $content.html(
                    '<div class="modal-content">' +
                        '<div class="modal-body"><div class="alert alert-danger mb-0">Failed to load assignment form.</div></div>' +
                    '</div>'
                );
            }
        }).fail(function() {
            $content.html(
                '<div class="modal-content">' +
                    '<div class="modal-body"><div class="alert alert-danger mb-0">Failed to load assignment form.</div></div>' +
                '</div>'
            );
        });
    });
})();
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
