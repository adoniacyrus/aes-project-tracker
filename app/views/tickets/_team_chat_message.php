<?php
if (!isset($comment)) {
    return;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isSystem = is_team_chat_system_message($comment['comment']);
$isOwn = !$isSystem && (int)$comment['user_id'] === $currentUserId;
$messageClass = $isSystem ? 'team-chat-message--system' : ($isOwn ? 'team-chat-message--outgoing' : 'team-chat-message--incoming');
?>
<div class="team-chat-message <?php echo $messageClass; ?>" data-comment-id="<?php echo (int)$comment['id']; ?>">
    <?php if ($isSystem): ?>
        <div class="team-chat-event">
            <span class="team-chat-event-line" aria-hidden="true"></span>
            <div class="team-chat-event-body">
                <span class="team-chat-event-text"><?php echo e(format_team_chat_system_message($comment['comment'])); ?></span>
                <small class="team-chat-event-time"><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></small>
            </div>
            <span class="team-chat-event-line" aria-hidden="true"></span>
        </div>
    <?php else: ?>
        <div class="team-chat-bubble-wrap">
            <?php if (!$isOwn): ?>
                <span class="team-chat-sender"><?php echo e($comment['full_name']); ?></span>
            <?php endif; ?>
            <div class="team-chat-bubble <?php echo $isOwn ? 'team-chat-bubble--outgoing' : 'team-chat-bubble--incoming'; ?>">
                <p class="team-chat-text mb-0"><?php echo e($comment['comment']); ?></p>
            </div>
            <small class="team-chat-time"><?php echo date('M d, H:i', strtotime($comment['created_at'])); ?></small>
        </div>
    <?php endif; ?>
</div>
