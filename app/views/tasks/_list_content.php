<?php
$isAdmin = $isAdmin ?? can_manage_tasks();
$currentUserId = $currentUserId ?? (int)($_SESSION['user_id'] ?? 0);
$taskStatuses = ['Pending', 'In Progress', 'Blocked', 'Completed'];
$statusGroups = [
    ['title' => 'Pending', 'icon' => 'circle', 'color' => 'info', 'tasks' => $pendingTasks],
    ['title' => 'In Progress', 'icon' => 'loader', 'color' => 'primary', 'tasks' => $inProgressTasks],
    ['title' => 'Blocked', 'icon' => 'lock', 'color' => 'danger', 'tasks' => $blockedTasks],
    ['title' => 'Completed', 'icon' => 'circle-check', 'color' => 'success', 'tasks' => $completedTasks],
];
?>
<?php foreach ($statusGroups as $group): ?>
    <div class="col-12 col-md-6">
        <div class="card shadow-sm border border-light h-100">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-bold">
                    <span class="badge bg-<?php echo $group['color']; ?>-subtle text-<?php echo $group['color']; ?> p-1.5 rounded-circle d-flex align-items-center justify-content-center">
                        <i class="ti ti-<?php echo $group['icon']; ?> fs-5"></i>
                    </span>
                    <?php echo $group['title']; ?>
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold px-2 py-1 fs-8">
                    <?php echo count($group['tasks']); ?>
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($group['tasks'])): ?>
                    <div class="p-4 text-center text-muted">
                        <p class="mb-0 fs-8 italic">No tasks in this state.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($group['tasks'] as $t): ?>
                            <?php $canUpdateStatus = can_update_task_status($t, $currentUserId); ?>
                            <div class="list-group-item p-3 border-bottom" data-task-id="<?php echo (int)$t['id']; ?>">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="flex-fill">
                                        <span class="fs-7 text-dark <?php echo $t['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : 'font-weight-semibold'; ?> d-block">
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
                                    <div class="d-flex flex-column align-items-end gap-1.5" style="flex-shrink: 0;">
                                        <?php if ($canUpdateStatus): ?>
                                            <select class="form-select form-select-sm task-status-select" data-task-id="<?php echo (int)$t['id']; ?>" style="min-width: 120px;">
                                                <?php foreach ($taskStatuses as $st): ?>
                                                    <option value="<?php echo e($st); ?>" <?php echo $t['status'] === $st ? 'selected' : ''; ?>><?php echo e($st); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary task-status-badge"><?php echo e($t['status']); ?></span>
                                        <?php endif; ?>
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
    </div>
<?php endforeach; ?>
