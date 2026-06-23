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
                            <?php echo $isAdmin ? 'View and manage all assigned work items across projects.' : 'Tasks assigned to you, grouped by status.'; ?>
                        </p>
                    </div>
                </div>
                
                <?php if ($isAdmin): ?>
                <form method="GET" action="" class="d-flex align-items-center gap-2 ajax-filter-form" data-ajax-target="#my-tasks-content">
                    <input type="hidden" name="partial" value="1">
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

<div id="my-tasks-content" class="row g-4" data-ajax-container data-ajax-refresh-url="<?php echo e(route('tasks', ['partial' => 1, 'user_id' => $selectedUserId ?? ''])); ?>">
    <?php require __DIR__ . '/_list_content.php'; ?>
</div>
