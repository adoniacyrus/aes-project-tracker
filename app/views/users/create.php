<div class="row">
    <div class="col-12 col-xl-8 mx-auto">
        
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center gap-2">
                <a href="<?php echo route('users'); ?>" class="btn btn-outline-secondary btn-icon me-2" title="Go back to list">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <h4 class="card-title mb-0"><i class="ti ti-user-plus me-1"></i> Add New User Account</h4>
            </div>
            
            <div class="card-body p-4">
                <form method="POST" action="<?php echo route('users-create'); ?>" novalidate>
                    <!-- CSRF Token hidden field -->
                    <?php echo csrf_field(); ?>

                    <h5 class="card-subtitle text-secondary border-bottom pb-2 mb-3">1. Personal Information</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium required">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" placeholder="John" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium required">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" placeholder="Doe" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="johndoe@aes.com" required>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="1234567890">
                        </div>
                    </div>

                    <h5 class="card-subtitle text-secondary border-bottom pb-2 mb-3">2. Work Profile & Role</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">System Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="developer" selected>Developer (Staff)</option>
                                <option value="intern">Intern (Junior Staff)</option>
                                <option value="client">Client (External Partner)</option>
                                <option value="admin">Administrator (Full Access)</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Designation / Job Title</label>
                            <input type="text" name="designation" class="form-control" placeholder="Senior Architect, PHP Intern, etc.">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Organization / Company</label>
                            <input type="text" name="organization" class="form-control" placeholder="AES" value="AES">
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Account Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active / Enabled</option>
                                <option value="inactive">Inactive / Disabled</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="card-subtitle text-secondary border-bottom pb-2 mb-3">3. Access Credentials</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6">
                            <label class="form-label font-weight-medium">Account Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password (min 6 characters)" required minlength="6">
                            <small class="text-muted-custom fs-8 mt-1 d-block">This password is encrypted and can be reset later.</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?php echo route('users'); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Save User Account</button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>
