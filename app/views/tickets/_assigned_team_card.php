<?php
if (($userRole ?? '') === 'client') {
    return;
}
$userRole = $userRole ?? ($_SESSION['user_role'] ?? '');
$isAdmin = ($userRole === 'admin');
$canSubmitForReview = can_submit_ticket_for_review($userRole, $ticket, (int)($currentUserId ?? ($_SESSION['user_id'] ?? 0)));
$isPendingAdminReview = is_ticket_pending_admin_review($ticket);
$canRequestCommercialReview = !empty($allowedTransitions['__commercial_review__'] ?? []);
$assignedMembers = get_ticket_visible_team_members($ticket, $projectMembers ?? []);
$isBugFixOpenTeam = TicketWorkflowService::isBugFixOpenToProjectTeam($ticket);
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-users text-primary"></i>
        <span>Assigned Team</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <?php if (!$isAdmin): ?>
            <?php if ($canSubmitForReview): ?>
                <button type="button"
                        class="btn btn-success btn-sm w-100 mb-3"
                        data-bs-toggle="modal"
                        data-bs-target="#submitForReviewModal">
                    <i class="ti ti-send me-1"></i> Mark as Resolved
                </button>
            <?php elseif ($isPendingAdminReview && in_array($userRole, ['developer', 'intern'], true)): ?>
                <div class="alert alert-info py-2 px-3 mb-3 fs-7">
                    <i class="ti ti-clock me-1"></i> Submitted for admin review. Awaiting approval.
                </div>
            <?php endif; ?>

            <?php if ($canRequestCommercialReview): ?>
                <form action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>"
                      method="POST"
                      class="ajax-form mb-3"
                      data-confirm="<?php echo e(destructive_workflow_confirm_message('__commercial_review__')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                    <input type="hidden" name="status" value="__commercial_review__">
                    <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                        <i class="ti ti-flag me-1"></i> Request Commercial Review
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

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
                <?php if ($isBugFixOpenTeam): ?>
                    No developers or interns are on this project yet.
                <?php else: ?>
                    No team assigned yet. Only admin and client can see this ticket until members are assigned.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
