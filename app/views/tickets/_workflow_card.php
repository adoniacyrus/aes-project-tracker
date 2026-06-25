<?php
$displayStatus = $displayStatus ?? ticket_display_status($ticket);
$statusClass = ticket_display_status_badge_class($displayStatus);
$isAdmin = $isAdmin ?? (($userRole ?? '') === 'admin');
$isPendingAdminReview = is_ticket_pending_admin_review($ticket);
$canAdminReview = can_admin_review_ticket($userRole ?? '', $ticket);
$canMarkCompleted = $isAdmin
    && $displayStatus === 'Processing'
    && !$isPendingAdminReview
    && !$canAdminReview;
$canQuickAssign = $isAdmin && in_array($displayStatus, ['Initiated', 'Processing'], true);
$canAccessClientChat = can_access_client_chat($userRole ?? '');
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-git-fork text-primary"></i>
        <span>Workflow</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <div class="ticket-status-row mb-0">
            <span class="ticket-meta-label">Current status</span>
            <span id="ticket-status-badge" class="badge <?php echo $statusClass; ?> ticket-status-badge"><?php echo e($displayStatus); ?></span>
        </div>

        <?php if ($isAdmin): ?>
            <?php if ($displayStatus === 'Completed'): ?>
                <div class="ticket-sidebar-divider"></div>
                <div class="alert alert-success py-2 px-3 mb-3 fs-7">
                    <i class="ti ti-circle-check me-1"></i> Completed successfully.
                </div>
                <p class="ticket-sidebar-hint mb-3 fs-7">
                    <i class="ti ti-lock me-1"></i> Team assignment locked.
                </p>
                <div class="ticket-workflow-actions-stack d-flex flex-column gap-2">
                    <?php if ($canAccessClientChat): ?>
                        <button type="button"
                                class="btn btn-outline-primary btn-sm w-100"
                                id="open-client-discussion-btn"
                                data-chat-launcher="client-chat-launcher">
                            <i class="ti ti-message-circle me-1"></i> Open Client Discussion
                        </button>
                    <?php endif; ?>
                    <button type="button"
                            class="btn btn-outline-secondary btn-sm w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#reclassifyTicketModal">
                        <i class="ti ti-switch-horizontal me-1"></i> Reclassify Ticket
                    </button>
                </div>
            <?php else: ?>
                <div class="ticket-sidebar-divider"></div>
                <p class="ticket-sidebar-subtitle mb-2"><i class="ti ti-layout-grid"></i> Actions</p>
                <div class="ticket-workflow-actions-stack d-flex flex-column gap-2">
                    <?php if ($canQuickAssign): ?>
                        <button type="button"
                                class="btn btn-primary btn-sm w-100"
                                id="workflowAssignDevelopersBtn"
                                data-load-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'assignment-modal'])); ?>"
                                data-bs-toggle="modal"
                                data-bs-target="#workflowAssignDevelopersModal">
                            <i class="ti ti-users me-1"></i> Assign Developers
                        </button>
                    <?php endif; ?>

                    <?php if ($canAccessClientChat): ?>
                        <button type="button"
                                class="btn btn-outline-primary btn-sm w-100"
                                id="open-client-discussion-btn"
                                data-chat-launcher="client-chat-launcher">
                            <i class="ti ti-message-circle me-1"></i> Open Client Discussion
                        </button>
                    <?php endif; ?>

                    <?php if ($canMarkCompleted): ?>
                        <form action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>"
                              method="POST"
                              class="ajax-form ticket-workflow-complete-form"
                              data-confirm="<?php echo e(simplified_workflow_confirm_message('Completed')); ?>"
                              data-confirm-title="Complete Ticket">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                            <input type="hidden" name="status" value="Completed">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="ti ti-circle-check me-1"></i> Mark Completed
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php require __DIR__ . '/_admin_review_card.php'; ?>

                    <button type="button"
                            class="btn btn-outline-secondary btn-sm w-100"
                            data-bs-toggle="modal"
                            data-bs-target="#reclassifyTicketModal">
                        <i class="ti ti-switch-horizontal me-1"></i> Reclassify Ticket
                    </button>
                </div>
            <?php endif; ?>
        <?php elseif ($canAccessClientChat): ?>
            <div class="ticket-sidebar-divider"></div>
            <button type="button"
                    class="btn btn-outline-primary btn-sm w-100"
                    id="open-client-discussion-btn"
                    data-chat-launcher="client-chat-launcher">
                <i class="ti ti-message-circle me-1"></i> Open Client Discussion
            </button>
        <?php endif; ?>
    </div>
</div>
