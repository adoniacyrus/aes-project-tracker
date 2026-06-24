<?php if (isset($ticket['id']) && can_access_team_chat()): ?>
<script>
$(document).ready(function() {
    const $root = $('#team-chat-root');
    if (!$root.length) return;

    if (!$root.parent().is('body')) {
        $('body').append($root);
    }

    const ticketId = parseInt($root.data('ticket-id'), 10);
    const currentUserId = parseInt($root.data('current-user-id'), 10) || 0;
    let lastKnownId = 0;
    let unreadCount = 0;
    let chatOpen = false;
    let isPolling = false;

    const $window = $('#team-chat-window');
    const $launcher = $('#team-chat-launcher');
    const $close = $('#team-chat-close');
    const $messages = $('#team-chat-messages');
    const $loading = $('#team-chat-loading');
    const $unread = $('#team-chat-unread');
    const $form = $('#team-chat-form');
    const $input = $('#team-chat-input');
    const $send = $('#team-chat-send');
    const pollUrl = <?php echo json_encode(route('tickets-comment', ['id' => $ticket['id']])); ?>;

    function getLastRenderedCommentId() {
        let maxId = 0;
        $messages.find('[data-comment-id]').each(function() {
            const id = parseInt($(this).attr('data-comment-id'), 10);
            if (!Number.isNaN(id) && id > maxId) {
                maxId = id;
            }
        });
        return maxId;
    }

    lastKnownId = getLastRenderedCommentId();

    function isSystemMessage(text) {
        const value = (text || '').trim();
        return value.startsWith('System Action:') || value.startsWith('[');
    }

    function formatSystemMessage(text) {
        let value = (text || '').trim();
        value = value.replace(/^System Action:\s*/i, '');
        value = value.replace(/^\[([^\]]+)\]\s*/, '$1 — ');
        value = value.replace(/\*\*/g, '');
        return value.trim();
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function formatChatDate(dateString, includeYear) {
        if (!dateString) return 'Just now';
        const date = new Date(dateString.replace(/-/g, '/'));
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const m = months[date.getMonth()];
        const d = String(date.getDate()).padStart(2, '0');
        const y = date.getFullYear();
        const h = String(date.getHours()).padStart(2, '0');
        const min = String(date.getMinutes()).padStart(2, '0');
        return includeYear ? `${m} ${d}, ${y} ${h}:${min}` : `${m} ${d}, ${h}:${min}`;
    }

    function buildTeamChatMessageHtml(comment) {
        const commentId = parseInt(comment.id, 10);
        const isSystem = isSystemMessage(comment.comment);
        const isOwn = !isSystem && parseInt(comment.user_id, 10) === currentUserId;
        const timeLabel = formatChatDate(comment.created_at, isSystem);

        if (isSystem) {
            return `
                <div class="team-chat-message team-chat-message--system" data-comment-id="${commentId}">
                    <div class="team-chat-event">
                        <span class="team-chat-event-line" aria-hidden="true"></span>
                        <div class="team-chat-event-body">
                            <span class="team-chat-event-text">${escapeHtml(formatSystemMessage(comment.comment))}</span>
                            <small class="team-chat-event-time">${timeLabel}</small>
                        </div>
                        <span class="team-chat-event-line" aria-hidden="true"></span>
                    </div>
                </div>`;
        }

        if (isOwn) {
            return `
                <div class="team-chat-message team-chat-message--outgoing" data-comment-id="${commentId}">
                    <div class="team-chat-bubble-wrap">
                        <div class="team-chat-bubble team-chat-bubble--outgoing">
                            <p class="team-chat-text mb-0">${escapeHtml(comment.comment || '')}</p>
                        </div>
                        <small class="team-chat-time">${timeLabel}</small>
                    </div>
                </div>`;
        }

        return `
            <div class="team-chat-message team-chat-message--incoming" data-comment-id="${commentId}">
                <div class="team-chat-bubble-wrap">
                    <span class="team-chat-sender">${escapeHtml(comment.full_name || '')}</span>
                    <div class="team-chat-bubble team-chat-bubble--incoming">
                        <p class="team-chat-text mb-0">${escapeHtml(comment.comment || '')}</p>
                    </div>
                    <small class="team-chat-time">${timeLabel}</small>
                </div>
            </div>`;
    }

    function scrollTeamChatToBottom() {
        const el = $messages.get(0);
        if (el) {
            el.scrollTop = el.scrollHeight;
        }
    }

    function updateUnreadBadge() {
        if (unreadCount > 0 && !chatOpen) {
            $unread.text(unreadCount > 99 ? '99+' : unreadCount).removeClass('d-none');
        } else {
            $unread.addClass('d-none').text('0');
        }
    }

    function appendTeamChatMessages(comments, markRead) {
        if (!comments || !comments.length) return;

        $messages.find('.team-chat-empty').remove();

        comments.forEach(function(comment) {
            const commentId = parseInt(comment.id, 10);
            if ($messages.find('[data-comment-id="' + commentId + '"]').length) {
                return;
            }
            $messages.append(buildTeamChatMessageHtml(comment));
            if (commentId > lastKnownId) {
                lastKnownId = commentId;
            }
        });

        if (markRead) {
            unreadCount = 0;
            updateUnreadBadge();
            scrollTeamChatToBottom();
        }
    }

    function openTeamChat() {
        chatOpen = true;
        $root.addClass('is-open');
        $window.removeAttr('hidden').attr('aria-hidden', 'false');
        $launcher.attr('aria-expanded', 'true');
        unreadCount = 0;
        updateUnreadBadge();
        pollTeamChat(true);
        scrollTeamChatToBottom();
        setTimeout(function() { $input.trigger('focus'); }, 200);
    }

    function closeTeamChat() {
        chatOpen = false;
        $root.removeClass('is-open');
        $window.attr('hidden', 'hidden').attr('aria-hidden', 'true');
        $launcher.attr('aria-expanded', 'false');
    }

    function pollTeamChat(forceRender) {
        if (isPolling) return;
        isPolling = true;
        if (chatOpen) {
            $loading.removeClass('d-none');
        }

        const pollLastId = (chatOpen || forceRender) ? getLastRenderedCommentId() : lastKnownId;

        $.ajax({
            url: pollUrl,
            type: 'GET',
            data: { id: ticketId, last_id: pollLastId },
            dataType: 'json'
        }).done(function(response) {
            if (response && response.success && response.comments && response.comments.length) {
                if (chatOpen || forceRender) {
                    appendTeamChatMessages(response.comments, true);
                    lastKnownId = Math.max(lastKnownId, getLastRenderedCommentId());
                } else {
                    response.comments.forEach(function(comment) {
                        const commentId = parseInt(comment.id, 10);
                        if (commentId > lastKnownId) {
                            lastKnownId = commentId;
                            unreadCount += 1;
                        }
                    });
                    updateUnreadBadge();
                }
            }
        }).always(function() {
            isPolling = false;
            $loading.addClass('d-none');
        });
    }

    $launcher.on('click', function() {
        openTeamChat();
    });

    $close.on('click', function() {
        closeTeamChat();
    });

    $(document).on('keydown.teamChat', function(e) {
        if (e.key === 'Escape' && chatOpen) {
            closeTeamChat();
        }
    });

    $form.on('submit', function(e) {
        e.preventDefault();
        const message = $.trim($input.val());
        if (!message) return;

        const originalHtml = $send.html();
        $send.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json'
        }).done(function(response) {
            if (response && response.success && response.comment) {
                appendTeamChatMessages([response.comment], true);
                $input.val('');
            } else {
                showToast((response && response.message) || 'Failed to send message.', 'danger');
            }
        }).fail(function() {
            showToast('Failed to send message.', 'danger');
        }).always(function() {
            $send.prop('disabled', false).html(originalHtml);
            $input.trigger('focus');
        });
    });

    $input.on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $form.trigger('submit');
        }
    });

    setInterval(pollTeamChat, 5000);
});
</script>
<?php endif; ?>
