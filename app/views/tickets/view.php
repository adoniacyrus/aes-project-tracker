<?php
$userRole = $_SESSION['user_role'] ?? '';
$showAssignee = ($userRole !== 'client');
$canDiscuss = TicketWorkflowService::canViewDiscussion($userRole);
$isAdmin = ($userRole === 'admin');
?>

<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4 d-flex flex-column flex-md-row justify-content-between align-md-items-center gap-3">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-1 p-0 bg-transparent">
                            <li class="breadcrumb-item"><a href="<?php echo route('tickets'); ?>" class="text-decoration-none">Tickets</a></li>
                            <li class="breadcrumb-item active text-dark" aria-current="page">Ticket #<?php echo $ticket['id']; ?></li>
                        </ol>
                    </nav>
                    <h3 class="mb-0 font-weight-bold d-flex align-items-center gap-2">
                        <span class="badge bg-secondary-subtle text-secondary font-monospace fs-6">#<?php echo $ticket['id']; ?></span>
                        <?php echo e($ticket['title']); ?>
                    </h3>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo route('tickets'); ?>" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="ti ti-arrow-left"></i> Back
                    </a>
                    <?php if ($isAdmin || (int)$ticket['created_by'] === (int)$_SESSION['user_id'] || (int)$ticket['assigned_to'] === (int)$_SESSION['user_id']): ?>
                        <button type="button" class="btn btn-primary d-flex align-items-center gap-2"
                                data-bs-toggle="modal"
                                data-bs-target="#ticketEditModal"
                                data-id="<?php echo $ticket['id']; ?>"
                                onclick="openTicketEditModal(this)">
                            <i class="ti ti-edit"></i> Edit Ticket
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">

        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-semibold">
                    <i class="ti ti-notes text-primary fs-4"></i> Description
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold px-2.5 py-1.5 fs-7 rounded-pill">
                    <?php echo e($ticket['category']); ?>
                </span>
            </div>
            <div class="card-body px-4 py-3">
                <p class="text-secondary leading-relaxed fs-6 mb-0" style="white-space: pre-line;"><?php echo e($ticket['description']); ?></p>
            </div>
        </div>

        <!-- Workflow Transitions -->
        <div class="card mb-4 shadow-sm border border-light bg-light-subtle">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-git-fork me-2 text-primary fs-4"></i> Workflow Transitions
            </div>
            <div class="card-body px-4 py-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <span class="text-secondary fs-8 text-uppercase font-weight-bold d-block">Current Status</span>
                        <?php
                            $statusClass = 'bg-secondary';
                            if ($ticket['status'] === 'Open') $statusClass = 'bg-info text-white';
                            if ($ticket['status'] === 'Awaiting Admin Approval') $statusClass = 'bg-warning text-dark';
                            if ($ticket['status'] === 'Awaiting Client Review') $statusClass = 'bg-info text-white';
                            if ($ticket['status'] === 'Awaiting Payment') $statusClass = 'bg-secondary-subtle text-dark border';
                            if ($ticket['status'] === 'Payment Confirmed') $statusClass = 'bg-success-subtle text-success border';
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
                        <?php if (empty($allowedTransitions)): ?>
                            <span class="text-muted fs-7 italic">No transitions available.</span>
                        <?php else: ?>
                            <?php foreach ($allowedTransitions as $targetStatus => $label): ?>
                                <?php if ($targetStatus === '__request_clarification__'): ?>
                                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#clarificationModal">
                                        <i class="ti ti-help"></i> <?php echo e($label); ?>
                                    </button>
                                <?php elseif ($targetStatus === '__commercial_review__'): ?>
                                    <form action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>" method="POST" class="d-inline ajax-form" data-confirm="Flag this ticket for commercial review? It will be hidden from the project team.">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="status" value="__commercial_review__">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="ti ti-flag"></i> <?php echo e($label); ?></button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>" method="POST" class="d-inline ajax-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo e($targetStatus); ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">
                                            <i class="ti ti-arrow-right"></i> <?php echo e($label); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isAdmin && $isCommercial && $ticket['status'] === 'Awaiting Admin Approval'): ?>
                <div class="border-top pt-3 mt-2">
                    <h6 class="font-weight-semibold mb-2"><i class="ti ti-file-invoice"></i> Commercial Proposal</h6>
                    <form action="<?php echo route('tickets-proposal', ['id' => $ticket['id']]); ?>" method="POST" class="row g-2 align-items-end ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="col-md-4">
                            <label class="form-label fs-8">Estimated Cost (Rs.)</label>
                            <input type="number" step="0.01" min="0.01" name="estimated_cost" class="form-control form-control-sm" value="<?php echo e($ticket['estimated_cost'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-8">Estimated Delivery Date</label>
                            <input type="date" name="estimated_delivery_date" class="form-control form-control-sm" value="<?php echo $ticket['estimated_delivery_date'] ?? ''; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-success btn-sm w-100"><i class="ti ti-send"></i> Send Proposal to Client</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && $ticket['status'] === 'Awaiting Payment'): ?>
                <div class="border-top pt-3 mt-2">
                    <form action="<?php echo route('tickets-payment', ['id' => $ticket['id']]); ?>" method="POST" class="d-inline ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <button type="submit" class="btn btn-success btn-sm"><i class="ti ti-cash"></i> Confirm Payment Received</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && $ticket['status'] === 'Payment Confirmed'): ?>
                <div class="border-top pt-3 mt-2">
                    <form action="<?php echo route('tickets-assign-team', ['id' => $ticket['id']]); ?>" method="POST" class="row g-2 align-items-end ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="col-md-8">
                            <label class="form-label fs-8">Assign Developer & Start Development</label>
                            <select name="assigned_to" class="form-select form-select-sm" required>
                                <option value="">-- Select Developer --</option>
                                <?php foreach ($projectMembers as $mem): ?>
                                    <option value="<?php echo $mem['user_id']; ?>"><?php echo e($mem['first_name'] . ' ' . $mem['last_name']); ?> (<?php echo e($mem['role']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Assign & Start</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && $ticket['category'] === 'Bug Fix' && in_array($ticket['status'], ['Awaiting Admin Approval', 'Open'], true)): ?>
                <div class="border-top pt-3 mt-2">
                    <form action="<?php echo route('tickets-assign-developer', ['id' => $ticket['id']]); ?>" method="POST" class="row g-2 align-items-end ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="col-md-8">
                            <label class="form-label fs-8">Assign Developer (Bug Fix)</label>
                            <select name="assigned_to" class="form-select form-select-sm" required>
                                <option value="">-- Select Developer --</option>
                                <?php foreach ($projectMembers as $mem): ?>
                                    <option value="<?php echo $mem['user_id']; ?>" <?php echo (int)$ticket['assigned_to'] === (int)$mem['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo e($mem['first_name'] . ' ' . $mem['last_name']); ?> (<?php echo e($mem['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-sm w-100">Assign Developer</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && (int)($ticket['commercial_review_requested'] ?? 0) === 1): ?>
                <div class="border-top pt-3 mt-2">
                    <h6 class="font-weight-semibold text-danger mb-2"><i class="ti ti-alert-triangle"></i> Commercial Review Requested — Reclassify Ticket</h6>
                    <form action="<?php echo route('tickets-reclassify', ['id' => $ticket['id']]); ?>" method="POST" class="row g-2 align-items-end ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="col-md-8">
                            <select name="category" class="form-select form-select-sm" required>
                                <option value="New Feature Request">New Feature Request</option>
                                <option value="Enhancement Request">Enhancement Request</option>
                                <option value="Technical Support">Technical Support</option>
                                <option value="Bug Fix">Bug Fix (keep as bug)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-warning btn-sm w-100">Reclassify & Resume</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isCommercial && !empty($ticket['estimated_cost'])): ?>
                <div class="border-top pt-3 mt-2">
                    <small class="text-muted d-block">Proposal: <strong>Rs. <?php echo number_format((float)$ticket['estimated_cost'], 2); ?></strong>
                    <?php if (!empty($ticket['estimated_delivery_date'])): ?>
                        · Delivery: <strong><?php echo date('M d, Y', strtotime($ticket['estimated_delivery_date'])); ?></strong>
                    <?php endif; ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canDiscuss): ?>
        <div class="card mb-4 shadow-sm border border-primary-subtle">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-lock me-2 text-primary fs-4"></i> Client ↔ Admin Discussion
                <small class="text-muted fs-8 ms-2">(Not visible to developers or interns)</small>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($discussions)): ?>
                    <p class="text-muted italic text-center py-3 mb-0 fs-7">No discussion messages yet. Use this thread for proposals, clarifications, and scope negotiation.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-3">
                        <?php foreach ($discussions as $msg): ?>
                            <div class="p-3 rounded border bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="font-weight-semibold fs-7">
                                        <?php echo e($msg['first_name'] . ' ' . $msg['last_name']); ?>
                                        <span class="badge badge-role badge-<?php echo $msg['role']; ?> ms-1 text-uppercase fs-9"><?php echo e($msg['role']); ?></span>
                                    </span>
                                    <small class="text-secondary fs-8"><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></small>
                                </div>
                                <p class="text-secondary mb-0 fs-7" style="white-space: pre-line;"><?php echo e($msg['message']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo route('tickets-discussion', ['id' => $ticket['id']]); ?>" method="POST" class="border-top pt-3 ajax-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="mb-2">
                        <textarea name="message" rows="3" class="form-control" placeholder="Proposal discussion, clarification, scope negotiation, delivery timeline..." required></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-3">Post Message</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($userRole !== 'client'): ?>
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="d-flex align-items-center gap-2 font-weight-semibold">
                    <i class="ti ti-checkbox text-primary fs-4"></i> Tasks Checklist
                </span>
                <span class="badge bg-light border text-dark font-weight-semibold rounded px-2">
                    <?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'Completed')) . '/' . count($tasks); ?> Completed
                </span>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($tasks)): ?>
                    <p class="text-muted italic text-center py-2 mb-0 fs-7">No checklist tasks defined.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <?php foreach ($tasks as $task): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light-subtle">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" class="form-check-input task-toggle-checkbox" data-task-id="<?php echo $task['id']; ?>" <?php echo $task['status'] === 'Completed' ? 'checked' : ''; ?>>
                                    <span class="fs-7 <?php echo $task['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : 'font-weight-medium'; ?>"><?php echo e($task['task_name']); ?></span>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary fs-8"><?php echo e($task['status']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo route('tasks-create'); ?>" method="POST" class="mt-3 border-top pt-3 ajax-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="input-group">
                        <input type="text" name="task_name" class="form-control" placeholder="Add a checklist task..." required>
                        <select name="assigned_member" class="form-select" style="max-width: 180px;">
                            <option value="">Assign Member</option>
                            <?php foreach ($projectMembers as $mem): ?>
                                <option value="<?php echo $mem['user_id']; ?>"><?php echo e($mem['first_name']); ?> (<?php echo e($mem['role']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary px-3">Add</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Team Discussion (Comments) -->
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-messages me-2 text-primary fs-4"></i> Team Discussion
                <?php if ($userRole === 'client'): ?><small class="text-muted fs-8">(Visible to project team)</small><?php endif; ?>
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($comments)): ?>
                    <p class="text-muted italic text-center py-4 mb-0 fs-7">No comments yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($comments as $comment): ?>
                            <?php $isSystem = str_starts_with($comment['comment'], 'System Action:') || str_starts_with($comment['comment'], '['); ?>
                            <div class="d-flex align-items-start gap-2.5 p-3 rounded <?php echo $isSystem ? 'bg-light border' : 'bg-white border'; ?>">
                                <div class="avatar <?php echo $isSystem ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary'; ?> rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 36px; height: 36px; font-size: 12px;">
                                    <?php echo $isSystem ? 'SYS' : strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-fill">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="font-weight-semibold fs-7">
                                            <?php echo $isSystem ? 'System' : e($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                            <?php if (!$isSystem): ?><span class="badge badge-role badge-<?php echo $comment['role']; ?> ms-1 fs-9"><?php echo e($comment['role']); ?></span><?php endif; ?>
                                        </span>
                                        <small class="text-secondary fs-8"><?php echo date('M d, H:i', strtotime($comment['created_at'])); ?></small>
                                    </div>
                                    <p class="text-secondary mb-0 fs-7" style="white-space: pre-line;"><?php echo e($comment['comment']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo route('tickets-comment', ['id' => $ticket['id']]); ?>" method="POST" class="border-top pt-3 ajax-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <textarea name="comment" rows="3" class="form-control mb-2" placeholder="Progress updates, questions for the team..." required></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4">Post Comment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-12 col-lg-4">
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-info-circle text-primary me-2 fs-4"></i> Properties
            </div>
            <div class="card-body px-4 py-3">
                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8">Project</span>
                    <p class="mb-0 font-weight-semibold fs-6 mt-1"><?php echo e($ticket['project_name']); ?> (<?php echo e($ticket['project_code']); ?>)</p>
                </div>
                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8">Priority</span>
                    <div class="mt-1">
                        <span class="badge bg-primary-subtle text-primary text-capitalize px-2 py-1 fs-7"><?php echo e($ticket['priority']); ?></span>
                    </div>
                </div>
                <?php if ($showAssignee): ?>
                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8">Assigned To</span>
                    <p class="mb-0 fs-7 mt-1">
                        <?php if ($ticket['assignee_first']): ?>
                            <?php echo e($ticket['assignee_first'] . ' ' . $ticket['assignee_last']); ?>
                        <?php else: ?>
                            <span class="text-muted italic">Unassigned</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8">Created By</span>
                    <p class="mb-0 fs-7 text-muted mt-1"><?php echo e($ticket['creator_first'] . ' ' . $ticket['creator_last']); ?></p>
                </div>
                <div class="mb-0">
                    <span class="text-secondary text-uppercase font-weight-bold fs-8">Date Filed</span>
                    <p class="mb-0 fs-7 text-secondary mt-1"><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-paperclip text-primary me-2 fs-4"></i> Attachments
            </div>
            <div class="card-body px-4 py-3">
                <?php if (empty($attachments)): ?>
                    <p class="text-muted italic text-center py-2 mb-3 fs-7">No files uploaded yet.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3 mb-3">
                        <?php foreach ($attachments as $att): ?>
                            <?php $isImage = is_image_attachment($att['file_name'], $att['mime_type'] ?? null); ?>
                            <div class="border rounded p-2 bg-light-subtle">
                                <?php if ($isImage): ?>
                                    <a href="<?php echo e($att['file_path']); ?>" target="_blank">
                                        <img src="<?php echo e($att['file_path']); ?>" alt="<?php echo e($att['file_name']); ?>" class="img-fluid rounded mb-2" style="max-height: 120px; object-fit: cover;">
                                    </a>
                                <?php endif; ?>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="text-truncate">
                                        <a href="<?php echo e($att['file_path']); ?>" target="_blank" class="text-decoration-none fs-7 font-weight-medium"><?php echo e($att['file_name']); ?></a>
                                        <small class="text-muted d-block fs-8"><?php echo format_file_size($att['file_size']); ?></small>
                                    </div>
                                    <?php if ($isAdmin || (int)$att['user_id'] === (int)$_SESSION['user_id']): ?>
                                        <a href="<?php echo route('tickets-delete-attachment', ['id' => $ticket['id'], 'attachment_id' => $att['id']]); ?>" class="btn btn-sm btn-outline-danger border-0 ajax-link" data-confirm="Delete this file?"><i class="ti ti-trash"></i></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="<?php echo route('tickets-attachment', ['id' => $ticket['id']]); ?>" method="POST" enctype="multipart/form-data" class="border-top pt-3 ajax-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <input type="file" name="attachment" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" required>
                    <button type="submit" class="btn btn-outline-primary btn-sm w-100"><i class="ti ti-upload"></i> Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clarificationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>" method="POST" class="modal-content ajax-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
            <input type="hidden" name="status" value="__request_clarification__">
            <div class="modal-header">
                <h5 class="modal-title">Request Clarification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea name="clarification_note" rows="4" class="form-control" placeholder="Describe what clarification is needed..." required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">Send Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Ticket Modal -->
<div class="modal fade" id="ticketEditModal" tabindex="-1" aria-labelledby="ticketEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="ticketEditForm" method="POST" class="modal-content ajax-form">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketEditModalLabel"><i class="ti ti-edit me-2"></i> Edit Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6 col-12">
                        <label class="form-label required font-weight-semibold">Associated Project</label>
                        <select name="project_id" id="editProjectSelect" class="form-select" required>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label required font-weight-semibold">Category</label>
                        <select name="category" id="editCategorySelect" class="form-select" required <?php echo $userRole !== 'admin' ? 'disabled' : ''; ?>>
                            <option value="Bug Fix">Bug Fix</option>
                            <option value="New Feature Request">New Feature Request</option>
                            <option value="Enhancement Request">Enhancement Request</option>
                            <option value="Technical Support">Technical Support</option>
                        </select>
                        <?php if ($userRole !== 'admin'): ?>
                            <input type="hidden" name="category" id="editCategoryHidden">
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label required font-weight-semibold">Ticket Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label required font-weight-semibold">Description</label>
                        <textarea name="description" id="editDescription" rows="6" class="form-control" required></textarea>
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold">Assign To</label>
                        <select name="assigned_to" id="editAssigneeSelect" class="form-select">
                            <option value="">-- Unassigned --</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-<?php echo $isAdmin ? '3' : '6'; ?> col-6">
                        <label class="form-label font-weight-semibold">Priority</label>
                        <select name="priority" id="editPriority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="col-md-<?php echo $isAdmin ? '3' : '6'; ?> col-6">
                        <label class="form-label font-weight-semibold">Due Date</label>
                        <input type="date" name="due_date" id="editDueDate" class="form-control">
                    </div>
                    <?php if ($isAdmin): ?>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold">Status (Admin Override)</label>
                        <select name="status" id="editStatus" class="form-select">
                            <?php foreach (TicketWorkflowService::getAllStatuses() as $st): ?>
                                <option value="<?php echo $st; ?>"><?php echo $st; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" name="status" id="editStatusHidden">
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
let editTicketProjectMembers = {};
let editTicketInitialAssignee = null;

function populateEditTicketAssignees(projectId) {
    const select = document.getElementById('editAssigneeSelect');
    if (!select) return;
    select.innerHTML = '<option value="">-- Unassigned --</option>';
    if (projectId && editTicketProjectMembers[projectId]) {
        editTicketProjectMembers[projectId]
            .filter(m => ['developer', 'intern', 'admin'].includes(m.role))
            .forEach(member => {
                const opt = document.createElement('option');
                opt.value = member.user_id;
                opt.textContent = `${member.first_name} ${member.last_name} (${member.role})`;
                if (editTicketInitialAssignee && parseInt(member.user_id) === parseInt(editTicketInitialAssignee)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
    }
}

function openTicketEditModal(button) {
    const id = button.dataset.id;
    const form = document.getElementById('ticketEditForm');
    
    showLoader();
    $.ajax({
        url: '<?php echo route("tickets-edit", ["id" => "__ID__"]); ?>'.replace('__ID__', id),
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            hideLoader();
            if (response && response.success) {
                const ticket = response.ticket;
                editTicketProjectMembers = response.projectMembers || {};
                editTicketInitialAssignee = ticket.assigned_to;

                form.action = '<?php echo route("tickets-edit", ["id" => "__ID__"]); ?>'.replace('__ID__', ticket.id);
                
                // Populate projects select
                const projectSelect = document.getElementById('editProjectSelect');
                projectSelect.innerHTML = '';
                response.projects.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = `${p.project_name} (${p.project_code})`;
                    if (parseInt(p.id) === parseInt(ticket.project_id)) opt.selected = true;
                    projectSelect.appendChild(opt);
                });

                // Populate category
                const catSelect = document.getElementById('editCategorySelect');
                if (catSelect) {
                    catSelect.value = ticket.category || 'Bug Fix';
                }
                const catHidden = document.getElementById('editCategoryHidden');
                if (catHidden) {
                    catHidden.value = ticket.category || 'Bug Fix';
                }

                // Populate title, description, priority, due_date
                document.getElementById('editTitle').value = ticket.title || '';
                document.getElementById('editDescription').value = ticket.description || '';
                document.getElementById('editPriority').value = ticket.priority || 'medium';
                document.getElementById('editDueDate').value = ticket.due_date ? ticket.due_date.substring(0, 10) : '';

                // Populate status
                const statusSelect = document.getElementById('editStatus');
                if (statusSelect) {
                    statusSelect.value = ticket.status || 'Open';
                }
                const statusHidden = document.getElementById('editStatusHidden');
                if (statusHidden) {
                    statusHidden.value = ticket.status || 'Open';
                }

                // Populate assignees
                populateEditTicketAssignees(ticket.project_id);
            } else {
                showToast(response.message || 'Failed to fetch ticket details.', 'danger');
            }
        },
        error: function() {
            hideLoader();
            showToast('Failed to fetch ticket details.', 'danger');
        }
    });
}

$(document).ready(function() {
    const editProjectSelect = document.getElementById('editProjectSelect');
    if (editProjectSelect) {
        editProjectSelect.addEventListener('change', function() {
            populateEditTicketAssignees(this.value);
        });
    }

    $('.task-toggle-checkbox').on('change', function() {
        const checkbox = $(this);
        showLoader();
        $.ajax({
            url: '<?php echo route("tasks-status", ["id" => "__TASK_ID__"]); ?>'.replace('__TASK_ID__', checkbox.data('task-id')),
            type: 'POST',
            data: { 
                csrf_token: '<?php echo csrf_token(); ?>', 
                task_id: checkbox.data('task-id'), 
                status: checkbox.is(':checked') ? 'Completed' : 'Pending' 
            },
            success: function(r) {
                hideLoader();
                if (r && r.success) {
                    showToast(r.message || 'Task status updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    showToast((r && r.message) ? r.message : 'Failed to update task status.', 'danger');
                }
            },
            error: function() {
                hideLoader();
                checkbox.prop('checked', !checkbox.is(':checked'));
                showToast('Server error while updating task status.', 'danger');
            }
        });
    });
});
</script>
