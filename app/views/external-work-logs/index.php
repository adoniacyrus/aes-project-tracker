<?php
$canCreate = $canCreate ?? can_create_external_work_log();
$ewlRefreshTarget = '#external-work-logs-ajax-content';
$statusFilter = $statusFilter ?? '';
?>
<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar bg-primary-subtle text-primary rounded" style="width: 48px; height: 48px;">
                            <i class="ti ti-notebook fs-2"></i>
                        </span>
                    </div>
                    <div class="col">
                        <h4 class="mb-0 font-weight-semibold">External Work Logs</h4>
                        <p class="text-secondary mb-0 fs-7">Document work completed from off-platform client requests (email, calls, meetings, and more).</p>
                    </div>
                    <?php if ($canCreate): ?>
                    <div class="col-auto">
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#ewlCreateModal">
                            <i class="ti ti-plus"></i> Create Log
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm border border-light"
     id="external-work-logs-ajax-content"
     data-ajax-container
     data-ajax-refresh-url="<?php echo e(route('external-work-logs', ['partial' => 1, 'status' => $statusFilter])); ?>">
    <?php require __DIR__ . '/_list_content.php'; ?>
</div>

<?php if ($canCreate): ?>
    <?php require __DIR__ . '/_create_modal.php'; ?>
<?php endif; ?>
<?php if ($canManage ?? can_manage_external_work_logs()): ?>
    <?php require __DIR__ . '/_edit_modal.php'; ?>
<?php endif; ?>
<?php require __DIR__ . '/_complete_modal.php'; ?>
<?php require __DIR__ . '/_modals_script.php'; ?>
