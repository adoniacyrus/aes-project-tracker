<?php
$userRole = $userRole ?? ($_SESSION['user_role'] ?? '');
?>
<div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
    <div class="ticket-sidebar-card__head">
        <i class="ti ti-ticket text-primary"></i>
        <span>Ticket Information</span>
    </div>
    <div class="ticket-sidebar-card__body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <span class="badge bg-light border text-dark font-weight-semibold px-2.5 py-1.5 fs-7 rounded-pill">
                <?php echo e($ticket['category']); ?>
            </span>
            <span class="badge bg-primary-subtle text-primary text-capitalize ticket-priority-badge"><?php echo e($ticket['priority']); ?></span>
        </div>
        <p class="text-secondary leading-relaxed fs-6 mb-3" style="white-space: pre-line;"><?php echo e($ticket['description']); ?></p>
        <dl class="ticket-meta-grid mb-0">
            <div class="ticket-meta-grid__item ticket-meta-grid__item--full">
                <dt>Project</dt>
                <dd><?php echo e($ticket['project_name']); ?> <span class="text-muted">(<?php echo e($ticket['project_code']); ?>)</span></dd>
            </div>
            <div class="ticket-meta-grid__item">
                <dt>Filed</dt>
                <dd><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></dd>
            </div>
            <div class="ticket-meta-grid__item">
                <dt>Created by</dt>
                <dd><?php echo e($ticket['creator_name']); ?></dd>
            </div>
        </dl>
    </div>
</div>
