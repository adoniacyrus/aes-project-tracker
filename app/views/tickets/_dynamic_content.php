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
                        <span id="ticket-status-badge" class="badge <?php echo $statusClass; ?> px-2.5 py-1.5 fs-6 mt-1 rounded"><?php echo e($ticket['status']); ?></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (empty($allowedTransitions)): ?>
                            <span class="text-muted fs-7 italic">No transitions available.</span>
                        <?php else: ?>
                            <?php foreach ($allowedTransitions as $targetStatus => $label): ?>
                                <?php if ($targetStatus === '__commercial_review__'): ?>
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
                                    <option value="<?php echo $mem['user_id']; ?>"><?php echo e($mem['full_name']); ?> (<?php echo e($mem['role']); ?>)</option>
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
                                        <?php echo e($mem['full_name']); ?> (<?php echo e($mem['role']); ?>)
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
                    <small class="text-muted d-block">Proposal: <strong><?php echo format_rs_currency((float)$ticket['estimated_cost'], 2); ?></strong>
                    <?php if (!empty($ticket['estimated_delivery_date'])): ?>
                        · Delivery: <strong><?php echo date('M d, Y', strtotime($ticket['estimated_delivery_date'])); ?></strong>
                    <?php endif; ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($userRole !== 'client'): ?>
        <?php
            $canManageTasks = can_manage_tasks($userRole);
            $currentUserId = (int)($_SESSION['user_id'] ?? 0);
            $taskAssignableMembers = filter_task_assignable_members($projectMembers);
            require __DIR__ . '/_tasks_checklist.php';
        ?>
        <?php endif; ?>
