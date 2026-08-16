<?php
$ewlRefreshTarget = $ewlRefreshTarget ?? '#external-work-logs-ajax-content';
?>
<div class="modal fade" id="ewlCompleteModal" tabindex="-1" aria-labelledby="ewlCompleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST"
              action=""
              id="ewlCompleteForm"
              class="modal-content ajax-form"
              novalidate
              <?php if (!empty($ewlAjaxReload)): ?>
              data-ajax-reload="true"
              <?php else: ?>
              data-ajax-refresh="<?php echo e($ewlRefreshTarget); ?>"
              <?php endif; ?>>
            <div class="modal-header">
                <h5 class="modal-title" id="ewlCompleteModalLabel">
                    <i class="ti ti-circle-check me-2"></i> Complete External Work Log
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="status" value="Completed">
                <p class="text-secondary fs-7 mb-3">
                    Completing <strong id="ewlCompleteTitle">this log</strong> requires actual hours and completion notes.
                </p>
                <div class="mb-3">
                    <label class="form-label required">Actual Hours</label>
                    <input type="number" name="actual_hours" class="form-control" min="0" step="0.25" required>
                </div>
                <div class="mb-0">
                    <label class="form-label required">Completion Notes</label>
                    <textarea name="completion_notes" class="form-control" rows="4" required placeholder="What was delivered or changed?"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Mark Completed</button>
            </div>
        </form>
    </div>
</div>
