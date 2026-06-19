<div class="row">
    <div class="col-12 col-xl-6 mx-auto">
        
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <h4 class="card-title mb-0"><i class="ti ti-key me-1"></i> Change Password</h4>
            </div>
            
            <div class="card-body p-4">
                <form method="POST" action="?page=profile-change-password" novalidate>
                    <!-- CSRF Token hidden field -->
                    <?php echo csrf_field(); ?>

                    <p class="text-secondary fs-7 mb-4">
                        Please update your password regularly to keep your account secure. Your new password must contain at least 6 characters.
                    </p>

                    <div class="mb-3">
                        <label class="form-label font-weight-medium">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent text-secondary"><i class="ti ti-lock"></i></span>
                            <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <hr class="my-4 text-secondary opacity-25">

                    <div class="mb-3">
                        <label class="form-label font-weight-medium">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent text-secondary"><i class="ti ti-lock-open"></i></span>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-medium">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent text-secondary"><i class="ti ti-lock-check"></i></span>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required minlength="6">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-4">
                        <a href="?page=profile" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>
