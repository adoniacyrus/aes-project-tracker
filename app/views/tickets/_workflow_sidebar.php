        <!-- Workflow Transitions -->
        <div class="card mb-4 shadow-sm border border-light bg-light-subtle">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-git-fork me-2 text-primary fs-4"></i> Workflow Transitions
            </div>
            <div class="card-body px-4 py-3">
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase font-weight-bold d-block mb-1">Current Status</span>
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
                    <span id="ticket-status-badge" class="badge <?php echo $statusClass; ?> px-2.5 py-1.5 fs-6 rounded"><?php echo e($ticket['status']); ?></span>
                </div>

                <?php if (empty($allowedTransitions)): ?>
                    <span class="text-muted fs-7 italic d-block">No transitions available.</span>
                <?php else: ?>
                    <form id="ticketWorkflowForm"
                          action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>"
                          method="POST"
                          class="ajax-form ticket-workflow-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <label for="ticketWorkflowStatus" class="form-label fs-8 text-secondary text-uppercase font-weight-bold mb-1">Change Status</label>
                        <select name="status" id="ticketWorkflowStatus" class="form-select form-select-sm mb-2" required>
                            <option value="">— Select Next Status —</option>
                            <?php foreach ($allowedTransitions as $targetStatus => $label): ?>
                                <option value="<?php echo e($targetStatus); ?>"
                                    <?php if ($targetStatus === '__commercial_review__'): ?>
                                        data-confirm="Flag this ticket for commercial review? It will be hidden from the project team."
                                    <?php endif; ?>>
                                    <?php echo e($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="ti ti-check me-1"></i> Update Status
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($isAdmin && $isCommercial && $ticket['status'] === 'Awaiting Admin Approval'): ?>
                <div class="border-top pt-3 mt-3">
                    <h6 class="font-weight-semibold mb-2"><i class="ti ti-file-invoice"></i> Commercial Proposal</h6>
                    <form action="<?php echo route('tickets-proposal', ['id' => $ticket['id']]); ?>" method="POST" class="ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <div class="mb-2">
                            <label class="form-label fs-8">Estimated Cost (Rs.)</label>
                            <input type="number" step="0.01" min="0.01" name="estimated_cost" class="form-control form-control-sm" value="<?php echo e($ticket['estimated_cost'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fs-8">Estimated Delivery Date</label>
                            <input type="date" name="estimated_delivery_date" class="form-control form-control-sm" value="<?php echo $ticket['estimated_delivery_date'] ?? ''; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="ti ti-send"></i> Send Proposal to Client</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && $ticket['status'] === 'Awaiting Payment'): ?>
                <div class="border-top pt-3 mt-3">
                    <form action="<?php echo route('tickets-payment', ['id' => $ticket['id']]); ?>" method="POST" class="ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <button type="submit" class="btn btn-success btn-sm w-100"><i class="ti ti-cash"></i> Confirm Payment Received</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && $ticket['status'] === 'Payment Confirmed'): ?>
                <div class="border-top pt-3 mt-3">
                    <p class="text-muted fs-8 mb-0">
                        <i class="ti ti-users"></i>
                        Payment confirmed — this ticket is visible to all project team members. Use workflow transitions to start development and assign work via tasks.
                    </p>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin && (int)($ticket['commercial_review_requested'] ?? 0) === 1): ?>
                <div class="border-top pt-3 mt-3">
                    <h6 class="font-weight-semibold text-danger mb-2"><i class="ti ti-alert-triangle"></i> Commercial Review Requested — Reclassify Ticket</h6>
                    <form action="<?php echo route('tickets-reclassify', ['id' => $ticket['id']]); ?>" method="POST" class="ajax-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <select name="category" class="form-select form-select-sm mb-2" required>
                            <option value="New Feature Request">New Feature Request</option>
                            <option value="Enhancement Request">Enhancement Request</option>
                            <option value="Technical Support">Technical Support</option>
                            <option value="Bug Fix">Bug Fix (keep as bug)</option>
                        </select>
                        <button type="submit" class="btn btn-warning btn-sm w-100">Reclassify & Resume</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($isCommercial && !empty($ticket['estimated_cost'])): ?>
                <div class="border-top pt-3 mt-3">
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
        <div class="card mb-4 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-users text-primary me-2 fs-4"></i> Project Team Visibility
            </div>
            <div class="card-body px-4 py-3">
                <?php if (is_ticket_visible_to_project_team($ticket)): ?>
                    <p class="text-secondary fs-8 mb-2">Visible to:</p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($projectMembers as $mem): ?>
                            <?php if (in_array($mem['role'], ['admin', 'developer', 'intern'], true)): ?>
                                <span class="badge bg-light border text-dark px-2 py-1 fs-8">
                                    <?php echo e($mem['full_name']); ?>
                                    <span class="text-muted">(<?php echo e(ucfirst($mem['role'])); ?>)</span>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted fs-8 mb-0">
                        <i class="ti ti-eye-off"></i>
                        Hidden from developers and interns until admin approval or payment is confirmed.
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
