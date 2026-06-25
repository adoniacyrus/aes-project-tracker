<?php
$canViewReviewComment = can_view_latest_review_comment($userRole ?? '', $ticket, (int)($currentUserId ?? ($_SESSION['user_id'] ?? 0)));
if (!$canViewReviewComment) {
    return;
}
?>
<div class="alert alert-warning border border-warning-subtle shadow-sm mb-4" role="status">
    <div class="d-flex align-items-start gap-2">
        <i class="ti ti-message-report fs-5 mt-1"></i>
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <strong class="fs-7">Latest Review Comment</strong>
                <?php if (!empty($ticket['latest_review_at'])): ?>
                    <small class="text-muted">
                        <?php echo e($ticket['latest_reviewer_name'] ?? 'Admin'); ?>
                        &middot;
                        <?php echo date('M d, Y g:i A', strtotime($ticket['latest_review_at'])); ?>
                    </small>
                <?php endif; ?>
            </div>
            <p class="mb-0 text-secondary" style="white-space: pre-line;"><?php echo e($ticket['latest_review_comment']); ?></p>
        </div>
    </div>
</div>
