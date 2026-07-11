<?php
// Standalone create user page (modal on index/dashboard is primary UI)
?>
<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <h3 class="card-title mb-0 font-weight-bold">Add New User</h3>
            </div>
            <form action="<?php echo route('users-create'); ?>" method="POST" class="card-body px-4 py-4">
                <?php require __DIR__ . '/_create_form_fields.php'; ?>
                <hr class="my-4 text-muted">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo route('users'); ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
