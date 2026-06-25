<?php
$isAdmin = $isAdmin ?? (($userRole ?? '') === 'admin');
if (!$isAdmin) {
    return;
}
?>
<div id="ticket-developer-assignment" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'assignment'])); ?>">
    <div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
        <div class="ticket-sidebar-card__head">
            <i class="ti ti-users-group text-primary"></i>
            <span>Developer Assignment</span>
        </div>
        <div class="ticket-sidebar-card__body">
            <?php require __DIR__ . '/_developer_assignment_form.php'; ?>
        </div>
    </div>
</div>
