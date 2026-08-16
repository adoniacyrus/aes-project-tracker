<?php
$userRole = $_SESSION['user_role'] ?? '';
$canCreate = can_create_external_work_log($userRole);
$showProject = false;
$showFilters = false;
$ewlAjaxReload = true;
$ewlRefreshTarget = '#project-ewl-pane';
$stats = $externalWorkLogStats ?? ['total' => 0, 'completed' => 0, 'pending' => 0, 'total_hours' => 0];
$logs = $externalWorkLogs ?? [];
?>
<div class="card shadow-sm border border-light">
    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="d-flex align-items-center gap-2">
            <i class="ti ti-notebook text-primary fs-4"></i> External Work Logs
        </span>
        <?php if ($canCreate): ?>
            <button type="button" class="btn btn-sm btn-primary d-flex align-items-center gap-1 font-weight-medium" data-bs-toggle="modal" data-bs-target="#ewlCreateModal">
                <i class="ti ti-plus"></i> Create Log
            </button>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . '/_list_content.php'; ?>
</div>
