<?php
$workflowHistory = $workflowHistory ?? [];
$latestWorkflowActivity = $latestWorkflowActivity ?? null;
?>
<div id="ticket-workflow-history" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tickets-view', ['id' => $ticket['id'], 'partial' => 'workflow-history'])); ?>">
    <?php if (!empty($latestWorkflowActivity)): ?>
        <div class="alert alert-light border shadow-sm mb-3 ticket-latest-activity" role="status">
            <div class="d-flex align-items-start gap-2">
                <i class="ti ti-activity text-primary mt-1"></i>
                <div>
                    <strong class="fs-7 d-block mb-1">Latest Activity</strong>
                    <p class="mb-1 text-secondary"><?php echo e(format_workflow_latest_activity($latestWorkflowActivity)); ?></p>
                    <small class="text-muted"><?php echo e(workflow_time_ago($latestWorkflowActivity['performed_at'] ?? '')); ?></small>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card ticket-sidebar-card shadow-sm border border-light mb-3">
        <div class="ticket-sidebar-card__head">
            <i class="ti ti-timeline text-primary"></i>
            <span>Workflow History</span>
        </div>
        <div class="ticket-sidebar-card__body">
            <?php if (empty($workflowHistory)): ?>
                <p class="ticket-sidebar-hint mb-0">
                    <i class="ti ti-history me-1"></i>
                    No workflow events recorded yet.
                </p>
            <?php else: ?>
                <div class="ticket-workflow-timeline">
                    <?php foreach ($workflowHistory as $entry): ?>
                        <div class="ticket-workflow-timeline__item">
                            <div class="ticket-workflow-timeline__marker" aria-hidden="true"></div>
                            <div class="ticket-workflow-timeline__content">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-1">
                                    <strong class="fs-7"><?php echo e($entry['label'] ?? 'Event'); ?></strong>
                                    <small class="text-muted">
                                        <?php echo !empty($entry['performed_at']) ? date('M d, Y g:i A', strtotime($entry['performed_at'])) : ''; ?>
                                    </small>
                                </div>
                                <?php if (!empty($entry['performer_name'])): ?>
                                    <small class="text-secondary d-block mb-1"><?php echo e($entry['performer_name']); ?></small>
                                <?php endif; ?>
                                <?php if (!empty($entry['comment'])): ?>
                                    <p class="mb-0 text-secondary fs-7" style="white-space: pre-line;"><?php echo e($entry['comment']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
