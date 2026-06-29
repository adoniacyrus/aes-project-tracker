<?php
$isAdmin = $isAdmin ?? can_manage_tasks();
$currentUserId = $currentUserId ?? (int)($_SESSION['user_id'] ?? 0);
$tasks = $tasks ?? [];
?>
<?php require __DIR__ . '/_status_tabs.php'; ?>

<div class="task-list-panel">
    <div class="card-body p-0">
        <?php if (empty($tasks)): ?>
            <div class="p-5 text-center text-muted">
                <i class="ti ti-checkbox-off fs-1 mb-2 text-secondary"></i>
                <p class="mb-0 fs-6">No tasks found for this status.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($tasks as $t): ?>
                    <?php
                        $canUpdateStatus = can_update_task_status($t, $currentUserId, $_SESSION['user_role'] ?? '');
                        $canManageTasksRow = $isAdmin;
                        $useChecklistUi = $canUpdateStatus && !$canManageTasksRow && uses_task_checklist_status_ui();
                        $isCompleted = ($t['status'] ?? '') === 'Completed';
                        $rowClass = 'list-group-item p-3 border-bottom task-row task-row--' . strtolower(str_replace(' ', '-', $t['status'] ?? 'pending'));
                        if ($isCompleted) {
                            $rowClass .= ' task-row--completed';
                        }
                        if ($useChecklistUi) {
                            $rowClass .= ' task-row--checklist';
                        }
                    ?>
                    <div class="<?php echo e($rowClass); ?>" data-task-id="<?php echo (int)$t['id']; ?>" data-task-status="<?php echo e($t['status']); ?>">
                        <div class="d-flex align-items-start gap-3">
                            <?php if ($useChecklistUi): ?>
                                <div class="task-checklist-cell pt-1">
                                    <?php if ($t['status'] === 'Pending'): ?>
                                        <span class="task-checklist-marker task-checklist-marker--pending"><i class="ti ti-circle"></i></span>
                                    <?php elseif ($t['status'] === 'In Progress'): ?>
                                        <span class="task-checklist-marker task-checklist-marker--active"><i class="ti ti-loader"></i></span>
                                    <?php elseif ($t['status'] === 'Completed'): ?>
                                        <span class="task-checklist-marker task-checklist-marker--done"><i class="ti ti-circle-check-filled"></i></span>
                                    <?php elseif ($t['status'] === 'Blocked'): ?>
                                        <span class="task-checklist-marker task-checklist-marker--blocked"><i class="ti ti-lock"></i></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-fill min-width-0">
                                <span class="task-name-cell fs-7 text-dark <?php echo $isCompleted ? 'text-decoration-line-through text-muted' : 'font-weight-semibold'; ?> d-block">
                                    <?php echo e($t['task_name']); ?>
                                </span>
                                <div class="mt-1 fs-8 text-secondary d-flex flex-wrap align-items-center gap-1.5">
                                    <span>Ticket:</span>
                                    <a href="<?php echo route('tickets-view', ['ticket_code' => $t['project_code'] . '-' . $t['ticket_id']]); ?>" class="text-decoration-none font-weight-medium text-primary">
                                        <?php echo e(mb_strimwidth($t['ticket_title'], 0, 30, '...')); ?>
                                    </a>
                                    <span>•</span>
                                    <span class="badge bg-light border text-dark font-monospace"><?php echo e($t['project_code']); ?></span>
                                    <?php if ($isAdmin && !empty($t['assignee_name'])): ?>
                                        <span>•</span>
                                        <span class="text-muted"><?php echo e($t['assignee_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="task-status-cell d-flex align-items-center gap-2 flex-shrink-0">
                                <?php
                                    $task = $t;
                                    $userRole = $_SESSION['user_role'] ?? '';
                                    $canManageTasks = $isAdmin;
                                    require __DIR__ . '/_status_control.php';
                                ?>
                                <?php if ($isAdmin): ?>
                                    <a href="<?php echo route('tasks-edit', ['id' => $t['id']]); ?>" class="btn btn-outline-secondary btn-icon btn-sm" title="Edit task">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
