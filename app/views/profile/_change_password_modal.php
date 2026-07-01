<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title font-weight-semibold" id="changePasswordModalLabel">
                    <i class="ti ti-key me-1 text-primary"></i> Change Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?php echo route('profile-change-password'); ?>" class="modal-body ajax-form" novalidate data-ajax-reset="true">
                <?php echo csrf_field(); ?>

                <p class="text-secondary fs-7 mb-3">
                    Please update your password regularly to keep your account secure. Your new password must contain at least 6 characters.
                </p>

                <div class="mb-3">
                    <label class="form-label font-weight-medium">Current Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent text-secondary"><i class="ti ti-lock"></i></span>
                        <input type="password" name="current_password" class="form-control" placeholder="Current password" required autocomplete="current-password">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label font-weight-medium">New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent text-secondary"><i class="ti ti-lock-open"></i></span>
                        <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6" autocomplete="new-password">
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label font-weight-medium">Confirm New Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent text-secondary"><i class="ti ti-lock-check"></i></span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required minlength="6" autocomplete="new-password">
                    </div>
                </div>

                <div class="modal-footer border-top-0 px-0 pb-0 pt-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
