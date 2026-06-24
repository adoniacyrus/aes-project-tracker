<?php
if (!can_access_team_chat()) {
    return;
}

$teamChatComments = $comments ?? [];
usort($teamChatComments, function ($a, $b) {
    return (int)$a['id'] <=> (int)$b['id'];
});
$lastTeamChatId = !empty($teamChatComments) ? (int)max(array_column($teamChatComments, 'id')) : 0;
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
?>
<style>
#team-chat-root {
    position: fixed !important;
    bottom: 20px !important;
    right: 20px !important;
    z-index: 1100 !important;
    margin: 0 !important;
    padding: 0 !important;
    pointer-events: none;
}
#team-chat-launcher {
    display: inline-flex !important;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.1rem;
    border: none;
    border-radius: 999px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #fff !important;
    font-size: 0.875rem;
    font-weight: 600;
    box-shadow: 0 8px 28px rgba(99, 102, 241, 0.38);
    cursor: pointer;
    white-space: nowrap;
    pointer-events: auto;
    line-height: 1.2;
}
#team-chat-window[hidden] {
    display: none !important;
}
</style>
<div id="team-chat-root"
     class="team-chat-root"
     data-ticket-id="<?php echo (int)$ticket['id']; ?>"
     data-last-id="<?php echo $lastTeamChatId; ?>"
     data-current-user-id="<?php echo $currentUserId; ?>">

    <button type="button"
            id="team-chat-launcher"
            class="team-chat-launcher"
            aria-expanded="false"
            aria-controls="team-chat-window">
        <span class="team-chat-launcher-icon" aria-hidden="true">💬</span>
        <span class="team-chat-launcher-label">Team Chat</span>
        <span id="team-chat-unread" class="team-chat-unread d-none">0</span>
    </button>

    <div id="team-chat-window"
         class="team-chat-window"
         hidden
         aria-hidden="true"
         role="dialog"
         aria-labelledby="team-chat-title">
        <div class="team-chat-header">
            <div class="team-chat-header-info">
                <div class="team-chat-header-avatar">
                    <i class="ti ti-messages"></i>
                </div>
                <div class="min-w-0">
                    <h6 id="team-chat-title" class="team-chat-header-title mb-0">Team Discussion</h6>
                    <small class="team-chat-header-subtitle">Ticket #<?php echo (int)$ticket['id']; ?></small>
                </div>
            </div>
            <button type="button" id="team-chat-close" class="team-chat-close" aria-label="Close chat">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div id="team-chat-messages" class="team-chat-messages">
            <?php if (empty($teamChatComments)): ?>
                <div class="team-chat-empty">
                    <i class="ti ti-message-circle"></i>
                    <p class="mb-0">No messages yet.<br>Start the conversation.</p>
                </div>
            <?php else: ?>
                <?php foreach ($teamChatComments as $comment): ?>
                    <?php require __DIR__ . '/_team_chat_message.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="team-chat-loading" class="team-chat-loading d-none" aria-live="polite">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        </div>

        <form id="team-chat-form" action="<?php echo route('tickets-comment', ['id' => $ticket['id']]); ?>" method="POST" class="team-chat-composer">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="ticket_id" value="<?php echo (int)$ticket['id']; ?>">
            <div class="team-chat-input-wrap">
                <textarea name="comment"
                          id="team-chat-input"
                          rows="1"
                          class="team-chat-input"
                          placeholder="Write a message..."
                          required></textarea>
            </div>
            <button type="submit" id="team-chat-send" class="team-chat-send" aria-label="Send message">
                <i class="ti ti-send"></i>
            </button>
        </form>
    </div>
</div>
<script>
(function () {
    var root = document.getElementById('team-chat-root');
    if (root && root.parentNode !== document.body) {
        document.body.appendChild(root);
    }
})();
</script>
