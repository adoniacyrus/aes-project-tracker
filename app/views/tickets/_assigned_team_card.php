<?php
if (($userRole ?? '') === 'client') {
    return;
}
$assignedMembers = get_ticket_visible_team_members($ticket, $projectMembers ?? []);
$isBugFixOpenTeam = TicketWorkflowService::isBugFixOpenToProjectTeam($ticket);
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-users text-primary"></i>
        <span>Assigned Team</span>
    </div>
    <div class="ticket-sidebar-card__body">
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
