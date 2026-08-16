<?php
$userRole = $_SESSION['user_role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$canCreate = can_create_external_work_log($userRole);
$canManage = can_manage_external_work_logs($userRole);
$ewlRefreshTarget = '#my-external-work-logs-content';
$showProject = true;
$compact = true;
$logs = $myExternalWorkLogs ?? [];
?>
<div class="card mb-4 shadow-sm border border-light">
    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="d-flex align-items-center gap-2">
            <i class="ti ti-notebook text-primary fs-4"></i> External Work Logs Assigned To Me
        </span>
        <a href="<?php echo e(route('external-work-logs')); ?>" class="btn btn-sm btn-outline-secondary">View all</a>
    </div>
    <div id="my-external-work-logs-content" data-ajax-container>
        <div class="card-body p-0">
            <?php require __DIR__ . '/_table.php'; ?>
        </div>
    </div>
</div>
<?php if ($canCreate): ?>
    <?php
    $projects = $ewlProjects ?? [];
    $assignees = $ewlAssignees ?? [];
    require __DIR__ . '/_create_modal.php';
    ?>
<?php endif; ?>
<?php if ($canManage): ?>
    <?php
    $projects = $ewlProjects ?? [];
    $assignees = $ewlAssignees ?? [];
    require __DIR__ . '/_edit_modal.php';
    ?>
<?php endif; ?>
<?php require __DIR__ . '/_complete_modal.php'; ?>
<?php require __DIR__ . '/_modals_script.php'; ?>
