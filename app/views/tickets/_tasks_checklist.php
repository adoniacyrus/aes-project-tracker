<?php
$canManageTasks = $canManageTasks ?? can_manage_tasks($userRole ?? '');
$currentUserId = $currentUserId ?? (int)($_SESSION['user_id'] ?? 0);
$taskAssignableMembers = $taskAssignableMembers ?? filter_ticket_task_assignable_members(
    $ticket ?? [],
    $projectMembers ?? [],
    array_column($tasks ?? [], 'assigned_member')
);
$showChecklistColumn = uses_task_checklist_status_ui($userRole ?? '');
$taskStatuses = ['Pending', 'In Progress', 'Blocked', 'Completed'];
?>
        <?php if (($userRole ?? '') !== 'client'): ?>
        <div class="card mb-4 shadow-sm border border-light ticket-tasks-card">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-semibold">
                    <i class="ti ti-checkbox text-primary fs-4"></i> Tasks
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold rounded px-2 ticket-tasks-progress">
                    <?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'Completed')) . '/' . count($tasks); ?> Completed
                </span>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($tasks)): ?>
                    <p class="text-muted italic text-center py-2 mb-0 fs-7 empty-tasks-placeholder">No tasks defined for this ticket.</p>
                <?php else: ?>
                    <div id="ticket-tasks-list" class="table-responsive mb-0">
                        <table class="table table-sm table-hover align-middle mb-0 fs-7 ticket-tasks-table">
                            <thead>
                                <tr class="bg-light">
                                    <?php if ($showChecklistColumn): ?>
                                        <th class="py-2 ps-3" style="width: 2.75rem;"></th>
                                    <?php endif; ?>
                                    <th class="py-2">Task Name</th>
                                    <th class="py-2">Assigned Member</th>
                                    <?php if ($canManageTasks): ?>
                                        <th class="py-2">Status</th>
                                    <?php else: ?>
                                        <th class="py-2">Progress</th>
                                    <?php endif; ?>
                                    <th class="py-2">Created</th>
                                    <?php if ($canManageTasks): ?>
                                        <th class="py-2 text-end">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <?php
                                        $canUpdateStatus = can_update_task_status($task, $currentUserId, $userRole ?? '');
                                        $useChecklistUi = $canUpdateStatus && !$canManageTasks && $showChecklistColumn;
                                        $isCompleted = ($task['status'] ?? '') === 'Completed';
                                        $rowClass = 'task-row task-row--' . strtolower(str_replace(' ', '-', $task['status'] ?? 'pending'));
                                        if ($isCompleted) {
                                            $rowClass .= ' task-row--completed';
                                        }
                                        if ($useChecklistUi) {
                                            $rowClass .= ' task-row--checklist';
                                        }
                                    ?>
                                    <tr class="<?php echo e($rowClass); ?>" data-task-id="<?php echo (int)$task['id']; ?>" data-task-status="<?php echo e($task['status']); ?>">
                                        <?php if ($showChecklistColumn): ?>
                                            <td class="ps-3 task-checklist-cell align-middle">
                                                <?php if ($useChecklistUi): ?>
                                                    <?php if ($task['status'] === 'Pending'): ?>
                                                        <span class="task-checklist-marker task-checklist-marker--pending" title="Not started"><i class="ti ti-circle"></i></span>
                                                    <?php elseif ($task['status'] === 'In Progress'): ?>
                                                        <span class="task-checklist-marker task-checklist-marker--active" title="In progress"><i class="ti ti-loader"></i></span>
                                                    <?php elseif ($task['status'] === 'Completed'): ?>
                                                        <span class="task-checklist-marker task-checklist-marker--done" title="Completed"><i class="ti ti-circle-check-filled"></i></span>
                                                    <?php elseif ($task['status'] === 'Blocked'): ?>
                                                        <span class="task-checklist-marker task-checklist-marker--blocked" title="Blocked"><i class="ti ti-lock"></i></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="task-name-cell font-weight-medium <?php echo $isCompleted ? 'text-decoration-line-through text-muted' : ''; ?>">
                                            <?php echo e($task['task_name']); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($task['assignee_name'])): ?>
                                                <?php echo e($task['assignee_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted italic">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="task-status-cell">
                                            <?php require __DIR__ . '/../tasks/_status_control.php'; ?>
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
                                                          data-confirm="Are you sure you want to delete this task?">
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
                    <p class="text-muted fs-8 mb-0 mt-2">New tasks are created as <strong>Pending</strong>. The assignee starts work, then marks done when finished.</p>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canManageTasks): ?>
        <?php require __DIR__ . '/../tasks/_edit_modal.php'; ?>
        <?php endif; ?>
        <?php endif; ?>
