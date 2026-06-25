<?php
$displayStatus = $displayStatus ?? ticket_display_status($ticket);
$statusClass = ticket_display_status_badge_class($displayStatus);
$canChangeSimplifiedStatus = $canChangeSimplifiedStatus ?? TicketWorkflowService::canAdminChangeSimplifiedStatus($userRole ?? '');
$canAccessClientChat = can_access_client_chat($userRole ?? '');
$canSubmitForReview = can_submit_ticket_for_review($userRole ?? '', $ticket, (int)($currentUserId ?? ($_SESSION['user_id'] ?? 0)));
$isPendingAdminReview = is_ticket_pending_admin_review($ticket);
$canRequestCommercialReview = !empty($allowedTransitions['__commercial_review__']);
$isBugFixOpenTeam = TicketWorkflowService::isBugFixOpenToProjectTeam($ticket);
?>
<!-- Workflow -->
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-git-fork text-primary"></i>
        <span>Workflow</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <div class="ticket-status-row">
            <span class="ticket-meta-label">Current status</span>
            <span id="ticket-status-badge" class="badge <?php echo $statusClass; ?> ticket-status-badge"><?php echo e($displayStatus); ?></span>
        </div>

        <?php if ($canChangeSimplifiedStatus): ?>
            <form id="ticketWorkflowForm"
                  action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>"
                  method="POST"
                  class="ajax-form ticket-workflow-form ticket-workflow-form--compact"
                  data-workflow-current="<?php echo e($displayStatus); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <label for="ticketWorkflowStatus" class="ticket-meta-label">Current status</label>
                <div class="ticket-workflow-actions">
                    <select name="status"
                            id="ticketWorkflowStatus"
                            class="form-select form-select-sm"
                            data-current-status="<?php echo e($displayStatus); ?>"
                            required>
                        <?php foreach (TicketWorkflowService::getSimplifiedStatuses() as $simplifiedStatus): ?>
                            <?php $workflowConfirm = simplified_workflow_confirm_message($simplifiedStatus); ?>
                            <option value="<?php echo e($simplifiedStatus); ?>"
                                <?php echo $simplifiedStatus === $displayStatus ? 'selected' : ''; ?>
                                <?php if ($workflowConfirm): ?>
                                    data-confirm="<?php echo e($workflowConfirm); ?>"
                                <?php endif; ?>>
                                <?php echo e($simplifiedStatus); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        <?php endif; ?>

        <div class="ticket-sidebar-divider"></div>
        <p class="ticket-sidebar-subtitle mb-2"><i class="ti ti-layout-grid"></i> Actions</p>
        <div class="ticket-workflow-actions-stack">
            <?php if ($canAccessClientChat): ?>
            <div class="card border border-light shadow-sm mb-2">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="ti ti-messages text-primary mt-1"></i>
                        <div>
                            <span class="fs-7 font-weight-semibold d-block">Client Discussion</span>
                            <small class="text-secondary">Requirements discussion, negotiations and commercial communication.</small>
                        </div>
                    </div>
                    <button type="button"
                            class="btn btn-outline-primary btn-sm w-100"
                            id="open-client-discussion-btn"
                            data-chat-launcher="client-chat-launcher">
                        <i class="ti ti-message-circle me-1"></i> Open Client Discussion
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($canSubmitForReview): ?>
            <div class="card border border-light shadow-sm mb-2">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2 mb-2">
                        <i class="ti ti-circle-check text-success mt-1"></i>
                        <div>
                            <span class="fs-7 font-weight-semibold d-block">Developer Resolution</span>
                            <small class="text-secondary">Submit your work for admin review before the ticket can be completed.</small>
                        </div>
                    </div>
                    <button type="button"
                            class="btn btn-success btn-sm w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#submitForReviewModal">
                        <i class="ti ti-send me-1"></i> Mark as Resolved
                    </button>
                </div>
            </div>
            <?php elseif ($isPendingAdminReview && in_array($userRole ?? '', ['developer', 'intern'], true)): ?>
            <div class="alert alert-info py-2 px-3 mb-2 fs-7 mb-0">
                <i class="ti ti-clock me-1"></i> Submitted for admin review. Awaiting approval.
            </div>
            <?php endif; ?>

            <?php if ($canRequestCommercialReview): ?>
            <form action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>"
                  method="POST"
                  class="ajax-form mb-2"
                  data-confirm="<?php echo e(destructive_workflow_confirm_message('__commercial_review__')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                <input type="hidden" name="status" value="__commercial_review__">
                <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                    <i class="ti ti-flag me-1"></i> Request Commercial Review
                </button>
            </form>
            <?php endif; ?>

            <?php if (($isAdmin ?? false)): ?>
            <button type="button"
                    class="btn btn-outline-secondary btn-sm w-100 mb-2"
                    data-bs-toggle="modal"
                    data-bs-target="#reclassifyTicketModal">
                <i class="ti ti-switch-horizontal me-1"></i> Reclassify Ticket
            </button>
            <?php endif; ?>

            <?php require __DIR__ . '/_admin_review_card.php'; ?>
        </div>
    </div>
</div>

<?php if ($userRole !== 'client'): ?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-users text-primary"></i>
        <span>Assigned Team</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <?php
        $assignedMembers = get_ticket_visible_team_members($ticket, $projectMembers ?? []);
        $isBugFixOpenTeam = TicketWorkflowService::isBugFixOpenToProjectTeam($ticket);
        ?>
        <?php if (!empty($assignedMembers)): ?>
            <?php if ($isBugFixOpenTeam && empty($ticketAssignments ?? get_ticket_assigned_members($ticket))): ?>
                <p class="ticket-sidebar-hint mb-2">
                    <i class="ti ti-users me-1"></i>
                    All project developers and interns can access this Bug Fix ticket.
                </p>
            <?php endif; ?>
            <div class="ticket-team-list">
                <?php foreach ($assignedMembers as $mem): ?>
                    <div class="ticket-team-member">
                        <span class="ticket-team-member__avatar"><?php echo e(user_initials($mem['full_name'])); ?></span>
                        <span class="ticket-team-member__info">
                            <span class="ticket-team-member__name"><?php echo e($mem['full_name']); ?></span>
                            <span class="ticket-team-member__role"><?php echo e(ucfirst($mem['role'] ?? 'member')); ?></span>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="ticket-sidebar-hint mb-0">
                <i class="ti ti-eye-off me-1"></i>
                <?php if ($isBugFixOpenTeam ?? false): ?>
                    No developers or interns are on this project yet.
                <?php else: ?>
                    No team assigned yet. Only admin and client can see this ticket until members are assigned.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
