<?php
$status = $status ?? '';
$search = $search ?? '';
$projectId = $projectId ?? 0;
$category = $category ?? '';
$priority = $priority ?? '';
$statusCounts = $statusCounts ?? [];

$statusTabs = [
    '' => [
        'label' => 'All',
        'icon' => 'ti-ticket',
        'badge' => 'bg-secondary-subtle text-secondary',
        'accent' => 'tab-accent-all',
    ],
    'Initiated' => [
        'label' => 'Initiated',
        'icon' => 'ti-player-play',
        'badge' => 'bg-warning-subtle text-warning-emphasis',
        'accent' => 'tab-accent-initiated',
    ],
    'Processing' => [
        'label' => 'Processing',
        'icon' => 'ti-loader',
        'badge' => 'bg-primary-subtle text-primary',
        'accent' => 'tab-accent-processing',
    ],
    'Completed' => [
        'label' => 'Completed',
        'icon' => 'ti-circle-check',
        'badge' => 'bg-success-subtle text-success',
        'accent' => 'tab-accent-completed',
    ],
];

$tabRouteParams = function ($statusValue) use ($search, $projectId, $category, $priority) {
    return [
        'partial' => 1,
        'q' => $search,
        'project_id' => $projectId,
        'category' => $category,
        'priority' => $priority,
        'status' => $statusValue,
        'p' => 1,
    ];
};
?>
<div class="project-status-tabs px-3 px-md-4 pt-2 bg-white border-bottom" aria-label="Filter tickets by status">
    <ul class="nav nav-tabs project-status-tablist flex-nowrap overflow-auto mb-0" id="ticketStatusTabs" role="tablist">
        <?php foreach ($statusTabs as $value => $meta): ?>
            <?php
                $isActive = $status === $value;
                $count = (int)($statusCounts[$value] ?? 0);
            ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link project-status-tab d-inline-flex align-items-center gap-2 <?php echo $isActive ? 'active ' . $meta['accent'] : ''; ?> ajax-partial-link"
                   href="<?php echo route('tickets', $tabRouteParams($value)); ?>"
                   role="tab"
                   data-status="<?php echo e($value); ?>"
                   aria-selected="<?php echo $isActive ? 'true' : 'false'; ?>"
                   <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                    <i class="ti <?php echo e($meta['icon']); ?> fs-6 flex-shrink-0"></i>
                    <span class="project-status-tab-label"><?php echo e($meta['label']); ?></span>
                    <span class="project-status-tab-count badge rounded-pill <?php echo $meta['badge']; ?>"><?php echo $count; ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
