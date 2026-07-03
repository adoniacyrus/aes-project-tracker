<?php
$statusFilter = $statusFilter ?? '';
$search = $search ?? '';
$archiveFilter = $archiveFilter ?? 0;
$statusCounts = $statusCounts ?? [];

$statusTabs = [
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
?>
<div class="project-status-tabs px-3 px-md-4 pt-2 bg-white border-bottom" aria-label="Filter projects by status">
    <ul class="nav nav-tabs project-status-tablist flex-nowrap overflow-auto mb-0" id="projectStatusTabs" role="tablist">
        <?php foreach ($statusTabs as $value => $meta): ?>
            <?php
                $isActive = $statusFilter === $value;
                $count = (int)($statusCounts[$value] ?? 0);
            ?>
            <li class="nav-item" role="presentation">
                <a class="nav-link project-status-tab d-inline-flex align-items-center gap-2 <?php echo $isActive ? 'active ' . $meta['accent'] : ''; ?> ajax-partial-link"
                   href="<?php echo route('projects', ['partial' => 1, 'q' => $search, 'status' => $value, 'archived' => $archiveFilter, 'p' => 1]); ?>"
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
