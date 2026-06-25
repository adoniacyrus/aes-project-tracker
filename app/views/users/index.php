<div class="card mb-4 shadow-sm border border-light">
    <!-- Header with Search and Create Actions -->
    <div class="card-header bg-transparent border-bottom py-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-sm-items-center gap-3">
        <form method="GET" action="<?php echo route('users'); ?>" class="d-flex flex-fill max-width-md align-items-center gap-2 ajax-filter-form" data-ajax-target="#users-ajax-content">
            <input type="hidden" name="partial" value="1">
            <div class="input-group input-group-flat">
                <span class="input-group-text border-end-0 bg-transparent text-secondary"><i class="ti ti-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-1" placeholder="Search by name, email, role, or designation..." value="<?php echo e($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary px-3">Search</button>
            <?php if (!empty($search)): ?>
                <a href="<?php echo route('users', ['partial' => 1]); ?>" class="btn btn-outline-secondary btn-icon ajax-partial-link" data-ajax-target="#users-ajax-content" title="Clear Search"><i class="ti ti-x"></i></a>
            <?php endif; ?>
        </form>
        <div>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 font-weight-medium" data-bs-toggle="modal" data-bs-target="#userCreateModal">
                <i class="ti ti-plus"></i> Add New User
            </button>
        </div>
    </div>

    <div id="users-ajax-content" data-ajax-container data-ajax-refresh-url="<?php echo e(route('users', ['partial' => 1, 'q' => $search, 'p' => $pageNum])); ?>">
        <?php require __DIR__ . '/_list_content.php'; ?>
    </div>
</div>

    <!-- User Modals -->
    <div class="modal fade" id="userCreateModal" tabindex="-1" aria-labelledby="userCreateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form method="POST" action="<?php echo route('users-create'); ?>" class="modal-content ajax-form" novalidate data-ajax-reset="true" data-ajax-refresh="#users-ajax-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userCreateModalLabel"><i class="ti ti-user-plus me-2"></i> Add New User Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                        <!-- <div class="col-md-6">
                            <label class="form-label">Account Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div> -->
                        <div class="col-12">
                            <div class="alert alert-info mb-0 py-2 px-3 fs-7">
                                <i class="ti ti-mail me-1"></i>
                                A secure temporary password will be generated automatically and emailed to the user.
                            </div>
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

    <div class="modal fade" id="userEditModal" tabindex="-1" aria-labelledby="userEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form id="userEditForm" method="POST" action="" class="modal-content ajax-form" novalidate data-ajax-refresh="#users-ajax-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userEditModalLabel"><i class="ti ti-edit me-2"></i> Edit User Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
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
                        <!-- <div class="col-md-6">
                            <label class="form-label">Account Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div> -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUserEditModal(button) {
            const form = document.getElementById('userEditForm');
            form.action = button.dataset.editUrl || '';
            document.getElementById('editFullName').value = button.dataset.fullName || '';
            document.getElementById('editEmail').value = button.dataset.email || '';
            document.getElementById('editPhone').value = button.dataset.phone || '';
            document.getElementById('editRole').value = button.dataset.role || 'developer';
            document.getElementById('editDesignation').value = button.dataset.designation || '';
            document.getElementById('editOrganization').value = button.dataset.organization || '';
        }
    </script>
