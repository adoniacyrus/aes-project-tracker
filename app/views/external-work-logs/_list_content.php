<?php
$stats = $stats ?? ['total' => 0, 'completed' => 0, 'pending' => 0, 'total_hours' => 0];
$statusFilter = $statusFilter ?? '';
$ewlRefreshTarget = $ewlRefreshTarget ?? '#external-work-logs-ajax-content';
$showProject = $showProject ?? true;
$showFilters = $showFilters ?? true;
?>
<?php if ($showFilters): ?>
<div class="card-header bg-transparent border-bottom py-3 px-4">
    <form method="GET" action="<?php echo e(route('external-work-logs')); ?>" class="row g-3 align-items-end ajax-filter-form" data-ajax-target="<?php echo e($ewlRefreshTarget); ?>">
        <input type="hidden" name="partial" value="1">
        <div class="col-md-4 col-lg-3">
            <label class="form-label fs-8 text-secondary font-weight-semibold">Status</label>
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <?php foreach (external_work_log_statuses() as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>>
                        <?php echo e($status); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <?php
                $clearFiltersUrl = route('external-work-logs', ['partial' => 1]);
                $clearFiltersTarget = $ewlRefreshTarget;
                require __DIR__ . '/../partials/_clear_filters_link.php';
            ?>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="row g-3 p-4 pb-0">
    <div class="col-6 col-lg-3">
        <div class="border rounded px-3 py-2">
            <div class="fs-8 text-secondary text-uppercase font-weight-bold">Total Logs</div>
            <div class="fs-4 font-weight-bold"><?php echo (int)($stats['total'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="border rounded px-3 py-2">
            <div class="fs-8 text-secondary text-uppercase font-weight-bold">Completed</div>
            <div class="fs-4 font-weight-bold text-success"><?php echo (int)($stats['completed'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="border rounded px-3 py-2">
            <div class="fs-8 text-secondary text-uppercase font-weight-bold">Pending</div>
            <div class="fs-4 font-weight-bold text-warning"><?php echo (int)($stats['pending'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="border rounded px-3 py-2">
            <div class="fs-8 text-secondary text-uppercase font-weight-bold">Total Hours</div>
            <div class="fs-4 font-weight-bold"><?php echo e(format_work_hours($stats['total_hours'] ?? 0)); ?></div>
        </div>
    </div>
</div>

<div class="card-body p-0 pt-3">
    <?php require __DIR__ . '/_table.php'; ?>
</div>
