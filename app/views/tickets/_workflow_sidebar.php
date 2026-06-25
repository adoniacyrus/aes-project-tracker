<?php
$displayStatus = $displayStatus ?? ticket_display_status($ticket);
$statusClass = ticket_display_status_badge_class($displayStatus);
$canChangeSimplifiedStatus = $canChangeSimplifiedStatus ?? TicketWorkflowService::canAdminChangeSimplifiedStatus($userRole ?? '');
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
            <div class="card border border-light shadow-sm ticket-action-placeholder mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-messages text-primary"></i>
                        <span class="fs-7 font-weight-medium">Client Discussion</span>
                        <span class="badge bg-light text-secondary border ms-auto">Coming soon</span>
                    </div>
                </div>
            </div>
            <div class="card border border-light shadow-sm ticket-action-placeholder mb-2">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-receipt text-primary"></i>
                        <span class="fs-7 font-weight-medium">Ticket Cost Estimation</span>
                        <span class="badge bg-light text-secondary border ms-auto">Coming soon</span>
                    </div>
                </div>
            </div>
            <div class="card border border-light shadow-sm ticket-action-placeholder mb-0">
                <div class="card-body py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-user-plus text-primary"></i>
                        <span class="fs-7 font-weight-medium">Assign Developers</span>
                        <span class="badge bg-light text-secondary border ms-auto">Coming soon</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($userRole !== 'client'): ?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-users text-primary"></i>
        <span>Team visibility</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <?php if (is_ticket_visible_to_project_team($ticket)): ?>
            <div class="ticket-team-list">
                <?php foreach ($projectMembers as $mem): ?>
                    <?php if (in_array($mem['role'], ['admin', 'developer', 'intern'], true)): ?>
                        <div class="ticket-team-member">
                            <span class="ticket-team-member__avatar"><?php echo e(user_initials($mem['full_name'])); ?></span>
                            <span class="ticket-team-member__info">
                                <span class="ticket-team-member__name"><?php echo e($mem['full_name']); ?></span>
                                <span class="ticket-team-member__role"><?php echo e(ucfirst($mem['role'])); ?></span>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="ticket-sidebar-hint mb-0">
                <i class="ti ti-eye-off me-1"></i>
                Hidden from developers and interns until approval or payment is confirmed.
            </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
