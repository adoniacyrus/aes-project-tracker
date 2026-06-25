<?php
if (!isset($floatingChat)) {
    if (!can_access_team_chat()) {
        return;
    }
    $floatingChat = floating_chat_config($ticket, $comments ?? [], 'team', 'team');
}

$fc = $floatingChat;
$prefix = $fc['prefix'];
$chatComments = $fc['comments'] ?? [];
usort($chatComments, function ($a, $b) {
    return (int)$a['id'] <=> (int)$b['id'];
});
$lastChatId = !empty($chatComments) ? (int)max(array_column($chatComments, 'id')) : 0;
$currentUserId = (int)($fc['current_user_id'] ?? ($_SESSION['user_id'] ?? 0));
$rootId = $prefix . '-root';
?>
<div id="<?php echo e($rootId); ?>"
     class="floating-chat-root team-chat-root <?php echo e($fc['offset_class']); ?>"
     data-ticket-id="<?php echo (int)$fc['ticket_id']; ?>"
     data-chat-channel="<?php echo e($fc['channel']); ?>"
     data-chat-prefix="<?php echo e($prefix); ?>"
     data-last-id="<?php echo $lastChatId; ?>"
     data-current-user-id="<?php echo $currentUserId; ?>"
     data-poll-url="<?php echo e($fc['poll_url']); ?>"
     data-post-url="<?php echo e($fc['post_url']); ?>"
     style="--chat-launcher-gradient: <?php echo e($fc['gradient']); ?>;">

    <button type="button"
            id="<?php echo e($prefix); ?>-launcher"
            class="team-chat-launcher floating-chat-launcher"
            aria-expanded="false"
            aria-controls="<?php echo e($prefix); ?>-window">
        <span class="team-chat-launcher-icon" aria-hidden="true"><?php echo $fc['launcher_icon']; ?></span>
        <span class="team-chat-launcher-label"><?php echo e($fc['launcher_label']); ?></span>
        <span id="<?php echo e($prefix); ?>-unread" class="team-chat-unread d-none">0</span>
    </button>

    <div id="<?php echo e($prefix); ?>-window"
         class="team-chat-window floating-chat-window"
         hidden
         aria-hidden="true"
         role="dialog"
         aria-labelledby="<?php echo e($prefix); ?>-title">
        <div class="team-chat-header">
            <div class="team-chat-header-info">
                <div class="team-chat-header-avatar">
                    <i class="ti ti-messages"></i>
                </div>
                <div class="min-w-0">
                    <h6 id="<?php echo e($prefix); ?>-title" class="team-chat-header-title mb-0"><?php echo e($fc['title']); ?></h6>
                    <small class="team-chat-header-subtitle"><?php echo e($fc['subtitle']); ?></small>
                </div>
            </div>
            <button type="button" class="team-chat-close floating-chat-close" aria-label="Close chat" data-chat-prefix="<?php echo e($prefix); ?>">
                <i class="ti ti-x"></i>
            </button>
        </div>

        <div class="team-chat-messages floating-chat-messages">
            <?php if (empty($chatComments)): ?>
                <div class="team-chat-empty">
                    <i class="ti ti-message-circle"></i>
                    <p class="mb-0">No messages yet.<br>Start the conversation.</p>
                </div>
            <?php else: ?>
                <?php foreach ($chatComments as $comment): ?>
                    <?php require __DIR__ . '/_team_chat_message.php'; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="team-chat-loading floating-chat-loading d-none" aria-live="polite">
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        </div>

        <form class="team-chat-composer floating-chat-form"
              action="<?php echo e($fc['post_url']); ?>"
              method="POST"
              enctype="multipart/form-data"
              data-post-url="<?php echo e($fc['post_url']); ?>">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="ticket_id" value="<?php echo (int)$fc['ticket_id']; ?>">
            <input type="hidden" name="chat_channel" value="<?php echo e($fc['channel']); ?>">
            <div class="team-chat-composer-main">
                <div class="team-chat-input-wrap">
                    <textarea name="comment"
                              rows="1"
                              class="team-chat-input floating-chat-input"
                              placeholder="Write a message..."></textarea>
                </div>
                <div class="team-chat-file-preview floating-chat-file-preview d-none" aria-live="polite"></div>
            </div>
            <div class="team-chat-composer-actions">
                <input type="file"
                       name="team_chat_attachment"
                       class="d-none floating-chat-file"
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,image/*">
                <button type="button"
                        class="team-chat-attach floating-chat-attach"
                        aria-label="Attach file"
                        title="Attach file">
                    <i class="ti ti-paperclip"></i>
                </button>
                <button type="submit" class="team-chat-send floating-chat-send" aria-label="Send message">
                    <i class="ti ti-send"></i>
                </button>
            </div>
        </form>
    </div>
</div>
