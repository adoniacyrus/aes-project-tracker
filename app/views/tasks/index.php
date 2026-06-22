<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-subtle text-primary rounded" style="width: 48px; height: 48px;">
                        <i class="ti ti-checkbox fs-2"></i>
                    </span>
                    <div>
                        <h4 class="mb-0 font-weight-semibold"><?php echo e($pageTitle); ?></h4>
                        <p class="text-secondary mb-0 fs-7">Manage your checklists across all tickets and projects.</p>
                    </div>
                </div>
                
                <!-- User Filter Dropdown -->
                <form method="GET" action="" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="page" value="tasks">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="ti ti-user"></i></span>
                        <select name="user_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($taskableUsers as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo $selectedUserId === (int)$u['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($u['full_name']); ?> (<?php echo e($u['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Tasks Lists grouped by status -->
    <?php 
    $statusGroups = [
        ['title' => 'In Progress', 'icon' => 'loader', 'color' => 'primary', 'tasks' => $inProgressTasks],
        ['title' => 'Pending', 'icon' => 'circle', 'color' => 'info', 'tasks' => $pendingTasks],
        ['title' => 'Blocked', 'icon' => 'lock', 'color' => 'danger', 'tasks' => $blockedTasks],
        ['title' => 'Completed', 'icon' => 'circle-check', 'color' => 'success', 'tasks' => $completedTasks]
    ];
    
    foreach ($statusGroups as $group):
    ?>
        <div class="col-12 col-md-6">
            <div class="card shadow-sm border border-light h-100">
                <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2 font-weight-bold">
                        <span class="badge bg-<?php echo $group['color']; ?>-subtle text-<?php echo $group['color']; ?> p-1.5 rounded-circle d-flex align-items-center justify-content-center">
                            <i class="ti ti-<?php echo $group['icon']; ?> fs-5"></i>
                        </span>
                        <?php echo $group['title']; ?>
                    </span>
                    <span class="badge bg-light border text-dark font-weight-semibold px-2 py-1 fs-8">
                        <?php echo count($group['tasks']); ?>
                    </span>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($group['tasks'])): ?>
                        <div class="p-4 text-center text-muted">
                            <p class="mb-0 fs-8 italic">No tasks in this state.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($group['tasks'] as $t): ?>
                                <div class="list-group-item p-3 border-bottom d-flex align-items-start justify-content-between gap-2 hover-bg-gray">
                                    <div class="d-flex align-items-start gap-2.5">
                                        <input type="checkbox" class="form-check-input task-toggle-checkbox mt-1 cursor-pointer" 
                                               data-task-id="<?php echo $t['id']; ?>" 
                                               <?php echo $t['status'] === 'Completed' ? 'checked' : ''; ?>
                                               style="width: 18px; height: 18px;">
                                        
                                        <div>
                                            <span class="fs-7 text-dark <?php echo $t['status'] === 'Completed' ? 'text-decoration-line-through text-muted' : 'font-weight-semibold'; ?> d-block">
                                                <?php echo e($t['task_name']); ?>
                                            </span>
                                            <div class="mt-1 fs-8 text-secondary d-flex flex-wrap align-items-center gap-1.5">
                                                <span>Ticket:</span>
                                                <a href="<?php echo route('tickets-view', ['id' => $t['ticket_id'], 'title' => $t['ticket_title']]); ?>" class="text-decoration-none font-weight-medium">
                                                    <?php echo e(substr($t['ticket_title'], 0, 30)); ?><?php echo strlen($t['ticket_title']) > 30 ? '...' : ''; ?>
                                                </a>
                                                <span>•</span>
                                                <span class="badge bg-light border text-dark font-monospace"><?php echo e($t['project_code']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex flex-column align-items-end gap-1.5" style="flex-shrink: 0;">
                                        <a href="<?php echo route('tasks-edit', ['id' => $t['id']]); ?>" class="btn btn-outline-secondary btn-icon" title="Edit Task details">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <?php if ($t['due_date']): ?>
                                            <?php 
                                                $isOverdue = (strtotime($t['due_date']) < time()) && ($t['status'] !== 'Completed');
                                                $dateColor = $isOverdue ? 'text-danger font-weight-bold' : 'text-secondary';
                                            ?>
                                            <span class="fs-9 <?php echo $dateColor; ?>">
                                                <i class="ti ti-calendar-event me-0.5"></i><?php echo date('M d', strtotime($t['due_date'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // AJAX functionality to toggle task status dynamically
    $(document).ready(function() {
        $('.task-toggle-checkbox').on('change', function() {
            const checkbox = $(this);
            const taskId = checkbox.data('task-id');
            const isChecked = checkbox.is(':checked');
            const newStatus = isChecked ? 'Completed' : 'Pending';

            $.ajax({
                url: '<?php echo route("tasks-status", ["id" => "__TASK_ID__"]); ?>'.replace('__TASK_ID__', taskId),
                type: 'POST',
                data: {
                    csrf_token: '<?php echo csrf_token(); ?>',
                    task_id: taskId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Dynamically refresh the page to move the card to the proper column
                        location.reload();
                    } else {
                        alert('Error: ' + (response.error || 'Failed to update task.'));
                        checkbox.prop('checked', !isChecked); // Revert
                    }
                },
                error: function() {
                    alert('Server connection error. Failed to update task status.');
                    checkbox.prop('checked', !isChecked); // Revert
                }
            });
        });
    });
</script>
