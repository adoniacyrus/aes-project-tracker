<?php
$dashboardEwlLogs = $dashboardEwlLogs ?? [];
?>
<?php if (empty($dashboardEwlLogs)): ?>
    <div class="p-4 text-center text-muted">
        <i class="ti ti-notebook-off fs-2 mb-2 text-secondary"></i>
        <p class="mb-0">No external work logs assigned to you.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover table-vcenter card-table mb-0 fs-7">
            <thead>
                <tr class="bg-light">
                    <th class="px-3">Date</th>
                    <th>Title</th>
                    <th>Project</th>
                    <th>Status</th>
                    <th class="text-end px-3">Hours</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dashboardEwlLogs as $log): ?>
                    <tr>
                        <td class="px-3 text-nowrap"><?php echo !empty($log['work_date']) ? date('M d, Y', strtotime($log['work_date'])) : '—'; ?></td>
                        <td class="font-weight-medium"><?php echo e($log['title']); ?></td>
                        <td>
                            <a href="<?php echo e(route('projects-view', ['project_code' => $log['project_code']])); ?>" class="text-decoration-none">
                                <?php echo e($log['project_code']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge <?php echo external_work_log_status_badge_class($log['status']); ?>">
                                <?php echo e($log['status']); ?>
                            </span>
                        </td>
                        <td class="text-end px-3"><?php echo e(format_work_hours(external_work_log_hours_spent($log))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
