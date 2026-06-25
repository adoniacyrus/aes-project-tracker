<?php
$assignmentFormId = $assignmentFormId ?? 'ticketDeveloperAssignmentForm';
$assignmentInputPrefix = $assignmentInputPrefix ?? 'assign-member';
$assignmentSubmitLabel = $assignmentSubmitLabel ?? 'Assign Team';
$assignmentShowConfirm = $assignmentShowConfirm ?? true;
$assignmentFormClass = trim('ajax-form ticket-developer-assignment-form ' . ($assignmentFormClass ?? ''));

$formData = prepare_ticket_developer_assignment_form($ticket, $developerAssignmentMembers ?? []);
extract($formData);
?>
<?php if (empty($developerMembers) && empty($internMembers)): ?>
    <p class="ticket-sidebar-hint mb-0">
        <i class="ti ti-info-circle me-1"></i>
        No developers or interns are assigned to this project.
    </p>
<?php else: ?>
    <form id="<?php echo e($assignmentFormId); ?>"
          action="<?php echo route('tickets-assign-team', ['id' => $ticket['id']]); ?>"
          method="POST"
          class="<?php echo e($assignmentFormClass); ?>"
          <?php if ($assignmentShowConfirm): ?>
          data-confirm="Assign the selected team members to this ticket?"
          <?php endif; ?>>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
        <?php require __DIR__ . '/_developer_assignment_fields.php'; ?>
        <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="ti ti-user-check me-1"></i> <?php echo e($assignmentSubmitLabel); ?>
        </button>
    </form>
<?php endif; ?>
