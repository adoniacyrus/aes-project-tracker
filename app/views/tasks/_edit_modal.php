<?php
$taskStatuses = $taskStatuses ?? get_task_statuses();
?>
<div class="modal fade" id="taskEditModal" tabindex="-1" aria-labelledby="taskEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="taskEditForm" method="POST" class="modal-content ajax-form" novalidate>
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-weight-semibold" id="taskEditModalLabel">
                    <i class="ti ti-edit me-1 text-primary"></i> Edit Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>

                <p id="taskEditTicketRef" class="text-secondary fs-8 mb-3 d-none"></p>

                <div class="mb-3">
                    <label class="form-label font-weight-medium required">Task Name</label>
                    <input type="text" name="task_name" id="taskEditName" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-medium required">Assign To</label>
                    <select name="assigned_member" id="taskEditAssignee" class="form-select" required>
                        <option value="">-- Select developer or intern --</option>
                    </select>
                    <p class="text-muted fs-8 mb-0 mt-1">Changing the assignee resets the task to <strong>Pending</strong>.</p>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label font-weight-medium">Due Date</label>
                        <input type="date" name="due_date" id="taskEditDueDate" class="form-control">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label font-weight-medium">Status</label>
                        <select name="status" id="taskEditStatus" class="form-select">
                            <?php foreach ($taskStatuses as $st): ?>
                                <option value="<?php echo e($st); ?>"><?php echo e($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
