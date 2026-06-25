<?php
if (!can_access_admin_dev_chat($userRole ?? '', $ticket, (int)($currentUserId ?? ($_SESSION['user_id'] ?? 0)))) {
    return;
}
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-tool text-primary"></i>
        <span>Development Discussion</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <p class="ticket-sidebar-hint mb-3">Admin and assigned team communication about implementation and review.</p>
        <button type="button"
                class="btn btn-outline-primary btn-sm w-100"
                id="open-admin-dev-discussion-btn"
                data-chat-launcher="admin-dev-chat-launcher">
            <i class="ti ti-message-circle me-1"></i> Open Team Discussion
        </button>
    </div>
</div>
