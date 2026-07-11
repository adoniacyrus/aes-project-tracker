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
            <?php
                $clearFiltersUrl = route('users', ['partial' => 1]);
                $clearFiltersTarget = '#users-ajax-content';
                require __DIR__ . '/../partials/_clear_filters_link.php';
            ?>
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
    <?php
    $userCreateModalTitle = 'Add New User Account';
    require __DIR__ . '/_create_modal.php';
    ?>

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

        $(document).on('click', '.user-reset-password-btn', function(e) {
            e.preventDefault();
            const $btn = $(this);

            aesConfirmAction(null, function() {
                showLoader();
                $.ajax({
                    url: $btn.data('reset-url'),
                    type: 'POST',
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    data: {
                        csrf_token: window.AES_CSRF_TOKEN || '',
                        user_id: $btn.data('user-id')
                    },
                    success: function(response) {
                        hideLoader();
                        handleAjaxSuccess($btn, response);
                    },
                    error: function(xhr) {
                        hideLoader();
                        let errorMessage = 'An error occurred while resetting the password.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMessage = response.message;
                            }
                        } catch (err) {}
                        showToast(errorMessage, 'danger');
                    }
                });
            }, {
                title: 'Reset Password',
                html: '<p class="mb-2">Are you sure you want to reset this user\'s password?</p>'
                    + '<p class="mb-0 text-secondary">A new temporary password will be generated and emailed to the user.</p>',
                confirmLabel: 'Reset Password',
                confirmClass: 'btn btn-primary'
            });
        });
    </script>
