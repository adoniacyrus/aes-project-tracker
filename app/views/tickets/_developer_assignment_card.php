<?php
$isAdmin = $isAdmin ?? (($userRole ?? '') === 'admin');
if (!$isAdmin) {
    return;
}

$developerAssignmentMembers = $developerAssignmentMembers ?? [];
$ticketAssignments = $ticketAssignments ?? get_ticket_assigned_members($ticket);
$assignedUserIds = array_map('intval', array_column($ticketAssignments, 'user_id'));
$hasExistingAssignment = !empty($assignedUserIds);

$developerMembers = array_values(array_filter($developerAssignmentMembers, function ($member) {
    return ($member['role'] ?? '') === 'developer';
}));
$internMembers = array_values(array_filter($developerAssignmentMembers, function ($member) {
    return ($member['role'] ?? '') === 'intern';
}));

$isMemberChecked = function ($member) use ($hasExistingAssignment, $assignedUserIds) {
    $memberId = (int)($member['user_id'] ?? 0);
    if ($hasExistingAssignment) {
        return in_array($memberId, $assignedUserIds, true);
    }

    return ($member['role'] ?? '') === 'developer';
};

?>
<div id="ticket-developer-assignment" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'assignment'])); ?>">
    <div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
        <div class="ticket-sidebar-card__head">
            <i class="ti ti-users-group text-primary"></i>
            <span>Developer Assignment</span>
        </div>
        <div class="ticket-sidebar-card__body">
            <?php if (empty($developerMembers) && empty($internMembers)): ?>
                <p class="ticket-sidebar-hint mb-0">
                    <i class="ti ti-info-circle me-1"></i>
                    No developers or interns are assigned to this project.
                </p>
            <?php else: ?>
                <form id="ticketDeveloperAssignmentForm"
                      action="<?php echo route('tickets-assign-team', ['id' => $ticket['id']]); ?>"
                      method="POST"
                      class="ajax-form ticket-developer-assignment-form"
                      data-confirm="Assign the selected team members to this ticket?">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">

                    <?php if (!empty($developerMembers)): ?>
                        <p class="ticket-sidebar-subtitle mb-2"><i class="ti ti-code"></i> Developers</p>
                        <div class="ticket-assignment-group mb-3">
                            <?php foreach ($developerMembers as $member): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="member_ids[]"
                                           value="<?php echo (int)$member['user_id']; ?>"
                                           id="assign-member-<?php echo (int)$member['user_id']; ?>"
                                           <?php echo $isMemberChecked($member) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="assign-member-<?php echo (int)$member['user_id']; ?>">
                                        <?php echo e($member['full_name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($internMembers)): ?>
                        <p class="ticket-sidebar-subtitle mb-2"><i class="ti ti-school"></i> Interns</p>
                        <div class="ticket-assignment-group mb-3">
                            <?php foreach ($internMembers as $member): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="member_ids[]"
                                           value="<?php echo (int)$member['user_id']; ?>"
                                           id="assign-member-<?php echo (int)$member['user_id']; ?>"
                                           <?php echo $isMemberChecked($member) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="assign-member-<?php echo (int)$member['user_id']; ?>">
                                        <?php echo e($member['full_name']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="ti ti-user-check me-1"></i> Assign Team
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
