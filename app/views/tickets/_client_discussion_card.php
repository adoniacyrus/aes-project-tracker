<?php
if (!can_access_client_chat($userRole ?? '')) {
    return;
}
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-messages text-primary"></i>
        <span>Client Discussion</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <p class="ticket-sidebar-hint mb-3">Requirements discussion, negotiations and commercial communication.</p>
        <button type="button"
                class="btn btn-outline-primary btn-sm w-100"
                id="open-client-discussion-btn"
                data-chat-launcher="client-chat-launcher">
            <i class="ti ti-message-circle me-1"></i> Open Client Discussion
        </button>
    </div>
</div>
