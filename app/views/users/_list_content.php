    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="p-5 text-center text-muted">
                <i class="ti ti-users fs-1 mb-2 text-secondary"></i>
                <p class="mb-0 fs-6">No users found matching your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-vcenter card-table mb-0 fs-6 align-middle">
                    <thead>
                        <tr class="bg-light">
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3">Email Address</th>
                            <th class="py-3">Role</th>
                            <th class="py-3">Designation & Org</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Last Sign In</th>
                            <th class="py-3 px-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-4 font-weight-semibold">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 32px; height: 32px; font-size: 12px;">
                                            <?php echo user_initials($user['full_name']); ?>
                                        </div>
                                        <div>
                                            <?php echo e($user['full_name']); ?>
                                            <?php if ((int)$user['id'] === (int)$_SESSION['user_id']): ?>
                                                <span class="badge bg-secondary-subtle text-secondary font-weight-normal ms-1 fs-8">You</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-secondary"><?php echo e($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-role badge-<?php echo $user['role']; ?> text-uppercase">
                                        <?php echo e($user['role']); ?>
                                    </span>
                                </td>
                                <td class="text-secondary">
                                    <?php echo e($user['designation'] ?: 'N/A'); ?>
                                    <div class="fs-8 text-muted"><?php echo e($user['organization'] ?: 'AES'); ?></div>
                                </td>
                                <td>
                                    <?php if ($user['status'] === 'active'): ?>
                                        <span class="badge bg-success px-2 py-1 fs-8 rounded">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-2 py-1 fs-8 rounded">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary">
                                    <?php
                                        if ($user['last_login']) {
                                            echo date('M d, Y H:i', strtotime($user['last_login']));
                                        } else {
                                            echo '<span class="text-muted-custom italic fs-8">Never</span>';
                                        }
                                    ?>
                                </td>
                                <td class="px-4 text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="<?php echo route('users-view', ['id' => $user['id'], 'full_name' => $user['full_name']]); ?>" class="btn btn-outline-secondary btn-icon" title="View Account Details">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-primary btn-icon" title="Edit Profile Details"
                                            data-bs-toggle="modal"
                                            data-bs-target="#userEditModal"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-edit-url="<?php echo e(route('users-edit', ['id' => $user['id']])); ?>"
                                            data-full-name="<?php echo e($user['full_name']); ?>"
                                            data-email="<?php echo e($user['email']); ?>"
                                            data-phone="<?php echo e($user['phone']); ?>"
                                            data-role="<?php echo e($user['role']); ?>"
                                            data-designation="<?php echo e($user['designation']); ?>"
                                            data-organization="<?php echo e($user['organization']); ?>"
                                            data-status="<?php echo e($user['status']); ?>"
                                            onclick="openUserEditModal(this)">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <?php if (is_protected_system_admin($user)): ?>
                                                    <span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo e(system_admin_deactivate_message()); ?>">
                                                        <button type="button" class="btn btn-outline-secondary btn-icon" disabled aria-label="Cannot deactivate System Admin">
                                                            <i class="ti ti-lock"></i>
                                                        </button>
                                                    </span>
                                                <?php else: ?>
                                                <a href="<?php echo route('users-status', ['id' => $user['id'], 'status' => 'inactive']); ?>"
                                                   class="btn btn-outline-danger btn-icon ajax-link"
                                                   title="Deactivate Account"
                                                   data-confirm="Are you sure you want to deactivate this user?">
                                                    <i class="ti ti-user-x"></i>
                                                </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="<?php echo route('users-status', ['id' => $user['id'], 'status' => 'active']); ?>"
                                                   class="btn btn-outline-success btn-icon ajax-link"
                                                   title="Activate Account"
                                                   data-confirm="Are you sure you want to activate this user?">
                                                    <i class="ti ti-user-check"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-transparent border-top py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-secondary fs-7">
                Showing Page <strong><?php echo $pageNum; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo $totalUsers; ?> users)
            </span>
            <nav aria-label="Users Page Navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?php echo ($pageNum <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link ajax-partial-link" href="<?php echo route('users', ['q' => $search, 'p' => $pageNum - 1, 'partial' => 1]); ?>"><i class="ti ti-chevron-left fs-8"></i> Prev</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $pageNum) ? 'active' : ''; ?>">
                            <a class="page-link ajax-partial-link" href="<?php echo route('users', ['q' => $search, 'p' => $i, 'partial' => 1]); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($pageNum >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link ajax-partial-link" href="<?php echo route('users', ['q' => $search, 'p' => $pageNum + 1, 'partial' => 1]); ?>">Next <i class="ti ti-chevron-right fs-8"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
