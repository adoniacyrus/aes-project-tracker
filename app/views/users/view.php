<div class="row g-4">
    <!-- User Profile Overview Card -->
    <div class="col-12 col-xl-4">
        <div class="card h-100 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center gap-2">
                <a href="<?php echo route('users'); ?>" class="btn btn-outline-secondary btn-icon me-2" title="Back to User List">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <h4 class="card-title mb-0">User Details</h4>
            </div>
            
            <div class="card-body p-4 text-center border-bottom">
                <!-- Initials Avatar -->
                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center font-weight-bold mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px;">
                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                </div>
                <h3 class="mb-1 font-weight-bold"><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <span class="badge badge-role badge-<?php echo $user['role']; ?> text-uppercase mb-3"><?php echo e($user['role']); ?></span>
                
                <div class="d-flex justify-content-center gap-2">
                    <a href="<?php echo route('users-edit', ['id' => $user['id']]); ?>" class="btn btn-primary btn-sm d-flex align-items-center gap-1 px-3">
                        <i class="ti ti-edit"></i> Edit Profile
                    </a>
                    
                    <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                        <?php if ($user['status'] === 'active'): ?>
                            <a href="<?php echo route('users-status', ['id' => $user['id'], 'status' => 'inactive']); ?>" 
                               class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 px-3" 
                               onclick="return confirm('Are you sure you want to deactivate this account?');">
                                <i class="ti ti-user-x"></i> Deactivate
                            </a>
                        <?php else: ?>
                            <a href="<?php echo route('users-status', ['id' => $user['id'], 'status' => 'active']); ?>" 
                               class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 px-3" 
                               onclick="return confirm('Are you sure you want to activate this account?');">
                                <i class="ti ti-user-check"></i> Activate
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Email Address</span>
                    <span class="fs-6 font-weight-medium"><?php echo e($user['email']); ?></span>
                </div>
                
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Phone Number</span>
                    <span class="fs-6 font-weight-medium"><?php echo e($user['phone'] ?: 'Not Provided'); ?></span>
                </div>
                
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Designation</span>
                    <span class="fs-6 font-weight-medium"><?php echo e($user['designation'] ?: 'No Title'); ?></span>
                </div>
                
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Organization</span>
                    <span class="fs-6 font-weight-medium"><?php echo e($user['organization'] ?: 'AES'); ?></span>
                </div>
                
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Account Status</span>
                    <?php if ($user['status'] === 'active'): ?>
                        <span class="badge bg-success fs-8 px-2 py-0.5">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary fs-8 px-2 py-0.5">Inactive</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Last Login</span>
                    <span class="fs-6 font-weight-medium">
                        <?php echo $user['last_login'] ? date('M d, Y H:i:s', strtotime($user['last_login'])) : 'Never logged in'; ?>
                    </span>
                </div>

                <div>
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Account Created</span>
                    <span class="fs-6 font-weight-medium text-secondary">
                        <?php echo date('M d, Y H:i:s', strtotime($user['created_at'])); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- User Audit Logs History -->
    <div class="col-12 col-xl-8">
        <div class="card h-100 shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <h4 class="card-title mb-0"><i class="ti ti-history me-1"></i> User Activity Log History</h4>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($userLogs)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="ti ti-timeline fs-1 mb-2 text-secondary"></i>
                        <p class="mb-0 fs-6">No recorded activity history for this user.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter card-table mb-0 fs-7">
                            <thead>
                                <tr class="bg-light">
                                    <th class="py-2 px-3">Action</th>
                                    <th class="py-2">Audit Detail</th>
                                    <th class="py-2">IP Address</th>
                                    <th class="py-2">Device / Browser</th>
                                    <th class="py-2 px-3 text-end">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userLogs as $log): ?>
                                    <tr>
                                        <td class="px-3 font-weight-medium">
                                            <?php 
                                                $actionClass = 'bg-secondary-subtle text-secondary';
                                                if (str_contains($log['action'], 'success')) $actionClass = 'bg-success-subtle text-success';
                                                if (str_contains($log['action'], 'failed')) $actionClass = 'bg-danger-subtle text-danger';
                                                if (str_contains($log['action'], 'create')) $actionClass = 'bg-primary-subtle text-primary';
                                                if (str_contains($log['action'], 'change') || str_contains($log['action'], 'reset')) $actionClass = 'bg-warning-subtle text-warning';
                                            ?>
                                            <span class="badge rounded-pill <?php echo $actionClass; ?> px-2 py-1 fs-8">
                                                <?php echo e(str_replace('_', ' ', $log['action'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-secondary text-wrap" style="max-width: 250px;"><?php echo e($log['details']); ?></td>
                                        <td class="text-secondary font-monospace fs-8"><?php echo e($log['ip_address']); ?></td>
                                        <td class="text-secondary text-truncate fs-8" style="max-width: 150px;" title="<?php echo e($log['user_agent']); ?>">
                                            <?php echo e($log['user_agent']); ?>
                                        </td>
                                        <td class="px-3 text-end text-secondary">
                                            <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
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
