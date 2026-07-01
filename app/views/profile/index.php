<div class="row g-4">
    <!-- Profile Info Card -->
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <h4 class="card-title mb-0">My Account Summary</h4>
            </div>
            
            <div class="card-body p-4 text-center border-bottom">
                <!-- Initials Avatar -->
                <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center font-weight-bold mx-auto mb-3 profile-avatar-initials" style="width: 72px; height: 72px; font-size: 28px;">
                    <?php echo user_initials($user['full_name']); ?>
                </div>
                <h3 class="mb-1 font-weight-bold profile-summary-name"><?php echo e($user['full_name']); ?></h3>
                <span class="badge badge-role badge-<?php echo $user['role']; ?> text-uppercase mb-2"><?php echo e($user['role']); ?></span>
                <p class="text-secondary fs-7 mb-0">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
            </div>

            <div class="card-body p-4">
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Email Address</span>
                    <span class="fs-6 font-weight-medium text-secondary profile-summary-email"><?php echo e($user['email']); ?></span>
                    <?php if (($user['role'] ?? '') !== 'admin'): ?>
                        <small class="text-muted d-block fs-8 italic">Contact administrator to change email.</small>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Designation</span>
                    <span class="fs-6 font-weight-medium profile-summary-designation"><?php echo e($user['designation'] ?: 'No Title'); ?></span>
                </div>
                
                <div class="mb-3">
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Organization</span>
                    <span class="fs-6 font-weight-medium profile-summary-organization"><?php echo e($user['organization'] ?: 'AES'); ?></span>
                </div>

                <div>
                    <span class="text-secondary fs-8 text-uppercase d-block font-weight-bold">Last Sign In</span>
                    <span class="fs-6 font-weight-medium text-secondary">
                        <?php echo $user['last_login'] ? date('M d, Y H:i:s', strtotime($user['last_login'])) : 'N/A'; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Card -->
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <h4 class="card-title mb-0"><i class="ti ti-user-edit me-1"></i> Edit Contact Details</h4>
            </div>
            
            <div class="card-body p-4">
                <form method="POST" action="<?php echo route('profile'); ?>" class="ajax-form" novalidate>
                    <!-- CSRF Token hidden field -->
                    <?php echo csrf_field(); ?>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label font-weight-medium">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" placeholder="Full Name" value="<?php echo e($user['full_name']); ?>" required>
                        </div>
                        <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <div class="col-12">
                            <label class="form-label font-weight-medium">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <?php endif; ?>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Phone Number" value="<?php echo e($user['phone']); ?>">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Designation / Job Title</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Developer, Client Support" value="<?php echo e($user['designation']); ?>">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Organization / Company</label>
                            <input type="text" name="organization" class="form-control" placeholder="Company" value="<?php echo e($user['organization']); ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="ti ti-key me-1"></i> Change Password
                        </button>
                        <button type="submit" class="btn btn-primary px-4">Save Profile Info</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_change_password_modal.php'; ?>
