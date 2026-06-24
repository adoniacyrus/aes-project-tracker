<?php
if (empty($attachments) || !is_array($attachments)) {
    return;
}

$isOutgoing = $isOutgoing ?? false;
?>
<div class="team-chat-attachments<?php echo $isOutgoing ? ' team-chat-attachments--outgoing' : ''; ?>">
    <?php foreach ($attachments as $attachment): ?>
        <?php
        $isImage = !empty($attachment['is_image']);
        $viewUrl = $attachment['view_url'] ?? '#';
        $downloadUrl = $attachment['download_url'] ?? $viewUrl;
        $originalName = $attachment['original_name'] ?? 'Attachment';
        $sizeLabel = $attachment['size_label'] ?? format_file_size($attachment['file_size'] ?? 0);
        $ext = strtoupper(pathinfo($originalName, PATHINFO_EXTENSION));
        ?>
        <?php if ($isImage): ?>
            <button type="button"
                    class="team-chat-attachment-thumb team-chat-attachment-open"
                    data-attachment-id="<?php echo (int)$attachment['id']; ?>"
                    data-attachment-name="<?php echo e($originalName); ?>"
                    data-attachment-size="<?php echo e($sizeLabel); ?>"
                    data-attachment-type="<?php echo e($attachment['file_type'] ?? 'image'); ?>"
                    data-attachment-view="<?php echo e($viewUrl); ?>"
                    data-attachment-download="<?php echo e($downloadUrl); ?>"
                    data-attachment-kind="<?php echo e($attachment['kind'] ?? 'image'); ?>"
                    data-attachment-image="1"
                    aria-label="View image <?php echo e($originalName); ?>">
                <img src="<?php echo e($viewUrl); ?>" alt="<?php echo e($originalName); ?>" loading="lazy">
            </button>
        <?php else: ?>
            <div class="team-chat-attachment-doc">
                <div class="team-chat-attachment-doc-icon" aria-hidden="true">📄</div>
                <div class="team-chat-attachment-doc-meta">
                    <span class="team-chat-attachment-doc-name" title="<?php echo e($originalName); ?>"><?php echo e($originalName); ?></span>
                    <small class="team-chat-attachment-doc-size"><?php echo e($sizeLabel); ?> · <?php echo e($ext); ?></small>
                </div>
                <div class="team-chat-attachment-doc-actions">
                    <button type="button"
                            class="btn btn-sm btn-outline-primary team-chat-attachment-open"
                            data-attachment-id="<?php echo (int)$attachment['id']; ?>"
                            data-attachment-name="<?php echo e($originalName); ?>"
                            data-attachment-size="<?php echo e($sizeLabel); ?>"
                            data-attachment-type="<?php echo e($attachment['file_type'] ?? $ext); ?>"
                            data-attachment-view="<?php echo e($viewUrl); ?>"
                            data-attachment-download="<?php echo e($downloadUrl); ?>"
                            data-attachment-kind="<?php echo e($attachment['kind'] ?? 'document'); ?>"
                            data-attachment-image="0">View</button>
                    <a href="<?php echo e($downloadUrl); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Download</a>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
