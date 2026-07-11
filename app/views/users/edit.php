<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex align-items-center">
                <a href="<?php echo route('users-view', ['id' => $user['id'], 'full_name' => $user['full_name']]); ?>" class="btn btn-outline-secondary btn-icon me-2" title="Go back to user details">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <h3 class="card-title mb-0 font-weight-bold">Edit User Profile</h3>
            </div>
            
            <form action="<?php echo route('users-edit', ['id' => $user['id']]); ?>" method="POST" class="card-body px-4 py-4">
                <?php echo csrf_field(); ?>
                
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="Full Name" value="<?php echo e($user['full_name']); ?>" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo e($user['email']); ?>" required>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo e($user['phone']); ?>">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">System Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                            <option value="developer" <?php echo $user['role'] === 'developer' ? 'selected' : ''; ?>>Developer</option>
                            <option value="intern" <?php echo $user['role'] === 'intern' ? 'selected' : ''; ?>>Intern</option>
                            <option value="client" <?php echo $user['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                        </select>
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Designation/Job Title</label>
                        <input type="text" name="designation" class="form-control" value="<?php echo e($user['designation']); ?>">
                    </div>

                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Organization</label>
                        <input type="text" name="organization" class="form-control" value="<?php echo e($user['organization']); ?>">
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="<?php echo route('users-view', ['id' => $user['id'], 'full_name' => $user['full_name']]); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
