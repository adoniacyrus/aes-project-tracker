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
<!-- Workflow -->
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-git-fork text-primary"></i>
        <span>Workflow</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <div class="ticket-status-row">
            <span class="ticket-meta-label">Current status</span>
            <span id="ticket-status-badge" class="badge <?php echo $statusClass; ?> ticket-status-badge"><?php echo e($ticket['status']); ?></span>
        </div>

        <?php if (empty($allowedTransitions)): ?>
            <p class="ticket-sidebar-hint mb-0">No transitions available.</p>
        <?php else: ?>
            <form id="ticketWorkflowForm"
                  action="<?php echo route('tickets-workflow', ['id' => $ticket['id']]); ?>"
                  method="POST"
                  class="ajax-form ticket-workflow-form ticket-workflow-form--compact">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <label for="ticketWorkflowStatus" class="ticket-meta-label">Change status</label>
                <div class="ticket-workflow-actions">
                    <select name="status" id="ticketWorkflowStatus" class="form-select form-select-sm" required>
                        <option value="">Select next status…</option>
                        <?php foreach ($allowedTransitions as $targetStatus => $label): ?>
                            <option value="<?php echo e($targetStatus); ?>"
                                <?php if ($targetStatus === '__commercial_review__'): ?>
                                    data-confirm="Flag this ticket for commercial review? It will be hidden from the project team."
                                <?php endif; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm ticket-workflow-submit" title="Update status" aria-label="Update status">
                        <i class="ti ti-check"></i>
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($isAdmin && $isCommercial && $ticket['status'] === 'Awaiting Admin Approval'): ?>
        <div class="ticket-sidebar-divider"></div>
        <div class="ticket-sidebar-subsection">
            <p class="ticket-sidebar-subtitle mb-2"><i class="ti ti-file-invoice"></i> Commercial proposal</p>
            <form action="<?php echo route('tickets-proposal', ['id' => $ticket['id']]); ?>" method="POST" class="ajax-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="ticket-meta-label">Cost (Rs.)</label>
                        <input type="number" step="0.01" min="0.01" name="estimated_cost" class="form-control form-control-sm" value="<?php echo e($ticket['estimated_cost'] ?? ''); ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="ticket-meta-label">Delivery</label>
                        <input type="date" name="estimated_delivery_date" class="form-control form-control-sm" value="<?php echo $ticket['estimated_delivery_date'] ?? ''; ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-sm w-100"><i class="ti ti-send me-1"></i> Send to client</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($isAdmin && $ticket['status'] === 'Awaiting Payment'): ?>
        <div class="ticket-sidebar-divider"></div>
        <form action="<?php echo route('tickets-payment', ['id' => $ticket['id']]); ?>" method="POST" class="ajax-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
            <button type="submit" class="btn btn-success btn-sm w-100"><i class="ti ti-cash me-1"></i> Confirm payment</button>
        </form>
        <?php endif; ?>

        <?php if ($isAdmin && $ticket['status'] === 'Payment Confirmed'): ?>
        <div class="ticket-sidebar-note">
            <i class="ti ti-info-circle"></i>
            <span>Visible to the project team. Transition to start development and assign tasks.</span>
        </div>
        <?php endif; ?>

        <?php if ($isAdmin && (int)($ticket['commercial_review_requested'] ?? 0) === 1): ?>
        <div class="ticket-sidebar-divider"></div>
        <div class="ticket-sidebar-subsection">
            <p class="ticket-sidebar-subtitle text-danger mb-2"><i class="ti ti-alert-triangle"></i> Reclassify ticket</p>
            <form action="<?php echo route('tickets-reclassify', ['id' => $ticket['id']]); ?>" method="POST" class="ajax-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                <select name="category" class="form-select form-select-sm mb-2" required>
                    <option value="New Feature Request">New Feature Request</option>
                    <option value="Enhancement Request">Enhancement Request</option>
                    <option value="Technical Support">Technical Support</option>
                    <option value="Bug Fix">Bug Fix (keep as bug)</option>
                </select>
                <button type="submit" class="btn btn-warning btn-sm w-100">Reclassify &amp; resume</button>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($isCommercial && !empty($ticket['estimated_cost'])): ?>
        <div class="ticket-proposal-strip">
            <span><i class="ti ti-receipt"></i> <?php echo format_rs_currency((float)$ticket['estimated_cost'], 2); ?></span>
            <?php if (!empty($ticket['estimated_delivery_date'])): ?>
                <span class="ticket-proposal-strip__sep">·</span>
                <span><i class="ti ti-calendar"></i> <?php echo date('M d, Y', strtotime($ticket['estimated_delivery_date'])); ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($userRole !== 'client'): ?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-users text-primary"></i>
        <span>Team visibility</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <?php if (is_ticket_visible_to_project_team($ticket)): ?>
            <div class="ticket-team-list">
                <?php foreach ($projectMembers as $mem): ?>
                    <?php if (in_array($mem['role'], ['admin', 'developer', 'intern'], true)): ?>
                        <div class="ticket-team-member">
                            <span class="ticket-team-member__avatar"><?php echo e(user_initials($mem['full_name'])); ?></span>
                            <span class="ticket-team-member__info">
                                <span class="ticket-team-member__name"><?php echo e($mem['full_name']); ?></span>
                                <span class="ticket-team-member__role"><?php echo e(ucfirst($mem['role'])); ?></span>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="ticket-sidebar-hint mb-0">
                <i class="ti ti-eye-off me-1"></i>
                Hidden from developers and interns until approval or payment is confirmed.
            </p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
