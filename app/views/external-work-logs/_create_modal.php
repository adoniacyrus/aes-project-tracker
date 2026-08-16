<?php
$ewlCreateModalId = $ewlCreateModalId ?? 'ewlCreateModal';
$ewlRefreshTarget = $ewlRefreshTarget ?? '#external-work-logs-ajax-content';
$hideProject = !empty($hideProject);
$lockedProjectId = $lockedProjectId ?? null;
$projects = $projects ?? [];
$assignees = $assignees ?? [];
?>
<div class="modal fade" id="<?php echo e($ewlCreateModalId); ?>" tabindex="-1" aria-labelledby="<?php echo e($ewlCreateModalId); ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST"
              action="<?php echo e(route('external-work-logs-create')); ?>"
              class="modal-content ajax-form"
              novalidate
              data-ajax-reset="true"
              <?php if (!empty($ewlAjaxReload)): ?>
              data-ajax-reload="true"
              <?php else: ?>
              data-ajax-refresh="<?php echo e($ewlRefreshTarget); ?>"
              <?php endif; ?>>
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo e($ewlCreateModalId); ?>Label">
                    <i class="ti ti-notebook me-2"></i> Create External Work Log
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php require __DIR__ . '/_form_fields.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Work Log</button>
            </div>
        </form>
    </div>
</div>
<?php
unset($hideProject, $lockedProjectId, $includeStatus);
?>
