<?php
$canManageTasks = $canManageTasks ?? can_manage_tasks($userRole ?? '');
$currentUserId = $currentUserId ?? (int)($_SESSION['user_id'] ?? 0);
$taskAssignableMembers = $taskAssignableMembers ?? filter_task_assignable_members($projectMembers ?? []);
$taskStatuses = ['Pending', 'In Progress', 'Blocked', 'Completed'];
?>
        <?php if (($userRole ?? '') !== 'client'): ?>
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-semibold">
                    <i class="ti ti-checkbox text-primary fs-4"></i> Tasks
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold rounded px-2">
                    <?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'Completed')) . '/' . count($tasks); ?> Completed
                </span>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($tasks)): ?>
                    <p class="text-muted italic text-center py-2 mb-0 fs-7 empty-tasks-placeholder">No tasks defined for this ticket.</p>
                <?php else: ?>
                    <div id="ticket-tasks-list" class="table-responsive mb-0">
                        <table class="table table-sm table-hover align-middle mb-0 fs-7">
                            <thead>
                                <tr class="bg-light">
                                    <th class="py-2">Task Name</th>
                                    <th class="py-2">Assigned Member</th>
                                    <th class="py-2">Status</th>
                                    <th class="py-2">Created</th>
                                    <?php if ($canManageTasks): ?>
                                        <th class="py-2 text-end">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <?php $canUpdateStatus = can_update_task_status($task, $currentUserId, $userRole ?? ''); ?>
                                    <tr data-task-id="<?php echo (int)$task['id']; ?>">
                                        <td class="font-weight-medium <?php echo $task['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : ''; ?>">
                                            <?php echo e($task['task_name']); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($task['assignee_name'])): ?>
                                                <?php echo e($task['assignee_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted italic">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($canUpdateStatus): ?>
                                                <select class="form-select form-select-sm task-status-select" data-task-id="<?php echo (int)$task['id']; ?>" style="min-width: 130px;">
                                                    <?php foreach ($taskStatuses as $st): ?>
                                                        <option value="<?php echo e($st); ?>" <?php echo $task['status'] === $st ? 'selected' : ''; ?>><?php echo e($st); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary task-status-badge"><?php echo e($task['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo !empty($task['created_at']) ? date('M d, Y', strtotime($task['created_at'])) : '—'; ?>
                                        </td>
                                        <?php if ($canManageTasks): ?>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1 task-edit-btn"
                                                            data-task-id="<?php echo (int)$task['id']; ?>"
                                                            title="Edit task">
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    <form class="ajax-form d-inline task-delete-form" method="POST"
                                                          action="<?php echo route('tasks-delete', ['id' => $task['id']]); ?>"
                                                          data-ajax-refresh="#ticket-dynamic-content"
                                                          data-confirm="Delete this task?">
                                                        <?php echo csrf_field(); ?>
                                                        <input type="hidden" name="task_id" value="<?php echo (int)$task['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1" title="Delete task">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if ($canManageTasks): ?>
                <form action="<?php echo route('tasks-create'); ?>" method="POST" class="mt-3 border-top pt-3 ajax-form" data-ajax-reset="true" data-ajax-refresh="#ticket-dynamic-content">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <label class="form-label font-weight-semibold fs-7 mb-2">Add Task</label>
                    <div class="row g-2">
                        <div class="col-md-5">
                            <input type="text" name="task_name" class="form-control form-control-sm" placeholder="Task name..." required>
                        </div>
                        <div class="col-md-4">
                            <select name="assigned_member" class="form-select form-select-sm" required>
                                <option value="">Assign to...</option>
                                <?php foreach ($taskAssignableMembers as $mem): ?>
                                    <option value="<?php echo $mem['user_id']; ?>"><?php echo e($mem['full_name']); ?> (<?php echo e($mem['role']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="due_date" class="form-control form-control-sm" title="Due date (optional)">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canManageTasks): ?>
        <div class="modal fade" id="ticketTaskEditModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form id="ticketTaskEditForm" method="POST" class="modal-content ajax-form" data-ajax-refresh="#ticket-dynamic-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ti ti-edit me-2"></i> Edit Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label required">Task Name</label>
                            <input type="text" name="task_name" id="ticketTaskEditName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Assign To</label>
                            <select name="assigned_member" id="ticketTaskEditAssignee" class="form-select" required>
                                <option value="">Select member...</option>
                                <?php foreach ($taskAssignableMembers as $mem): ?>
                                    <option value="<?php echo $mem['user_id']; ?>"><?php echo e($mem['full_name']); ?> (<?php echo e($mem['role']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" id="ticketTaskEditDueDate" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Status</label>
                                <select name="status" id="ticketTaskEditStatus" class="form-select">
                                    <?php foreach ($taskStatuses as $st): ?>
                                        <option value="<?php echo e($st); ?>"><?php echo e($st); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
