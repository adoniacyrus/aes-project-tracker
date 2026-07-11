<?php echo csrf_field(); ?>
<div class="row g-3">
    <div class="col-12">
        <label class="form-label required">Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="John Smith" required>
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
    <div class="col-12">
        <div class="alert alert-info mb-0 py-2 px-3 fs-7">
            <i class="ti ti-mail me-1"></i>
            A secure temporary password will be generated automatically and emailed to the user.
        </div>
    </div>
</div>
