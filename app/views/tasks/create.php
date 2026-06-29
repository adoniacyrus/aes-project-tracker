<div class="row row-cards mb-4">
    <div class="col-12 col-lg-6 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2 text-dark">
                    <i class="ti ti-checkbox fs-3 text-primary"></i>
                    <h3 class="card-title mb-0 font-weight-bold">Add Task Checklist Item</h3>
                </div>
                <a href="<?php echo route('tickets-view', ['id' => $ticket['id'], 'title' => $ticket['title']]); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left"></i> Back to Ticket
                </a>
            </div>
            
            <form action="<?php echo route('tasks-create'); ?>" method="POST" class="card-body px-4 py-4 ajax-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                
                <div class="row g-3">
                    <!-- Associated Ticket Info -->
                    <div class="col-12">
                        <span class="text-secondary fs-8 text-uppercase font-weight-bold d-block" style="letter-spacing: 0.5px;">Ticket Reference</span>
                        <p class="text-dark font-weight-semibold fs-6 mt-1 mb-0"><?php echo e($ticket['title']); ?></p>
                        <small class="text-muted fs-8">Project: <?php echo e($ticket['project_name']); ?> (<?php echo e($ticket['project_code']); ?>)</small>
                    </div>

                    <!-- Task Name -->
                    <div class="col-12 mt-3">
                        <label class="form-label font-weight-semibold text-dark required">Task Description / Item Name</label>
                        <input type="text" name="task_name" class="form-control" placeholder="e.g. Implement user search inputs" required>
                    </div>

                    <!-- Assign Member -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Assign To</label>
                        <select name="assigned_member" class="form-select" required>
                            <option value="">-- Select developer or intern --</option>
                            <?php foreach ($projectMembers as $mem): ?>
                                <option value="<?php echo $mem['user_id']; ?>">
                                    <?php echo e($mem['full_name']); ?> (<?php echo e($mem['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Due Date -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="<?php echo route('tickets-view', ['id' => $ticket['id'], 'title' => $ticket['title']]); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Add Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
