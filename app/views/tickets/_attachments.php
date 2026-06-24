<?php
$canManageAttachments = ($userRole === 'client');
?>
    <div class="card mb-4 shadow-sm border border-light">
        <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="d-flex align-items-center gap-2 font-weight-semibold">
                <i class="ti ti-paperclip text-primary fs-4"></i> Attachments
            </span>
            <?php if (!empty($attachments)): ?>
                <span class="badge bg-light border text-secondary font-weight-semibold px-2 py-1 fs-8 rounded-pill">
                    <?php echo count($attachments); ?> file<?php echo count($attachments) !== 1 ? 's' : ''; ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body px-4 py-3">
            <?php if (empty($attachments)): ?>
                <p class="text-muted italic text-center py-2 mb-0 fs-7">No files uploaded yet.</p>
            <?php else: ?>
                <div class="row g-3 <?php echo $canManageAttachments ? 'mb-3' : 'mb-0'; ?>">
                    <?php foreach ($attachments as $attIndex => $att): ?>
                        <?php
                            $isImage = is_image_attachment($att['file_name'], $att['mime_type'] ?? null);
                            $previewType = attachment_preview_type($att['file_name'], $att['mime_type'] ?? null);
                            $attachmentUrl = attachment_url($att['file_path'], $ticket['id'], $att['id']);
                            $previewIcon = $previewType === 'pdf' ? 'ti-file-type-pdf' : ($isImage ? 'ti-photo' : 'ti-file');
                        ?>
                        <div class="col-sm-6 col-lg-4">
                            <div class="border rounded p-2 bg-light-subtle h-100">
                                <a href="#"
                                   class="attachment-preview-trigger d-block mb-2 text-decoration-none"
                                   data-attachment-index="<?php echo (int)$attIndex; ?>"
                                   data-attachment-url="<?php echo e($attachmentUrl); ?>"
                                   data-attachment-name="<?php echo e($att['file_name']); ?>"
                                   data-attachment-type="<?php echo e($previewType); ?>"
                                   data-attachment-size="<?php echo e(format_file_size($att['file_size'])); ?>">
                                    <?php if ($isImage): ?>
                                        <img src="<?php echo e($attachmentUrl); ?>" alt="<?php echo e($att['file_name']); ?>" class="img-fluid rounded w-100 attachment-preview-thumb" style="max-height: 140px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="d-flex flex-column align-items-center justify-content-center rounded bg-white border py-4 px-2 attachment-preview-thumb" style="min-height: 120px;">
                                            <i class="ti <?php echo $previewIcon; ?> text-primary" style="font-size: 2.5rem;"></i>
                                            <span class="text-muted fs-8 mt-2">Click to preview</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="d-flex align-items-center justify-content-between gap-2">
                                    <div class="text-truncate min-w-0">
                                        <a href="#"
                                           class="attachment-preview-trigger text-decoration-none fs-7 font-weight-medium d-block text-truncate"
                                           title="<?php echo e($att['file_name']); ?>"
                                           data-attachment-index="<?php echo (int)$attIndex; ?>"
                                           data-attachment-url="<?php echo e($attachmentUrl); ?>"
                                           data-attachment-name="<?php echo e($att['file_name']); ?>"
                                           data-attachment-type="<?php echo e($previewType); ?>"
                                           data-attachment-size="<?php echo e(format_file_size($att['file_size'])); ?>"><?php echo e($att['file_name']); ?></a>
                                        <small class="text-muted fs-8"><?php echo format_file_size($att['file_size']); ?></small>
                                    </div>
                                    <?php if ($canManageAttachments && (int)$att['user_id'] === (int)$_SESSION['user_id']): ?>
                                        <a href="<?php echo route('tickets-delete-attachment', ['id' => $ticket['id'], 'attachment_id' => $att['id']]); ?>" class="btn btn-sm btn-outline-danger border-0 flex-shrink-0 ajax-link" data-confirm="Are you sure you want to delete this attachment?" data-ajax-refresh="#ticket-attachments"><i class="ti ti-trash"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($canManageAttachments): ?>
                <form action="<?php echo route('tickets-attachment', ['id' => $ticket['id']]); ?>" method="POST" enctype="multipart/form-data" class="<?php echo empty($attachments) ? '' : 'border-top pt-3'; ?> ajax-form" data-ajax-refresh="#ticket-attachments" data-ajax-reset="true">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <input type="file" name="attachment" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" required>
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="ti ti-upload"></i> Upload</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
