<?php
$userRole = $userRole ?? ($_SESSION['user_role'] ?? '');
$userId = (int)($userId ?? ($_SESSION['user_id'] ?? 0));
$canCreate = $canCreate ?? can_create_external_work_log($userRole);
$canManage = $canManage ?? can_manage_external_work_logs($userRole);
$ewlRefreshTarget = $ewlRefreshTarget ?? '#external-work-logs-ajax-content';
$showProject = $showProject ?? true;
$compact = $compact ?? false;
?>
<div class="table-responsive">
    <table class="table table-hover table-vcenter card-table mb-0 align-middle">
        <thead>
            <tr class="bg-light">
                <th class="ps-3">Date</th>
                <?php if ($showProject): ?>
                    <th>Project</th>
                <?php endif; ?>
                <th>Title</th>
                <th>Source</th>
                <th>Requested By</th>
                <th>Assigned To</th>
                <th>Hours</th>
                <th>Status</th>
                <th class="pe-3 text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="<?php echo $showProject ? 9 : 8; ?>" class="text-center text-muted py-5">
                        <i class="ti ti-notebook-off fs-1 d-block mb-2 text-secondary"></i>
                        No external work logs found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <?php $canUpdateStatus = can_update_external_work_log_status($userRole, $log, $userId); ?>
                    <tr>
                        <td class="ps-3 text-nowrap">
                            <?php echo !empty($log['work_date']) ? date('M d, Y', strtotime($log['work_date'])) : '—'; ?>
                        </td>
                        <?php if ($showProject): ?>
                            <td>
                                <a href="<?php echo e(route('projects-view', ['project_code' => $log['project_code']])); ?>" class="text-decoration-none font-weight-semibold">
                                    <?php echo e($log['project_code']); ?>
                                </a>
                                <div class="fs-8 text-secondary"><?php echo e($log['project_name']); ?></div>
                            </td>
                        <?php endif; ?>
                        <td>
                            <div class="font-weight-semibold text-dark"><?php echo e($log['title']); ?></div>
                            <?php if (!$compact && !empty($log['requested_by'])): ?>
                                <div class="fs-8 text-secondary d-md-none">From: <?php echo e($log['requested_by']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light border text-dark font-weight-normal"><?php echo e($log['communication_source']); ?></span>
                        </td>
                        <td><?php echo e($log['requested_by'] ?: '—'); ?></td>
                        <td><?php echo e($log['assigned_to_name'] ?? '—'); ?></td>
                        <td class="text-nowrap"><?php echo e(format_work_hours(external_work_log_hours_spent($log))); ?></td>
                        <td>
                            <span class="badge <?php echo external_work_log_status_badge_class($log['status']); ?>">
                                <?php echo e($log['status']); ?>
                            </span>
                        </td>
                        <td class="pe-3 text-end">
                            <div class="d-inline-flex gap-1 justify-content-end flex-wrap">
                                <?php if ($canManage): ?>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-icon btn-sm ewl-edit-btn"
                                            title="Edit log"
                                            data-bs-toggle="modal"
                                            data-bs-target="#ewlEditModal"
                                            data-id="<?php echo (int)$log['id']; ?>">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($canUpdateStatus && $log['status'] === 'Pending'): ?>
                                    <form method="POST"
                                          action="<?php echo e(route('external-work-logs-status', ['id' => $log['id']])); ?>"
                                          class="ajax-form d-inline"
                                          <?php if (!empty($ewlAjaxReload)): ?>
                                          data-ajax-reload="true"
                                          <?php else: ?>
                                          data-ajax-refresh="<?php echo e($ewlRefreshTarget); ?>"
                                          <?php endif; ?>>
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="status" value="In Progress">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Start</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canUpdateStatus && $log['status'] === 'In Progress'): ?>
                                    <button type="button"
                                            class="btn btn-outline-success btn-sm ewl-complete-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#ewlCompleteModal"
                                            data-id="<?php echo (int)$log['id']; ?>"
                                            data-title="<?php echo e($log['title']); ?>">
                                        Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
