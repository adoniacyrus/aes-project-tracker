<div class="row row-cards mb-4">
    <!-- Breadcrumb and Quick Navigation -->
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-md-items-center gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 p-0 bg-transparent">
                            <li class="breadcrumb-item"><a href="?page=tickets" class="text-decoration-none">Tickets</a></li>
                            <li class="breadcrumb-item"><a href="?page=projects-view&id=<?php echo $ticket['project_id']; ?>" class="text-decoration-none"><?php echo e($ticket['project_code']); ?></a></li>
                            <li class="breadcrumb-item active text-dark" aria-current="page">Ticket #<?php echo $ticket['id']; ?></li>
                        </ol>
                    </nav>
                    <h3 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                        <span class="badge bg-secondary-subtle text-secondary font-monospace fs-6">#<?php echo $ticket['id']; ?></span>
                        <?php echo e($ticket['title']); ?>
                    </h3>
                </div>
                <div class="d-flex gap-2">
                    <a href="?page=tickets" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin' || 
                              (int)$ticket['created_by'] === (int)$_SESSION['user_id'] || 
                              (int)$ticket['assigned_to'] === (int)$_SESSION['user_id']): ?>
                        <a href="?page=tickets-edit&id=<?php echo $ticket['id']; ?>" class="btn btn-primary d-flex align-items-center gap-2">
                            <i class="ti ti-edit"></i> Edit Ticket
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column (Overview, Workflow, Tasks, Comments) -->
    <div class="col-12 col-lg-8">
        
        <!-- Ticket Core Description -->
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-semibold">
                    <i class="ti ti-notes text-primary fs-4"></i> Description
                </span>
                <div>
                    <span class="badge bg-light border text-dark font-weight-semibold px-2.5 py-1.5 fs-7 rounded-pill">
                        <?php echo e($ticket['category']); ?>
                    </span>
                </div>
            </div>
            <div class="card-body px-4 py-3">
                <p class="text-secondary leading-relaxed fs-6" style="white-space: pre-line;">
                    <?php echo e($ticket['description']); ?>
                </p>
            </div>
        </div>

        <!-- Workflow Engine Transitions Panel -->
        <div class="card mb-4 shadow-sm border border-light bg-light-subtle">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-git-fork me-2 text-primary fs-4"></i> Workflow Transitions
            </div>
            <div class="card-body px-4 py-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <span class="text-secondary fs-8 text-uppercase font-weight-bold d-block" style="letter-spacing: 0.5px;">Current Workflow Status</span>
                        <?php 
                            $statusClass = 'bg-secondary';
                            if ($ticket['status'] === 'Open') $statusClass = 'bg-info text-white';
                            if ($ticket['status'] === 'Awaiting Admin Approval') $statusClass = 'bg-warning text-dark';
                            if ($ticket['status'] === 'Awaiting Payment') $statusClass = 'bg-secondary-subtle text-dark border';
                            if ($ticket['status'] === 'Approved') $statusClass = 'bg-success-subtle text-success border';
                            if ($ticket['status'] === 'In Development') $statusClass = 'bg-primary text-white';
                            if ($ticket['status'] === 'Resolved') $statusClass = 'bg-success text-white';
                            if ($ticket['status'] === 'Reopened') $statusClass = 'bg-danger text-white';
                            if ($ticket['status'] === 'Closed') $statusClass = 'bg-dark text-white';
                            if ($ticket['status'] === 'Rejected') $statusClass = 'bg-danger-subtle text-danger border';
                            if ($ticket['status'] === 'On Hold') $statusClass = 'bg-warning text-dark';
                        ?>
                        <span class="badge <?php echo $statusClass; ?> px-2.5 py-1.5 fs-6 mt-1 rounded"><?php echo e($ticket['status']); ?></span>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($ticket['status'] === 'Awaiting Admin Approval' && $_SESSION['user_role'] === 'admin'): ?>
                            <!-- Specialized Admin Decision Buttons for Commercial Review -->
                            <form action="?page=tickets-workflow" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                <input type="hidden" name="status" value="Approved">
                                <button type="submit" class="btn btn-success d-flex align-items-center gap-1.5 px-3 py-2 fs-7 font-weight-semibold text-white">
                                    <i class="ti ti-check"></i> Approve
                                </button>
                            </form>
                            
                            <form action="?page=tickets-workflow" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                <input type="hidden" name="status" value="Awaiting Payment">
                                <button type="submit" class="btn btn-outline-secondary d-flex align-items-center gap-1.5 px-3 py-2 fs-7 font-weight-semibold">
                                    <i class="ti ti-credit-card"></i> Require Payment
                                </button>
                            </form>

                            <form action="?page=tickets-workflow" method="POST" class="d-inline">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                <input type="hidden" name="status" value="Open">
                                <button type="submit" class="btn btn-warning d-flex align-items-center gap-1.5 px-3 py-2 fs-7 font-weight-semibold text-dark">
                                    <i class="ti ti-calculator"></i> Request Estimation
                                </button>
                            </form>

                            <form action="?page=tickets-workflow" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this ticket?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                <input type="hidden" name="status" value="Rejected">
                                <button type="submit" class="btn btn-danger d-flex align-items-center gap-1.5 px-3 py-2 fs-7 font-weight-semibold text-white">
                                    <i class="ti ti-x"></i> Reject
                                </button>
                            </form>
                        <?php elseif (empty($allowedTransitions)): ?>
                            <span class="text-muted fs-7 italic">No transitions available in current state.</span>
                        <?php else: ?>
                            <?php foreach ($allowedTransitions as $targetStatus => $label): ?>
                                <form action="?page=tickets-workflow" method="POST" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $targetStatus; ?>">
                                    <button type="submit" class="btn btn-outline-primary d-flex align-items-center gap-1.5 px-3 py-2 fs-7 font-weight-semibold">
                                        <i class="ti ti-arrow-right-tail"></i> <?php echo e($label); ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task checklist -->
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-semibold">
                    <i class="ti ti-checkbox text-primary fs-4"></i> Tasks Checklist
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold rounded px-2">
                    <?php 
                        $completedCount = count(array_filter($tasks, fn($t) => $t['status'] === 'Completed'));
                        echo $completedCount . '/' . count($tasks);
                    ?> Completed
                </span>
            </div>
            
            <div class="card-body px-4 py-3">
                <!-- Task List -->
                <?php if (empty($tasks)): ?>
                    <p class="text-muted italic text-center py-2 mb-0 fs-7">No checklist tasks defined. Use the input below to add.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php foreach ($tasks as $task): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 border border-light rounded bg-light-subtle hover-bg-gray">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="form-check-input task-toggle-checkbox cursor-pointer" 
                                           data-task-id="<?php echo $task['id']; ?>" 
                                           <?php echo $task['status'] === 'Completed' ? 'checked' : ''; ?>
                                           style="width: 18px; height: 18px;">
                                    
                                    <span class="fs-7 text-dark <?php echo $task['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : 'font-weight-medium'; ?>">
                                        <?php echo e($task['task_name']); ?>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary fs-8">
                                        <?php echo e($task['status']); ?>
                                    </span>
                                    <a href="?page=tasks-edit&id=<?php echo $task['id']; ?>" class="text-secondary" title="Edit Task"><i class="ti ti-edit fs-5"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Create quick task form (Admin or Dev/Intern only) -->
                <?php if (($_SESSION['user_role'] ?? '') !== 'client'): ?>
                    <form action="?page=tasks-create" method="POST" class="mt-3 border-top pt-3">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        
                        <div class="input-group">
                            <input type="text" name="task_name" class="form-control" placeholder="Add a checklist task..." required>
                            <select name="assigned_member" class="form-select max-width-xs" style="max-width: 180px;">
                                <option value="">Assign Member</option>
                                <?php foreach ($projectMembers as $mem): ?>
                                    <option value="<?php echo $mem['user_id']; ?>"><?php echo e($mem['first_name']); ?> (<?php echo e($mem['role']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary px-3">Add Task</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comments Threads Section -->
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-messages me-2 text-primary fs-4"></i> Discussion Feed
            </div>
            <div class="card-body px-4 py-3">
                <!-- Comments list -->
                <?php if (empty($comments)): ?>
                    <p class="text-muted italic text-center py-4 mb-0 fs-7">No comments posted on this ticket yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($comments as $comment): ?>
                            <?php 
                                $isSystem = str_starts_with($comment['comment'], 'System Action:'); 
                                $avatarBg = $isSystem ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary';
                            ?>
                            <div class="d-flex align-items-start gap-2.5 p-3 rounded <?php echo $isSystem ? 'bg-light border border-light-subtle' : 'bg-white border'; ?>">
                                <div class="avatar <?php echo $avatarBg; ?> rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 36px; height: 36px; font-size: 12px; flex-shrink: 0;">
                                    <?php 
                                        if ($isSystem) {
                                            echo 'SYS';
                                        } else {
                                            echo strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1)); 
                                        }
                                    ?>
                                </div>
                                <div class="flex-fill leading-tight">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-weight-semibold text-dark fs-7">
                                            <?php echo $isSystem ? 'System Engine' : e($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                            <?php if (!$isSystem): ?>
                                                <span class="badge badge-role badge-<?php echo $comment['role']; ?> ms-1 text-uppercase fs-9" style="font-size: 0.55rem;"><?php echo e($comment['role']); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <small class="text-secondary fs-8"><?php echo date('M d, H:i', strtotime($comment['created_at'])); ?></small>
                                    </div>
                                    <p class="text-secondary mb-0 fs-7 leading-relaxed" style="white-space: pre-line;">
                                        <?php echo e($comment['comment']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add Comment Form -->
                <form action="?page=tickets-comment" method="POST" class="border-top pt-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label font-weight-semibold text-dark fs-7">Leave a Comment</label>
                        <textarea name="comment" rows="3" class="form-control" placeholder="Write feedback, question, or updates regarding this ticket..." required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fs-7 font-weight-semibold">Post Comment</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Right Column (Ticket details, attachments) -->
    <div class="col-12 col-lg-4">
        <!-- Ticket Metadata Properties -->
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-info-circle text-primary me-2 fs-4"></i> Properties
            </div>
            <div class="card-body px-4 py-3">
                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Project Space</span>
                    <p class="mb-0 font-weight-semibold text-dark fs-6 mt-1">
                        <a href="?page=projects-view&id=<?php echo $ticket['project_id']; ?>" class="text-decoration-none">
                            <?php echo e($ticket['project_name']); ?> (<?php echo e($ticket['project_code']); ?>)
                        </a>
                    </p>
                </div>

                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Ticket Priority</span>
                    <div class="mt-1">
                        <?php 
                            $priorityClass = 'bg-secondary-subtle text-secondary';
                            if ($ticket['priority'] === 'critical') $priorityClass = 'bg-danger-subtle text-danger';
                            if ($ticket['priority'] === 'high') $priorityClass = 'bg-warning-subtle text-warning-emphasis';
                            if ($ticket['priority'] === 'medium') $priorityClass = 'bg-primary-subtle text-primary';
                        ?>
                        <span class="badge <?php echo $priorityClass; ?> text-capitalize px-2 py-1 fs-7">
                            <?php echo e($ticket['priority']); ?>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Assigned Person</span>
                    <div class="mt-1 fs-7 text-dark">
                        <?php if ($ticket['assignee_first']): ?>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="avatar bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 32px; height: 32px; font-size: 11px;">
                                    <?php echo strtoupper(substr($ticket['assignee_first'], 0, 1) . substr($ticket['assignee_last'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="mb-0 font-weight-semibold"><?php echo e($ticket['assignee_first'] . ' ' . $ticket['assignee_last']); ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted italic mb-2">Unassigned</p>
                        <?php endif; ?>

                        <!-- Quick Assign Form (Admin or Creator/Assignee can modify) -->
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin' || (int)$ticket['created_by'] === (int)$_SESSION['user_id']): ?>
                            <form action="?page=tickets-edit&id=<?php echo $ticket['id']; ?>" method="POST" class="mt-1">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="project_id" value="<?php echo $ticket['project_id']; ?>">
                                <input type="hidden" name="title" value="<?php echo e($ticket['title']); ?>">
                                <input type="hidden" name="description" value="<?php echo e($ticket['description']); ?>">
                                <input type="hidden" name="category" value="<?php echo $ticket['category']; ?>">
                                <input type="hidden" name="priority" value="<?php echo $ticket['priority']; ?>">
                                <input type="hidden" name="status" value="<?php echo $ticket['status']; ?>">
                                <input type="hidden" name="due_date" value="<?php echo $ticket['due_date']; ?>">
                                
                                <select name="assigned_to" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">-- Assign Member --</option>
                                    <?php foreach ($projectMembers as $mem): ?>
                                        <option value="<?php echo $mem['user_id']; ?>" <?php echo (int)$ticket['assigned_to'] === (int)$mem['user_id'] ? 'selected' : ''; ?>>
                                            <?php echo e($mem['first_name']); ?> (<?php echo e($mem['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Created By</span>
                        <p class="mb-0 fs-7 text-muted mt-1">
                            <?php echo e($ticket['creator_first'] . ' ' . $ticket['creator_last']); ?>
                        </p>
                    </div>
                    <div class="col-6">
                        <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Due Date</span>
                        <p class="mb-0 font-weight-semibold text-dark fs-7 mt-1">
                            <?php echo $ticket['due_date'] ? date('M d, Y', strtotime($ticket['due_date'])) : '<span class="text-muted italic font-weight-normal">None</span>'; ?>
                        </p>
                    </div>
                </div>

                <div class="mb-0">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8" style="letter-spacing: 0.5px;">Date Filed</span>
                    <p class="mb-0 fs-7 text-secondary mt-1">
                        <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Ticket File Attachments -->
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-paperclip text-primary me-2 fs-4"></i> Attachments
            </div>
            
            <div class="card-body px-4 py-3">
                <!-- Attachments list -->
                <?php if (empty($attachments)): ?>
                    <p class="text-muted italic text-center py-2 mb-3 fs-7">No files uploaded yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php foreach ($attachments as $att): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light-subtle">
                                <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 80%;">
                                    <i class="ti ti-file fs-4 text-secondary"></i>
                                    <div class="text-truncate">
                                        <a href="<?php echo $att['file_path']; ?>" target="_blank" class="text-decoration-none text-dark font-weight-medium fs-7 text-truncate d-block" title="<?php echo e($att['file_name']); ?>">
                                            <?php echo e($att['file_name']); ?>
                                        </a>
                                        <small class="text-muted fs-8">
                                            <?php 
                                                $size = $att['file_size'];
                                                if ($size >= 1048576) {
                                                    echo round($size / 1048576, 2) . ' MB';
                                                } elseif ($size >= 1024) {
                                                    echo round($size / 1024, 1) . ' KB';
                                                } else {
                                                    echo $size . ' B';
                                                }
                                                echo ' • by ' . $att['first_name'];
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                <?php if (($_SESSION['user_role'] ?? '') === 'admin' || 
                                          (int)$att['user_id'] === (int)$_SESSION['user_id']): ?>
                                    <a href="?page=tickets-delete-attachment&id=<?php echo $att['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger p-1 fs-8 border-0" 
                                       title="Delete File"
                                       onclick="return confirm('Are you sure you want to delete file: <?php echo e($att['file_name']); ?>?');">
                                        <i class="ti ti-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- File Upload Form -->
                <form action="?page=tickets-attachment" method="POST" enctype="multipart/form-data" class="border-top pt-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label font-weight-semibold text-dark fs-7">Attach a File</label>
                        <input type="file" name="attachment" class="form-control form-control-sm" required>
                        <small class="text-muted fs-8 mt-1 d-block">Images, PDFs, zip files. Scripts blocked.</small>
                    </div>
                    
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100 py-1.5 font-weight-semibold">
                        <i class="ti ti-upload"></i> Upload Attachment
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    // AJAX functionality to toggle task status dynamically
    $(document).ready(function() {
        $('.task-toggle-checkbox').on('change', function() {
            const checkbox = $(this);
            const taskId = checkbox.data('task-id');
            const isChecked = checkbox.is(':checked');
            const newStatus = isChecked ? 'Completed' : 'Pending';

            // Post request to task-status page via AJAX
            $.ajax({
                url: '?page=tasks-status',
                type: 'POST',
                data: {
                    csrf_token: '<?php echo csrf_token(); ?>',
                    task_id: taskId,
                    status: newStatus
                },
                success: function(response) {
                    // Quick reload of checklist stats or text decoration
                    if (response.success) {
                        const textSpan = checkbox.next('span');
                        if (isChecked) {
                            textSpan.addClass('text-decoration-line-through text-muted').removeClass('font-weight-medium');
                        } else {
                            textSpan.removeClass('text-decoration-line-through text-muted').addClass('font-weight-medium');
                        }
                        // Refresh the status badge text next to the checkbox if we want
                        checkbox.closest('.border').find('.badge').text(newStatus);
                    } else {
                        alert('Error: ' + (response.error || 'Failed to update task.'));
                        checkbox.prop('checked', !isChecked); // Revert
                    }
                },
                error: function() {
                    alert('Server connection error. Failed to update task status.');
                    checkbox.prop('checked', !isChecked); // Revert
                }
            });
        });
    });
</script>
