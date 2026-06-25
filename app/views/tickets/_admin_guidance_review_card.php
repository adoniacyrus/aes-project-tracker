<?php
if (!can_admin_respond_to_guidance($userRole ?? '', $ticket)) {
    return;
}
$adminGuidanceReviewModalUrl = route('tickets-view', ['id' => $ticket['id'], 'partial' => 'admin-guidance-review-modal']);
?>
<button type="button"
        class="btn btn-warning btn-sm w-100 workflow-admin-guidance-review-btn"
        data-bs-toggle="modal"
        data-bs-target="#adminGuidanceReviewModal"
        data-load-url="<?php echo e($adminGuidanceReviewModalUrl); ?>">
    <i class="ti ti-message-question me-1"></i> Admin Review
</button>
