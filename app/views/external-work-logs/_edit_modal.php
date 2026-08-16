<?php
$ewlRefreshTarget = $ewlRefreshTarget ?? '#external-work-logs-ajax-content';
$projects = $projects ?? [];
$assignees = $assignees ?? [];
$includeStatus = true;
$log = [];
?>
<div class="modal fade" id="ewlEditModal" tabindex="-1" aria-labelledby="ewlEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="POST"
              action=""
              id="ewlEditForm"
              class="modal-content ajax-form"
              novalidate
              <?php if (!empty($ewlAjaxReload)): ?>
              data-ajax-reload="true"
              <?php else: ?>
              data-ajax-refresh="<?php echo e($ewlRefreshTarget); ?>"
              <?php endif; ?>>
            <div class="modal-header">
                <h5 class="modal-title" id="ewlEditModalLabel">
                    <i class="ti ti-edit me-2"></i> Edit External Work Log
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php require __DIR__ . '/_form_fields.php'; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
