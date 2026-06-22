<?php
// Standalone create user page (modal on index is primary UI)
?>
<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <h3 class="card-title mb-0 font-weight-bold">Add New User</h3>
            </div>
            <form action="<?php echo route('users-create'); ?>" method="POST" class="card-body px-4 py-4">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="John Smith" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="john@aes.com" required>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="1234567890">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">System Role</label>
                        <select name="role" class="form-select" required>
                            <option value="developer" selected>Developer</option>
                            <option value="intern">Intern</option>
                            <option value="client">Client</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Designation</label>
                        <input type="text" name="designation" class="form-control" placeholder="Senior Engineer">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Organization</label>
                        <input type="text" name="organization" class="form-control" value="AES">
                    </div>
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                </div>
                <hr class="my-4 text-muted">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo route('users'); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
