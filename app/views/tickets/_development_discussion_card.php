<?php
if (!can_access_admin_dev_chat($userRole ?? '', $ticket, (int)($currentUserId ?? ($_SESSION['user_id'] ?? 0)))) {
    return;
}

$userRole = $userRole ?? ($_SESSION['user_role'] ?? '');
$isAdmin = ($userRole === 'admin');
$canSubmitForReview = can_submit_ticket_for_review($userRole, $ticket, (int)($currentUserId ?? ($_SESSION['user_id'] ?? 0)));
$isPendingAdminReview = is_ticket_pending_admin_review($ticket);
$canRequestCommercialReview = !empty($allowedTransitions['__commercial_review__'] ?? []);
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-tool text-primary"></i>
        <span>Development Discussion</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <?php if (!$isAdmin): ?>
            <?php if ($canSubmitForReview): ?>
                <button type="button"
                        class="btn btn-success btn-sm w-100 mb-3"
                        data-bs-toggle="modal"
                        data-bs-target="#submitForReviewModal">
                    <i class="ti ti-send me-1"></i> Mark as Resolved
                </button>
            <?php elseif ($isPendingAdminReview && in_array($userRole, ['developer', 'intern'], true)): ?>
                <div class="alert alert-info py-2 px-3 mb-3 fs-7">
                    <i class="ti ti-clock me-1"></i> Submitted for admin review. Awaiting approval.
                </div>
            <?php endif; ?>

            <?php if ($canRequestCommercialReview): ?>
                <form action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>"
                      method="POST"
                      class="ajax-form mb-3"
                      data-confirm="<?php echo e(destructive_workflow_confirm_message('__commercial_review__')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
                    <input type="hidden" name="status" value="__commercial_review__">
                    <button type="submit" class="btn btn-outline-warning btn-sm w-100">
                        <i class="ti ti-flag me-1"></i> Request Commercial Review
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <p class="ticket-sidebar-hint mb-3">Admin and assigned team communication about implementation and review.</p>
        <button type="button"
                class="btn btn-outline-primary btn-sm w-100"
                id="open-admin-dev-discussion-btn"
                data-chat-launcher="admin-dev-chat-launcher">
            <i class="ti ti-message-circle me-1"></i> Open Developer Chat
        </button>
    </div>
</div>
