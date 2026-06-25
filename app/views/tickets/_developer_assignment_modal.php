<?php
if (!($isAdmin ?? false) && ($_SESSION['user_role'] ?? '') !== 'admin') {
    return;
}

$formData = prepare_ticket_developer_assignment_form($ticket, $developerAssignmentMembers ?? []);
extract($formData);
$assignmentInputPrefix = 'workflow-assign-member';
?>
<form id="workflowAssignDevelopersForm"
      action="<?php echo route('tickets-assign-team', ['id' => $ticket['id']]); ?>"
      method="POST"
      class="modal-content ajax-form ticket-developer-assignment-form"
      data-confirm="Assign the selected team members to this ticket?">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
    <div class="modal-header">
        <h5 class="modal-title" id="workflowAssignDevelopersModalLabel">
            <i class="ti ti-users-group me-2"></i> Assign Developers
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <?php require __DIR__ . '/_developer_assignment_fields.php'; ?>
    </div>
    <?php if (!empty($developerMembers) || !empty($internMembers)): ?>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-user-check me-1"></i> Assign Team
        </button>
    </div>
    <?php else: ?>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </div>
    <?php endif; ?>
</form>
