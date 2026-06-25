<?php
if (!can_admin_respond_to_guidance($userRole ?? '', $ticket)) {
    return;
}
?>
<div class="ticket-inline-review ticket-inline-review--guidance mb-2">
    <div class="ticket-inline-review__head">
        <i class="ti ti-message-question"></i>
        <span>Admin Review</span>
    </div>
    <p class="ticket-inline-review__hint">A team member requested your suggestions or clarification.</p>
    <dl class="ticket-meta-grid mb-2">
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
            <dd class="ticket-inline-review__message mb-0">
                <?php if (trim((string)($ticket['guidance_comment'] ?? '')) !== ''): ?>
                    <?php echo e($ticket['guidance_comment']); ?>
                <?php else: ?>
                    <span class="text-muted">No message provided.</span>
                <?php endif; ?>
            </dd>
        </div>
    </dl>
    <label for="adminGuidanceResponseInput" class="form-label font-weight-semibold fs-7 mb-1">Your Response <span class="text-danger">*</span></label>
    <textarea id="adminGuidanceResponseInput"
              rows="3"
              class="form-control form-control-sm mb-2"
              placeholder="Use SSO for this release.&#10;Validate email on the client side as well."></textarea>
    <button type="button" class="btn btn-warning btn-sm w-100" id="adminGuidanceRespondBtn">
        <i class="ti ti-send me-1"></i> Send Response
    </button>
</div>
