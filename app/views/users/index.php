<div class="card mb-4 shadow-sm border border-light">
    <!-- Header with Search and Create Actions -->
    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-sm-items-center gap-3">
        <form method="GET" action="" class="d-flex flex-fill max-width-md align-items-center gap-2">
            <input type="hidden" name="page" value="users">
            <div class="input-group input-group-flat">
                <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-1" placeholder="Search by name, email, role, or designation..." value="<?php echo e($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary px-3">Search</button>
            <?php if (!empty($search)): ?>
                <a href="<?php echo route('users'); ?>" class="btn btn-outline-secondary btn-icon" title="Clear Search"><i class="ti ti-x"></i></a>
            <?php endif; ?>
        </form>
        <div>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 font-weight-medium" data-bs-toggle="modal" data-bs-target="#userCreateModal">
                <i class="ti ti-plus"></i> Add New User
            </button>
        </div>
    </div>

    <!-- Table content -->
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
                                        <!-- Mini initials avatar -->
                                        <div class="avatar bg-light text-secondary rounded-circle d-flex align-items-center justify-content-center font-weight-bold" style="width: 32px; height: 32px; font-size: 12px;">
                                            <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>
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
                                        <!-- View -->
                                        <a href="<?php echo route('users-view', ['id' => $user['id'], 'slug' => $user['first_name'] . ' ' . $user['last_name']]); ?>" class="btn btn-outline-secondary btn-icon" title="View Account Details">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        <!-- Edit -->
                                        <button type="button" class="btn btn-outline-primary btn-icon" title="Edit Profile Details"
                                            data-bs-toggle="modal"
                                            data-bs-target="#userEditModal"
                                            data-id="<?php echo $user['id']; ?>"
                                            data-first-name="<?php echo e($user['first_name']); ?>"
                                            data-last-name="<?php echo e($user['last_name']); ?>"
                                            data-email="<?php echo e($user['email']); ?>"
                                            data-phone="<?php echo e($user['phone']); ?>"
                                            data-role="<?php echo e($user['role']); ?>"
                                            data-designation="<?php echo e($user['designation']); ?>"
                                            data-organization="<?php echo e($user['organization']); ?>"
                                            data-status="<?php echo e($user['status']); ?>"
                                            onclick="openUserEditModal(this)">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <!-- Toggle Status -->
                                        <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                            <?php if ($user['status'] === 'active'): ?>
                                                <a href="<?php echo route('users-status', ['id' => $user['id'], 'status' => 'inactive']); ?>" 
                                                   class="btn btn-outline-danger btn-icon" 
                                                   title="Deactivate Account"
                                                   onclick="return confirm('Are you sure you want to deactivate user: <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>? The user will be logged out and blocked from logging in.');">
                                                    <i class="ti ti-user-x"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo route('users-status', ['id' => $user['id'], 'status' => 'active']); ?>" 
                                                   class="btn btn-outline-success btn-icon" 
                                                   title="Activate Account"
                                                   onclick="return confirm('Are you sure you want to activate user: <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>?');">
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

    <!-- User Modals -->
    <div class="modal fade" id="userCreateModal" tabindex="-1" aria-labelledby="userCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userCreateModalLabel"><i class="ti ti-user-plus me-2"></i> Add New User Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo route('users-create'); ?>" novalidate>
                    <div class="modal-body">
                        <?php echo csrf_field(); ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">First Name</label>
                                <input type="text" name="first_name" class="form-control" placeholder="John" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Last Name</label>
                                <input type="text" name="last_name" class="form-control" placeholder="Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="johndoe@aes.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" placeholder="1234567890">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">System Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="developer" selected>Developer</option>
                                    <option value="intern">Intern</option>
                                    <option value="client">Client</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation / Job Title</label>
                                <input type="text" name="designation" class="form-control" placeholder="Senior Architect, PHP Intern">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Organization / Company</label>
                                <input type="text" name="organization" class="form-control" placeholder="AES" value="AES">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Account Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Enter password" required minlength="6">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userEditModal" tabindex="-1" aria-labelledby="userEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userEditModalLabel"><i class="ti ti-edit me-2"></i> Edit User Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="userEditForm" method="POST" action="<?php echo route('users-edit', ['id' => 0]); ?>" novalidate>
                    <div class="modal-body">
                        <?php echo csrf_field(); ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">First Name</label>
                                <input type="text" name="first_name" id="editFirstName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Last Name</label>
                                <input type="text" name="last_name" id="editLastName" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Email Address</label>
                                <input type="email" name="email" id="editEmail" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" id="editPhone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">System Role</label>
                                <select name="role" id="editRole" class="form-select" required>
                                    <option value="admin">Administrator</option>
                                    <option value="developer">Developer</option>
                                    <option value="intern">Intern</option>
                                    <option value="client">Client</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Designation / Job Title</label>
                                <input type="text" name="designation" id="editDesignation" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Organization / Company</label>
                                <input type="text" name="organization" id="editOrganization" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Account Status</label>
                                <select name="status" id="editStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openUserEditModal(button) {
            const form = document.getElementById('userEditForm');
            form.action = '<?php echo route("users-edit", ["id" => "__USER_ID__"]); ?>'.replace('__USER_ID__', button.dataset.id);
            document.getElementById('editFirstName').value = button.dataset.firstName || '';
            document.getElementById('editLastName').value = button.dataset.lastName || '';
            document.getElementById('editEmail').value = button.dataset.email || '';
            document.getElementById('editPhone').value = button.dataset.phone || '';
            document.getElementById('editRole').value = button.dataset.role || 'developer';
            document.getElementById('editDesignation').value = button.dataset.designation || '';
            document.getElementById('editOrganization').value = button.dataset.organization || '';
            document.getElementById('editStatus').value = button.dataset.status || 'active';
        }
    </script>

    <!-- Pagination controls -->
    <?php if ($totalPages > 1): ?>
        <div class="card-footer bg-transparent border-top py-3 px-4 d-flex justify-content-between align-items-center">
            <span class="text-secondary fs-7">
                Showing Page <strong><?php echo $pageNum; ?></strong> of <strong><?php echo $totalPages; ?></strong> (Total <?php echo $totalUsers; ?> users)
            </span>
            <nav aria-label="Users Page Navigation">
                <ul class="pagination pagination-sm mb-0">
                    <!-- Prev -->
                    <li class="page-item <?php echo ($pageNum <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo route('users', ['q' => $search, 'p' => $pageNum - 1]); ?>"><i class="ti ti-chevron-left fs-8"></i> Prev</a>
                    </li>
                    
                    <!-- Pages -->
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i === $pageNum) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo route('users', ['q' => $search, 'p' => $i]); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- Next -->
                    <li class="page-item <?php echo ($pageNum >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo route('users', ['q' => $search, 'p' => $pageNum + 1]); ?>">Next <i class="ti ti-chevron-right fs-8"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
