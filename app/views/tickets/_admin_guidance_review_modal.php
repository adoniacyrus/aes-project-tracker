<?php
if (!can_admin_respond_to_guidance($userRole ?? '', $ticket)) {
    return;
}
?>
<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title" id="adminGuidanceReviewModalLabel"><i class="ti ti-message-question me-2"></i> Admin Review</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <p class="text-secondary fs-7 mb-3">A team member requested your suggestions or clarification.</p>
        <dl class="ticket-meta-grid mb-3">
            <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                <dt>Requested By</dt>
                <dd><?php echo e($ticket['guidance_requester_name'] ?? 'Unknown'); ?></dd>
            </div>
            <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                <dt>Request Time</dt>
                <dd>
                    <?php if (!empty($ticket['guidance_requested_at'])): ?>
                        <?php echo date('M d, Y g:i A', strtotime($ticket['guidance_requested_at'])); ?>
                    <?php else: ?>
                        <span class="text-muted">Not available</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                <dt>Developer Message</dt>
                <dd class="mb-0" style="white-space: pre-line;">
                    <?php if (trim((string)($ticket['guidance_comment'] ?? '')) !== ''): ?>
                        <?php echo e($ticket['guidance_comment']); ?>
                    <?php else: ?>
                        <span class="text-muted">No message provided.</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>
        <label for="adminGuidanceResponseInput" class="form-label font-weight-semibold">Your Response <span class="text-danger">*</span></label>
        <textarea id="adminGuidanceResponseInput"
                  rows="4"
                  class="form-control"
                  required
                  placeholder="Use SSO for this release.&#10;Validate email on the client side as well."></textarea>
        <p class="text-muted fs-8 mb-0 mt-2">Your response will be posted to Developer Chat.</p>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="adminGuidanceRespondBtn">
            <i class="ti ti-send me-1"></i> Send Response
        </button>
    </div>
</div>
