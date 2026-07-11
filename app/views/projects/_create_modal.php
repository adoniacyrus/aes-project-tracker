<?php
$projectCreateModalId = $projectCreateModalId ?? 'projectCreateModal';
$projectCreateModalTitle = $projectCreateModalTitle ?? 'Create New Project';
$projectCreateFormAction = $projectCreateFormAction ?? route('projects-create');
$projectCreateAjaxReload = !empty($projectCreateAjaxReload);
$projectCreateAjaxRefresh = $projectCreateAjaxRefresh ?? '#projects-ajax-content';
?>
<div class="modal fade" id="<?php echo e($projectCreateModalId); ?>" tabindex="-1" aria-labelledby="<?php echo e($projectCreateModalId); ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form action="<?php echo e($projectCreateFormAction); ?>"
              method="POST"
              class="modal-content ajax-form"
              data-ajax-reset="true"
              <?php if ($projectCreateAjaxReload): ?>
              data-ajax-reload="true"
              <?php else: ?>
              data-ajax-refresh="<?php echo e($projectCreateAjaxRefresh); ?>"
              <?php endif; ?>>
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo e($projectCreateModalId); ?>Label"><i class="ti ti-folder-plus me-2"></i> <?php echo e($projectCreateModalTitle); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php require __DIR__ . '/_create_form_fields.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>
<?php
unset($projectCreateModalId, $projectCreateModalTitle, $projectCreateFormAction, $projectCreateAjaxReload, $projectCreateAjaxRefresh);
?>
