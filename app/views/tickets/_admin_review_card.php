<?php
if (!can_admin_review_ticket($userRole ?? '', $ticket)) {
    return;
}
?>
<div class="card border border-warning shadow-sm mb-2 ticket-admin-review-card">
    <div class="card-body py-3 px-3">
        <div class="d-flex align-items-start gap-2 mb-3">
            <i class="ti ti-clipboard-check text-warning mt-1"></i>
            <div>
                <span class="fs-7 font-weight-semibold d-block">Admin Review</span>
                <small class="text-secondary">A team member submitted this ticket for your review.</small>
            </div>
        </div>

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
                <dt>Latest Resolution Comment</dt>
                <dd class="mb-0" style="white-space: pre-line;">
                    <?php if (trim((string)($ticket['resolution_comment'] ?? '')) !== ''): ?>
                        <?php echo e($ticket['resolution_comment']); ?>
                    <?php else: ?>
                        <span class="text-muted">No comment provided.</span>
                    <?php endif; ?>
                </dd>
            </div>
        </dl>

        <form action="<?php echo route('tickets-approve-review', ['id' => $ticket['id']]); ?>"
              method="POST"
              class="ajax-form mb-2"
              data-confirm="Approve this ticket and mark it as Completed?">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
            <button type="submit" class="btn btn-success btn-sm w-100">
                <i class="ti ti-circle-check me-1"></i> Approve &amp; Complete
            </button>
        </form>

        <button type="button"
                class="btn btn-outline-warning btn-sm w-100"
                data-bs-toggle="modal"
                data-bs-target="#returnToDevelopmentModal">
            <i class="ti ti-arrow-back-up me-1"></i> Return to Development
        </button>
    </div>
</div>
