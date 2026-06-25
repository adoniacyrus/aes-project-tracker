<?php
if (!can_admin_review_ticket($userRole ?? '', $ticket)) {
    return;
}
?>
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="adminReviewModalLabel"><i class="ti ti-clipboard-check me-2"></i> Admin Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <p class="text-secondary fs-7 mb-3">A team member submitted this ticket for your review.</p>
        <dl class="ticket-meta-grid mb-3">
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
                <dd class="mb-0" style="white-space: pre-line;">
                    <?php if (trim((string)($ticket['resolution_comment'] ?? '')) !== ''): ?>
                        <?php echo e($ticket['resolution_comment']); ?>
                    <?php else: ?>
                        <span class="text-muted">No comment provided.</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <label for="adminReviewCommentInput" class="form-label font-weight-semibold">Your Review Comment <span class="text-muted fw-normal">(optional)</span></label>
        <textarea id="adminReviewCommentInput"
                  rows="4"
                  class="form-control"
                  placeholder="Please fix validation for empty email."></textarea>
        <p class="text-muted fs-8 mb-0 mt-2">Use this comment when returning the ticket to the development team.</p>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="adminReviewReturnBtn">
            <i class="ti ti-arrow-back-up me-1"></i> Return to Development
        </button>
        <button type="button" class="btn btn-success" id="adminReviewApproveBtn">
            <i class="ti ti-circle-check me-1"></i> Approve &amp; Complete
        </button>
    </div>
</div>
