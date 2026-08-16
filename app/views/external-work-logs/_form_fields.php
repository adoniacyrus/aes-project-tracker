<?php
$hideProject = !empty($hideProject);
$log = $log ?? [];
$includeStatus = !empty($includeStatus);
$projects = $projects ?? [];
$assignees = $assignees ?? [];
$lockedProjectId = $lockedProjectId ?? null;
?>
<?php echo csrf_field(); ?>
<div class="row g-3">
    <?php if ($hideProject && $lockedProjectId): ?>
        <input type="hidden" name="project_id" value="<?php echo (int)$lockedProjectId; ?>">
    <?php else: ?>
        <div class="col-12">
            <label class="form-label required">Project</label>
            <select name="project_id" class="form-select ewl-project-select" required>
                <option value="">Select a project...</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>" <?php echo ((int)($log['project_id'] ?? 0) === (int)$p['id']) ? 'selected' : ''; ?>>
                        <?php echo e($p['project_code'] . ' — ' . $p['project_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <label class="form-label required">Title</label>
        <input type="text" name="title" class="form-control ewl-title" maxlength="255" required value="<?php echo e($log['title'] ?? ''); ?>" placeholder="e.g. Update homepage banner">
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control ewl-description" rows="3" placeholder="What was requested and what work was done?"><?php echo e($log['description'] ?? ''); ?></textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label required">Communication Source</label>
        <select name="communication_source" class="form-select ewl-source" required>
            <?php foreach (external_work_log_sources() as $source): ?>
                <option value="<?php echo e($source); ?>" <?php echo (($log['communication_source'] ?? 'Email') === $source) ? 'selected' : ''; ?>>
                    <?php echo e($source); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label required">Requested By</label>
        <input type="text" name="requested_by" class="form-control ewl-requested-by" maxlength="255" required value="<?php echo e($log['requested_by'] ?? ''); ?>" placeholder="Client contact name">
    </div>

    <div class="col-md-6">
        <label class="form-label required">Assigned To</label>
        <select name="assigned_to" class="form-select ewl-assigned-to" required>
            <option value="">Select assignee...</option>
            <?php foreach ($assignees as $assignee): ?>
                <option value="<?php echo (int)$assignee['id']; ?>" <?php echo ((int)($log['assigned_to'] ?? ($_SESSION['user_id'] ?? 0)) === (int)$assignee['id']) ? 'selected' : ''; ?>>
                    <?php echo e($assignee['full_name']); ?> (<?php echo e($assignee['role']); ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label required">Work Date</label>
        <input type="date" name="work_date" class="form-control ewl-work-date" required value="<?php echo e($log['work_date'] ?? date('Y-m-d')); ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Estimated Hours</label>
        <input type="number" name="estimated_hours" class="form-control ewl-estimated-hours" min="0" step="0.25" value="<?php echo e($log['estimated_hours'] ?? ''); ?>">
    </div>

    <?php if ($includeStatus): ?>
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select ewl-status">
                <?php foreach (external_work_log_statuses() as $status): ?>
                    <option value="<?php echo e($status); ?>" <?php echo (($log['status'] ?? 'Pending') === $status) ? 'selected' : ''; ?>>
                        <?php echo e($status); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Actual Hours</label>
            <input type="number" name="actual_hours" class="form-control ewl-actual-hours" min="0" step="0.25" value="<?php echo e($log['actual_hours'] ?? ''); ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Completion Notes</label>
            <textarea name="completion_notes" class="form-control ewl-completion-notes" rows="3"><?php echo e($log['completion_notes'] ?? ''); ?></textarea>
        </div>
    <?php endif; ?>

    <div class="col-12">
        <label class="form-label">Reference Notes</label>
        <textarea name="client_reference" class="form-control ewl-client-reference" rows="2" placeholder="Email thread, meeting notes, ticket-less request details..."><?php echo e($log['client_reference'] ?? ''); ?></textarea>
    </div>
</div>
