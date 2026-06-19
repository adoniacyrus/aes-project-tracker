<div class="row g-4">
    <!-- User Profile Details Edit Card -->
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center gap-2">
                <a href="?page=users-view&id=<?php echo $user['id']; ?>" class="btn btn-outline-secondary btn-icon me-2" title="Go back to user details">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <h4 class="card-title mb-0"><i class="ti ti-edit me-1"></i> Edit User Profile</h4>
            </div>
            
            <div class="card-body p-4">
                <form method="POST" action="?page=users-edit&id=<?php echo $user['id']; ?>" novalidate>
                    <!-- CSRF Token hidden field -->
                    <?php echo csrf_field(); ?>

                    <h5 class="card-subtitle text-secondary border-bottom pb-2 mb-3">Personal Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" placeholder="First Name" value="<?php echo e($user['first_name']); ?>" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" placeholder="Last Name" value="<?php echo e($user['last_name']); ?>" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="email@example.com" value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Phone Number" value="<?php echo e($user['phone']); ?>">
                        </div>
                    </div>

                    <h5 class="card-subtitle text-secondary border-bottom pb-2 mb-3">Work Profile & Role</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">System Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                <option value="developer" <?php echo ($user['role'] === 'developer') ? 'selected' : ''; ?>>Developer</option>
                                <option value="intern" <?php echo ($user['role'] === 'intern') ? 'selected' : ''; ?>>Intern</option>
                                <option value="client" <?php echo ($user['role'] === 'client') ? 'selected' : ''; ?>>Client</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Designation / Job Title</label>
                            <input type="text" name="designation" class="form-control" placeholder="Designation" value="<?php echo e($user['designation']); ?>">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Organization / Company</label>
                            <input type="text" name="organization" class="form-control" placeholder="Organization" value="<?php echo e($user['organization']); ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="?page=users-view&id=<?php echo $user['id']; ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save Profile Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Administrative Force Reset Password Card -->
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <h4 class="card-title mb-0"><i class="ti ti-key me-1 text-warning"></i> Force Reset Password</h4>
            </div>
            
            <div class="card-body p-4">
                <p class="text-secondary fs-7 mb-3">
                    As an administrator, you can override and reset this user's password directly. The user will be notified of their new credentials.
                </p>
                
                <form method="POST" action="?page=users-admin-reset">
                    <!-- CSRF Token hidden field -->
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                    <div class="mb-3">
                        <label class="form-label font-weight-medium">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required minlength="6">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning font-weight-semibold text-dark" onclick="return confirm('Are you sure you want to force reset this user\'s password?');">
                            Reset Account Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
