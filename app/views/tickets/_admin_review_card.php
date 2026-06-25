<?php
if (!can_admin_review_ticket($userRole ?? '', $ticket)) {
    return;
}
?>
<div class="ticket-inline-review ticket-inline-review--resolution mb-2">
    <div class="ticket-inline-review__head">
        <i class="ti ti-clipboard-check"></i>
        <span>Review Resolution</span>
    </div>
    <p class="ticket-inline-review__hint">A team member submitted this ticket for your review.</p>
    <dl class="ticket-meta-grid mb-2">
        <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
            <dt>Submitted By</dt>
            <dd><?php echo e($ticket['resolution_submitter_name'] ?? 'Unknown'); ?></dd>
        </div>
        <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
            <dt>Submission Time</dt>
            <dd>
                <?php if (!empty($ticket['resolution_submitted_at'])): ?>
                    <?php echo date('M d, Y g:i A', strtotime($ticket['resolution_submitted_at'])); ?>
                <?php else: ?>
                    <span class="text-muted">Not available</span>
                <?php endif; ?>
            </dd>
        </div>
        <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
            <dt>Resolution Comment</dt>
            <dd class="ticket-inline-review__message mb-0">
                <?php if (trim((string)($ticket['resolution_comment'] ?? '')) !== ''): ?>
                    <?php echo e($ticket['resolution_comment']); ?>
                <?php else: ?>
                    <span class="text-muted">No comment provided.</span>
                <?php endif; ?>
            </dd>
        </div>
    </dl>
    <label for="adminReviewCommentInput" class="form-label font-weight-semibold fs-7 mb-1">Your Review Comment <span class="text-muted fw-normal">(optional)</span></label>
    <textarea id="adminReviewCommentInput"
              rows="3"
              class="form-control form-control-sm mb-2"
              placeholder="Please fix validation for empty email."></textarea>
    <div class="d-flex flex-column gap-2">
        <button type="button" class="btn btn-success btn-sm w-100" id="adminReviewApproveBtn">
            <i class="ti ti-circle-check me-1"></i> Approve &amp; Complete
        </button>
        <button type="button" class="btn btn-warning btn-sm w-100" id="adminReviewReturnBtn">
            <i class="ti ti-arrow-back-up me-1"></i> Return to Development
        </button>
    </div>
</div>
