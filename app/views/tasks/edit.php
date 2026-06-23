<div class="row row-cards mb-4">
    <div class="col-12 col-lg-6 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 text-dark">
                    <i class="ti ti-edit fs-3 text-primary"></i>
                    <h3 class="card-title mb-0 font-weight-bold">Edit Task Details</h3>
                </div>
                <a href="<?php echo route('tickets-view', ['id' => $task['ticket_id'], 'title' => $task['ticket_title']]); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left"></i> Back to Ticket
                </a>
            </div>
            
            <form action="<?php echo route('tasks-edit', ['id' => $task['id']]); ?>" method="POST" class="card-body px-4 py-4 ajax-form">
                <?php echo csrf_field(); ?>
                
                <div class="row g-3">
                    <!-- Ticket Title Reference -->
                    <div class="col-12">
                        <span class="text-secondary fs-8 text-uppercase font-weight-bold d-block" style="letter-spacing: 0.5px;">Associated Ticket</span>
                        <p class="text-dark font-weight-semibold fs-6 mt-1 mb-0">#<?php echo $task['ticket_id']; ?>: <?php echo e($task['ticket_title']); ?></p>
                    </div>

                    <!-- Task Name -->
                    <div class="col-12 mt-3">
                        <label class="form-label font-weight-semibold text-dark required">Task / Item Name</label>
                        <input type="text" name="task_name" class="form-control" value="<?php echo e($task['task_name']); ?>" required>
                    </div>

                    <!-- Assignee -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Assign To</label>
                        <select name="assigned_member" class="form-select" required>
                            <option value="">-- Select developer or intern --</option>
                            <?php foreach ($projectMembers as $mem): ?>
                                <option value="<?php echo $mem['user_id']; ?>" <?php echo (int)$task['assigned_member'] === (int)$mem['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($mem['full_name']); ?> (<?php echo e($mem['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Due Date -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo $task['due_date'] ? date('Y-m-d', strtotime($task['due_date'])) : ''; ?>">
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Status</label>
                        <select name="status" class="form-select">
                            <option value="Pending" <?php echo $task['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo $task['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Completed" <?php echo $task['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Blocked" <?php echo $task['status'] === 'Blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="<?php echo route('tickets-view', ['id' => $task['ticket_id'], 'title' => $task['ticket_title']]); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
