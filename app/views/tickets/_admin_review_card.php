<?php
if (!can_admin_review_ticket($userRole ?? '', $ticket)) {
    return;
}
$adminReviewModalUrl = route('tickets-view', ['id' => $ticket['id'], 'partial' => 'admin-review-modal']);
?>
<button type="button"
        class="btn btn-warning btn-sm w-100 workflow-admin-review-btn"
        data-bs-toggle="modal"
        data-bs-target="#adminReviewModal"
        data-load-url="<?php echo e($adminReviewModalUrl); ?>">
    <i class="ti ti-clipboard-check me-1"></i> Review Resolution
</button>
