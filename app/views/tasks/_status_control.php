<?php
/** @var array $task */
$task = $task ?? [];
$currentUserId = $currentUserId ?? (int)($_SESSION['user_id'] ?? 0);
$userRole = $userRole ?? ($_SESSION['user_role'] ?? '');
$taskStatuses = $taskStatuses ?? ['Pending', 'In Progress', 'Blocked', 'Completed'];

$canManageTasks = $canManageTasks ?? can_manage_tasks($userRole);
$canUpdateStatus = can_update_task_status($task, $currentUserId, $userRole);
$useChecklistUi = $canUpdateStatus && !$canManageTasks && uses_task_checklist_status_ui($userRole);
$status = $task['status'] ?? 'Pending';
$taskId = (int)($task['id'] ?? 0);
?>
<?php if (!$canUpdateStatus): ?>
    <span class="badge <?php echo task_status_badge_class($status); ?> task-status-badge"><?php echo e($status); ?></span>
<?php elseif ($canManageTasks): ?>
    <select class="form-select form-select-sm task-status-select" data-task-id="<?php echo $taskId; ?>" style="min-width: 130px;">
        <?php foreach ($taskStatuses as $st): ?>
            <option value="<?php echo e($st); ?>" <?php echo $status === $st ? 'selected' : ''; ?>><?php echo e($st); ?></option>
        <?php endforeach; ?>
    </select>
<?php elseif ($useChecklistUi): ?>
    <div class="task-checklist-control" data-task-id="<?php echo $taskId; ?>" data-status="<?php echo e($status); ?>">
        <?php if ($status === 'Pending'): ?>
            <button type="button" class="btn btn-sm btn-outline-primary task-checklist-start px-2 py-1" title="Acknowledge and start working">
                <i class="ti ti-player-play me-1"></i> Start
            </button>
        <?php elseif ($status === 'In Progress'): ?>
            <button type="button" class="btn btn-sm btn-outline-success task-checklist-done px-2 py-1" title="Mark as completed">
                <i class="ti ti-circle-check me-1"></i> Done
            </button>
        <?php elseif ($status === 'Completed'): ?>
            <span class="badge bg-success-subtle text-success border d-inline-flex align-items-center gap-1">
                <i class="ti ti-circle-check fs-8"></i> Done
            </span>
        <?php elseif ($status === 'Blocked'): ?>
            <span class="badge bg-danger-subtle text-danger border d-inline-flex align-items-center gap-1">
                <i class="ti ti-lock fs-8"></i> Blocked
            </span>
        <?php else: ?>
            <span class="badge <?php echo task_status_badge_class($status); ?>"><?php echo e($status); ?></span>
        <?php endif; ?>
    </div>
<?php else: ?>
    <span class="badge <?php echo task_status_badge_class($status); ?> task-status-badge"><?php echo e($status); ?></span>
<?php endif; ?>
