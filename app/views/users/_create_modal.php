<?php
$userCreateModalId = $userCreateModalId ?? 'userCreateModal';
$userCreateModalTitle = $userCreateModalTitle ?? 'Create New User';
$userCreateFormAction = $userCreateFormAction ?? route('users-create');
$userCreateAjaxReload = !empty($userCreateAjaxReload);
$userCreateAjaxRefresh = $userCreateAjaxRefresh ?? '#users-ajax-content';
$userCreateSubmitLabel = $userCreateSubmitLabel ?? 'Save User Account';
?>
<div class="modal fade" id="<?php echo e($userCreateModalId); ?>" tabindex="-1" aria-labelledby="<?php echo e($userCreateModalId); ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST"
              action="<?php echo e($userCreateFormAction); ?>"
              class="modal-content ajax-form"
              novalidate
              data-ajax-reset="true"
              <?php if ($userCreateAjaxReload): ?>
              data-ajax-reload="true"
              <?php else: ?>
              data-ajax-refresh="<?php echo e($userCreateAjaxRefresh); ?>"
              <?php endif; ?>>
            <div class="modal-header">
                <h5 class="modal-title" id="<?php echo e($userCreateModalId); ?>Label"><i class="ti ti-user-plus me-2"></i> <?php echo e($userCreateModalTitle); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php require __DIR__ . '/_create_form_fields.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><?php echo e($userCreateSubmitLabel); ?></button>
            </div>
        </form>
    </div>
</div>
<?php
unset($userCreateModalId, $userCreateModalTitle, $userCreateFormAction, $userCreateAjaxReload, $userCreateAjaxRefresh, $userCreateSubmitLabel);
?>
