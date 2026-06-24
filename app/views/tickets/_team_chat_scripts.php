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
    let pendingFile = null;

    const $window = $('#team-chat-window');
    const $launcher = $('#team-chat-launcher');
    const $close = $('#team-chat-close');
    const $messages = $('#team-chat-messages');
    const $loading = $('#team-chat-loading');
    const $unread = $('#team-chat-unread');
    const $form = $('#team-chat-form');
    const $input = $('#team-chat-input');
    const $send = $('#team-chat-send');
    const $attachBtn = $('#team-chat-attach');
    const $fileInput = $('#team-chat-file');
    const $filePreview = $('#team-chat-file-preview');
    const $attachmentModal = $('#teamChatAttachmentModal');
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

    function formatFileSize(bytes) {
        bytes = parseInt(bytes, 10) || 0;
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function buildAttachmentsHtml(attachments, isOutgoing) {
        if (!attachments || !attachments.length) return '';

        const outgoingClass = isOutgoing ? ' team-chat-attachments--outgoing' : '';
        let html = `<div class="team-chat-attachments${outgoingClass}">`;

        attachments.forEach(function(att) {
            const name = att.original_name || 'Attachment';
            const sizeLabel = att.size_label || formatFileSize(att.file_size);
            const viewUrl = att.view_url || '#';
            const downloadUrl = att.download_url || viewUrl;
            const kind = att.kind || (att.is_image ? 'image' : 'document');
            const dataAttrs = `data-attachment-id="${att.id}" data-attachment-name="${escapeHtml(name)}" data-attachment-size="${escapeHtml(sizeLabel)}" data-attachment-type="${escapeHtml(att.file_type || '')}" data-attachment-view="${escapeHtml(viewUrl)}" data-attachment-download="${escapeHtml(downloadUrl)}" data-attachment-kind="${escapeHtml(kind)}"`;

            if (kind === 'image' || att.is_image) {
                html += `<button type="button" class="team-chat-attachment-thumb team-chat-attachment-open" ${dataAttrs} data-attachment-image="1" aria-label="View image ${escapeHtml(name)}"><img src="${escapeHtml(viewUrl)}" alt="${escapeHtml(name)}" loading="lazy"></button>`;
            } else {
                const ext = (name.split('.').pop() || '').toUpperCase();
                const openLabel = kind === 'pdf' ? 'Open' : 'View';
                html += `
                    <div class="team-chat-attachment-doc">
                        <div class="team-chat-attachment-doc-icon" aria-hidden="true">📄</div>
                        <div class="team-chat-attachment-doc-meta">
                            <span class="team-chat-attachment-doc-name" title="${escapeHtml(name)}">${escapeHtml(name)}</span>
                            <small class="team-chat-attachment-doc-size">${escapeHtml(sizeLabel)} · ${escapeHtml(ext)}</small>
                        </div>
                        <div class="team-chat-attachment-doc-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary team-chat-attachment-open" ${dataAttrs} data-attachment-image="0">${openLabel}</button>
                            <a href="${escapeHtml(downloadUrl)}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Download</a>
                        </div>
                    </div>`;
            }
        });

        html += '</div>';
        return html;
    }

    function buildTeamChatMessageHtml(comment) {
        const commentId = parseInt(comment.id, 10);
        const isSystem = isSystemMessage(comment.comment);
        const isOwn = !isSystem && parseInt(comment.user_id, 10) === currentUserId;
        const timeLabel = formatChatDate(comment.created_at, isSystem);
        const attachments = comment.attachments || [];
        const hasText = $.trim(comment.comment || '') !== '';
        const attachmentsHtml = buildAttachmentsHtml(attachments, isOwn);
        const textHtml = hasText ? `<p class="team-chat-text mb-0${attachments.length ? ' mt-2' : ''}">${escapeHtml(comment.comment || '')}</p>` : '';

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
                            ${attachmentsHtml}
                            ${textHtml}
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
                        ${attachmentsHtml}
                        ${textHtml}
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

    function clearFilePreview() {
        pendingFile = null;
        $fileInput.val('');
        $filePreview.addClass('d-none').empty();
    }

    function renderFilePreview(file) {
        if (!file) {
            clearFilePreview();
            return;
        }

        pendingFile = file;
        const sizeLabel = formatFileSize(file.size);
        const isImage = file.type && file.type.startsWith('image/');
        let previewInner = `<span class="team-chat-file-preview-icon"><i class="ti ti-file"></i></span><span class="team-chat-file-preview-name text-truncate">${escapeHtml(file.name)}</span><small class="team-chat-file-preview-size">${sizeLabel}</small>`;

        if (isImage) {
            const objectUrl = URL.createObjectURL(file);
            previewInner = `<img src="${objectUrl}" alt="" class="team-chat-file-preview-thumb"><span class="team-chat-file-preview-name text-truncate">${escapeHtml(file.name)}</span><small class="team-chat-file-preview-size">${sizeLabel}</small>`;
        }

        $filePreview
            .removeClass('d-none')
            .html(`
                <div class="team-chat-file-preview-card">
                    ${previewInner}
                    <button type="button" class="team-chat-file-preview-remove" aria-label="Remove attachment"><i class="ti ti-x"></i></button>
                </div>
            `);
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

    function getAttachmentTriggerData($trigger) {
        const name = $trigger.attr('data-attachment-name') || '';
        let kind = $trigger.attr('data-attachment-kind') || '';
        if (!kind) {
            const lower = name.toLowerCase();
            if (/\.(jpe?g|png|gif|webp)$/.test(lower)) {
                kind = 'image';
            } else if (/\.pdf$/.test(lower)) {
                kind = 'pdf';
            } else {
                kind = 'document';
            }
        }

        return {
            name: name,
            size: $trigger.attr('data-attachment-size') || '',
            type: $trigger.attr('data-attachment-type') || '',
            viewUrl: $trigger.attr('data-attachment-view') || '#',
            downloadUrl: $trigger.attr('data-attachment-download') || $trigger.attr('data-attachment-view') || '#',
            kind: kind,
            isImage: kind === 'image' || String($trigger.attr('data-attachment-image')) === '1'
        };
    }

    function openAttachmentModal($trigger, data) {
        const attachment = data || getAttachmentTriggerData($trigger);
        const name = attachment.name || 'Attachment';
        const size = attachment.size || '';
        const type = attachment.type || '';
        const viewUrl = attachment.viewUrl || '#';
        const downloadUrl = attachment.downloadUrl || viewUrl;

        $('#teamChatAttachmentModalLabel').text(name);
        $('#teamChatAttachmentModalMeta').text([type, size].filter(Boolean).join(' · '));
        $('#teamChatAttachmentModalDownload').attr('href', downloadUrl);

        const $openBtn = $('#teamChatAttachmentModalOpen');
        const $body = $('#teamChatAttachmentModalBody');

        $body.html(`<div class="text-center"><img src="${escapeHtml(viewUrl)}" alt="${escapeHtml(name)}" class="team-chat-modal-image img-fluid rounded"></div>`);
        $openBtn.addClass('d-none').attr('href', '#');

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance($attachmentModal.get(0)).show();
        } else {
            $attachmentModal.modal('show');
        }
    }

    function openUrlInNewTab(url) {
        if (!url || url === '#') {
            return false;
        }
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        document.body.appendChild(link);
        link.click();
        link.remove();
        return true;
    }

    function openTeamChatAttachment($trigger) {
        const attachment = getAttachmentTriggerData($trigger);
        const viewUrl = attachment.viewUrl;
        const downloadUrl = attachment.downloadUrl;

        if (!viewUrl || viewUrl === '#') {
            showToast('Attachment URL is unavailable.', 'danger');
            return;
        }

        if (attachment.kind === 'image' || attachment.isImage) {
            openAttachmentModal($trigger, attachment);
            return;
        }

        if (attachment.kind === 'pdf') {
            if (!openUrlInNewTab(viewUrl)) {
                showToast('Unable to open PDF.', 'danger');
            }
            return;
        }

        if (!openUrlInNewTab(downloadUrl)) {
            showToast('Unable to download file.', 'danger');
        }
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

    $attachBtn.on('click', function() {
        $fileInput.trigger('click');
    });

    $fileInput.on('change', function() {
        const file = this.files && this.files[0] ? this.files[0] : null;
        if (!file) {
            clearFilePreview();
            return;
        }
        renderFilePreview(file);
    });

    $filePreview.on('click', '.team-chat-file-preview-remove', function() {
        clearFilePreview();
    });

    $messages.on('click', '.team-chat-attachment-open', function(e) {
        e.preventDefault();
        openTeamChatAttachment($(this));
    });

    $messages.on('click', '.team-chat-attachment-doc', function(e) {
        if ($(e.target).closest('a').length) {
            return;
        }
        const $btn = $(this).find('.team-chat-attachment-open').first();
        if ($btn.length) {
            e.preventDefault();
            openTeamChatAttachment($btn);
        }
    });

    $form.on('submit', function(e) {
        e.preventDefault();
        const message = $.trim($input.val());
        const file = ($fileInput.get(0) && $fileInput.get(0).files[0]) ? $fileInput.get(0).files[0] : null;

        if (!message && !file) {
            showToast('Please enter a message or attach a file.', 'warning');
            return;
        }

        const formData = new FormData($form.get(0));
        const originalHtml = $send.html();
        $send.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function(response) {
            if (response && response.success && response.comment) {
                appendTeamChatMessages([response.comment], true);
                $input.val('');
                clearFilePreview();
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
