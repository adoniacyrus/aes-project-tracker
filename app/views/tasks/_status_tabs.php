<?php
$statusFilter = $statusFilter ?? '';
$selectedUserId = $selectedUserId ?? null;
$statusCounts = $statusCounts ?? [];

$statusTabs = [
    '' => [
        'label' => 'All',
        'icon' => 'ti-checkbox',
        'badge' => 'bg-secondary-subtle text-secondary',
        'accent' => 'tab-accent-all',
    ],
    'In Progress' => [
        'label' => 'In Progress',
        'icon' => 'ti-loader',
        'badge' => 'bg-primary-subtle text-primary',
        'accent' => 'tab-accent-processing',
    ],
    'Pending' => [
        'label' => 'Pending',
        'icon' => 'ti-circle',
        'badge' => 'bg-info-subtle text-info',
        'accent' => 'tab-accent-pending',
    ],
    'Completed' => [
        'label' => 'Completed',
        'icon' => 'ti-circle-check',
        'badge' => 'bg-success-subtle text-success',
        'accent' => 'tab-accent-completed',
    ],
    'Blocked' => [
        'label' => 'Blocked',
        'icon' => 'ti-lock',
        'badge' => 'bg-danger-subtle text-danger',
        'accent' => 'tab-accent-blocked',
    ],
];

$tabRouteParams = function ($statusValue) use ($selectedUserId) {
    return [
        'partial' => 1,
        'status' => $statusValue,
        'user_id' => $selectedUserId ?? '',
    ];
};
?>
<div class="project-status-tabs px-3 px-md-4 pt-2 bg-white border-bottom" aria-label="Filter tasks by status">
    <ul class="nav nav-tabs project-status-tablist flex-nowrap overflow-auto mb-0" id="taskStatusTabs" role="tablist">
        <?php foreach ($statusTabs as $value => $meta): ?>
            <?php
                $isActive = $statusFilter === $value;
                $count = (int)($statusCounts[$value] ?? 0);
            ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link project-status-tab d-inline-flex align-items-center gap-2 <?php echo $isActive ? 'active ' . $meta['accent'] : ''; ?> ajax-partial-link"
                   href="<?php echo route('tasks', $tabRouteParams($value)); ?>"
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
