<?php
$assignmentInputPrefix = $assignmentInputPrefix ?? 'assign-member';

if (!isset($developerMembers) || !isset($internMembers)) {
    $formData = prepare_ticket_developer_assignment_form($ticket, $developerAssignmentMembers ?? []);
    extract($formData);
}
?>
<?php if (empty($developerMembers) && empty($internMembers)): ?>
    <p class="ticket-sidebar-hint mb-0">
        <i class="ti ti-info-circle me-1"></i>
        No developers or interns are assigned to this project.
    </p>
<?php else: ?>
    <?php if (!empty($developerMembers)): ?>
        <p class="ticket-sidebar-subtitle mb-2"><i class="ti ti-code"></i> Developers</p>
        <div class="ticket-assignment-group mb-3">
            <?php foreach ($developerMembers as $member): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input"
                           type="checkbox"
                           name="member_ids[]"
                           value="<?php echo (int)$member['user_id']; ?>"
                           id="<?php echo e($assignmentInputPrefix); ?>-<?php echo (int)$member['user_id']; ?>"
                           <?php echo is_ticket_assignment_member_checked($member, $hasExistingAssignment, $assignedUserIds) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo e($assignmentInputPrefix); ?>-<?php echo (int)$member['user_id']; ?>">
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
                           id="<?php echo e($assignmentInputPrefix); ?>-<?php echo (int)$member['user_id']; ?>"
                           <?php echo is_ticket_assignment_member_checked($member, $hasExistingAssignment, $assignedUserIds) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo e($assignmentInputPrefix); ?>-<?php echo (int)$member['user_id']; ?>">
                        <?php echo e($member['full_name']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
