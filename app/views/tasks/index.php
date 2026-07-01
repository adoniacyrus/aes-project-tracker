<?php
$isAdmin = can_manage_tasks();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
?>
<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card shadow-sm border border-light">
            <div class="card-body py-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-subtle text-primary rounded" style="width: 48px; height: 48px;">
                        <i class="ti ti-checkbox fs-2"></i>
                    </span>
                    <div>
                        <h4 class="mb-0 font-weight-semibold"><?php echo e($pageTitle); ?></h4>
                        <p class="text-secondary mb-0 fs-7">
                            <?php echo $isAdmin ? 'View and manage all assigned work items across projects.' : 'Tasks assigned to you, filtered by status.'; ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($isAdmin): ?>
                <form method="GET" action="" class="d-flex align-items-center gap-2 ajax-filter-form" data-ajax-target="#my-tasks-content">
                    <input type="hidden" name="partial" value="1">
                    <input type="hidden" name="status" value="<?php echo e($statusFilter ?? ''); ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-secondary"><i class="ti ti-user"></i></span>
                        <select name="user_id" class="form-select">
                            <option value="">All Assignees</option>
                            <?php foreach ($taskableUsers as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo ($selectedUserId ?? null) === (int)$u['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($u['full_name']); ?> (<?php echo e($u['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4 shadow-sm border border-light">
    <div id="my-tasks-content" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tasks', ['partial' => 1, 'user_id' => $selectedUserId ?? '', 'status' => $statusFilter ?? ''])); ?>">
        <?php require __DIR__ . '/_list_content.php'; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
    <?php require __DIR__ . '/_edit_modal.php'; ?>
<?php endif; ?>

<script>
    function clearTaskTabLoading() {
        $('#my-tasks-content').removeClass('is-refreshing').attr('aria-busy', 'false');
        $('#taskStatusTabs .project-status-tab').removeClass('is-loading pe-none');
    }

    $(document).on('click', '#taskStatusTabs .ajax-partial-link', function(e) {
        const $link = $(this);
        if ($link.hasClass('active')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        $('#taskStatusTabs .project-status-tab').removeClass('is-loading pe-none');
        $link.addClass('is-loading pe-none');
        $('#my-tasks-content').addClass('is-refreshing').attr('aria-busy', 'true');
    });

    $(document).on('ajax:content-updated', function(e, targetSelector, response) {
        if (targetSelector !== '#my-tasks-content') return;
        clearTaskTabLoading();
        if (!response || !response.refresh_url) return;
        try {
            const url = new URL(response.refresh_url, window.location.origin);
            $('form.ajax-filter-form input[name="status"]').val(url.searchParams.get('status') || '');
        } catch (err) {}
    });

    $(document).ajaxError(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('partial=1') !== -1 && /tasks/i.test(settings.url)) {
            clearTaskTabLoading();
        }
    });
</script>
