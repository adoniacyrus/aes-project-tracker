<?php
// Build the project members map for interactive UI mapping
$projectMembersMap = [];
foreach ($projects as $p) {
    // We can fetch members using ProjectModel
    $projectMembersMap[$p['id']] = $this->projectModel->getProjectMembers($p['id']);
}
?>

<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2 text-dark">
                    <i class="ti ti-ticket fs-3 text-primary"></i>
                    <h3 class="card-title mb-0 font-weight-bold">Create New Ticket</h3>
                </div>
            </div>
            
            <form action="?page=tickets-create" method="POST" class="card-body px-4 py-4">
                <?php echo csrf_field(); ?>
                
                <div class="row g-3">
                    <!-- Project Selector -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">Associated Project</label>
                        <select name="project_id" id="projectSelect" class="form-select" required>
                            <option value="">-- Choose Project --</option>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $selectedProjectId === (int)$p['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($p['project_name']); ?> (<?php echo e($p['project_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category Selector -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">Category / Issue Type</label>
                        <select name="category" class="form-select" required>
                            <option value="Bug Fix">Bug Fix</option>
                            <option value="New Feature Request">New Feature Request</option>
                            <option value="Enhancement Request">Enhancement Request</option>
                            <option value="Technical Support">Technical Support</option>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Ticket Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Brief summary of the issue or feature request..." required>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Description & Steps to Reproduce</label>
                        <textarea name="description" rows="6" class="form-control" placeholder="Provide full details, expected vs actual behavior, steps to reproduce, or requirements..." required></textarea>
                    </div>

                    <!-- Assignee Selector -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Assign To</label>
                        <select name="assigned_to" id="assigneeSelect" class="form-select">
                            <option value="">-- Unassigned --</option>
                            <!-- Dynamically populated by JS based on project choice -->
                        </select>
                    </div>

                    <!-- Priority Selector -->
                    <div class="col-md-3 col-6">
                        <label class="form-label font-weight-semibold text-dark">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <!-- Due Date -->
                    <div class="col-md-3 col-6">
                        <label class="form-label font-weight-semibold text-dark">Due Date</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="?page=tickets" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Project Members Mapping data structure
    const projectMembers = <?php echo json_encode($projectMembersMap); ?>;

    function populateAssignees(projectId) {
        const select = document.getElementById('assigneeSelect');
        // Clear all options except default
        select.innerHTML = '<option value="">-- Unassigned --</option>';

        if (projectId && projectMembers[projectId]) {
            const members = projectMembers[projectId];
            members.forEach(member => {
                const opt = document.createElement('option');
                opt.value = member.user_id;
                opt.textContent = `${member.first_name} ${member.last_name} (${member.role})`;
                select.appendChild(opt);
            });
        }
    }

    document.getElementById('projectSelect').addEventListener('change', function() {
        populateAssignees(this.value);
    });

    // Populate initially on load if project is preselected
    window.addEventListener('DOMContentLoaded', () => {
        const val = document.getElementById('projectSelect').value;
        if (val) {
            populateAssignees(val);
        }
    });
</script>
