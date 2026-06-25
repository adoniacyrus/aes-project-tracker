<?php
$userRole = $userRole ?? ($_SESSION['user_role'] ?? '');
if (!can_access_team_chat($userRole)) {
    return;
}
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-users-group text-primary"></i>
        <span>Team Chat</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <p class="ticket-sidebar-hint mb-3">Discussion between admin, developers, and client on this ticket.</p>
        <button type="button"
                class="btn btn-outline-primary btn-sm w-100"
                data-chat-launcher="team-chat-launcher">
            <i class="ti ti-message-circle me-1"></i> Open Team Chat
        </button>
    </div>
</div>
