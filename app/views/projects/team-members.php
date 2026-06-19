<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light mb-4">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-users-group fs-3 text-primary"></i>
                    <h3 class="card-title mb-0 font-weight-bold">Manage Project Team</h3>
                </div>
                <a href="?page=projects-view&id=<?php echo $project['id']; ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left"></i> Back to Project
                </a>
            </div>
            
            <div class="card-body px-4 py-3">
                <p class="text-secondary fs-6 mb-1">Project: <strong><?php echo e($project['project_name']); ?></strong> (<?php echo e($project['project_code']); ?>)</p>
                <p class="text-secondary fs-7">Add or remove engineers, clients, or interns mapped to this project workspace.</p>
            </div>
        </div>
        
        <!-- Assignment Form -->
        <div class="card shadow-sm border border-light mb-4">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-user-plus me-1 text-primary"></i> Assign New Team Member
            </div>
            <form action="?page=projects-team&id=<?php echo $project['id']; ?>" method="POST" class="card-body px-4 py-3">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                
                <div class="row align-items-end g-3">
                    <div class="col-md-9 col-12">
                        <label class="form-label font-weight-semibold text-dark">Select User Profile</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose User --</option>
                            <?php foreach ($availableUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?> 
                                    (<?php echo e($user['email']); ?> - Role: <?php echo e($user['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 col-12">
                        <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-1">
                            <i class="ti ti-plus"></i> Assign User
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Mapped Team Directory -->
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <i class="ti ti-users text-primary me-1"></i> Current Assigned Members
            </div>
            <div class="card-body p-0">
                <?php if (empty($members)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="ti ti-users-group fs-2 mb-2"></i>
                        <p class="mb-0 fs-7">No members currently mapped. Use the form above to add.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter card-table mb-0 fs-7 align-middle">
                            <thead>
                                <tr class="bg-light">
                                    <th class="py-2.5 px-3">Name</th>
                                    <th class="py-2.5">Email</th>
                                    <th class="py-2.5">Role</th>
                                    <th class="py-2.5">Designation</th>
                                    <th class="py-2.5">Assigned Date</th>
                                    <th class="py-2.5 px-3 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $mem): ?>
                                    <tr>
                                        <td class="px-3 font-weight-semibold">
                                            <?php echo e($mem['first_name'] . ' ' . $mem['last_name']); ?>
                                        </td>
                                        <td class="text-secondary"><?php echo e($mem['email']); ?></td>
                                        <td>
                                            <span class="badge badge-role badge-<?php echo $mem['role']; ?> text-uppercase">
                                                <?php echo e($mem['role']); ?>
                                            </span>
                                        </td>
                                        <td class="text-secondary"><?php echo e($mem['designation'] ?: 'N/A'); ?></td>
                                        <td class="text-secondary">
                                            <?php echo date('M d, Y', strtotime($mem['assigned_at'])); ?>
                                        </td>
                                        <td class="px-3 text-end">
                                            <form action="?page=projects-team&id=<?php echo $project['id']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove <?php echo e($mem['first_name']); ?> from this project?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="user_id" value="<?php echo $mem['user_id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm p-1 fs-8">
                                                    <i class="ti ti-trash"></i> Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
