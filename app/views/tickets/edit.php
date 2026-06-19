<?php
// Build the project members map for interactive UI mapping
$projectMembersMap = [];
foreach ($projects as $p) {
    $projectMembersMap[$p['id']] = $this->projectModel->getProjectMembers($p['id']);
}
?>

<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2 text-dark">
                    <i class="ti ti-edit fs-3 text-primary"></i>
                    <h3 class="card-title mb-0 font-weight-bold">Edit Ticket #<?php echo $ticket['id']; ?></h3>
                </div>
            </div>
            
            <form action="?page=tickets-edit&id=<?php echo $ticket['id']; ?>" method="POST" class="card-body px-4 py-4">
                <?php echo csrf_field(); ?>
                
                <div class="row g-3">
                    <!-- Project Selector -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">Associated Project</label>
                        <select name="project_id" id="projectSelect" class="form-select" required>
                            <?php foreach ($projects as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo (int)$ticket['project_id'] === (int)$p['id'] ? 'selected' : ''; ?>>
                                    <?php echo e($p['project_name']); ?> (<?php echo e($p['project_code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category Selector -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark required">Category / Issue Type</label>
                        <select name="category" class="form-select" required>
                            <?php 
                            $categories = ['Bug Fix', 'New Feature Request', 'Enhancement Request', 'Technical Support'];
                            foreach ($categories as $cat):
                            ?>
                                <option value="<?php echo $cat; ?>" <?php echo $ticket['category'] === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Ticket Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo e($ticket['title']); ?>" required>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark required">Description & Steps</label>
                        <textarea name="description" rows="6" class="form-control" required><?php echo e($ticket['description']); ?></textarea>
                    </div>

                    <!-- Assignee Selector -->
                    <div class="col-md-6 col-12">
                        <label class="form-label font-weight-semibold text-dark">Assign To</label>
                        <select name="assigned_to" id="assigneeSelect" class="form-select">
                            <option value="">-- Unassigned --</option>
                            <!-- Populated dynamically by JS -->
                        </select>
                    </div>

                    <!-- Priority Selector -->
                    <div class="col-md-3 col-6">
                        <label class="form-label font-weight-semibold text-dark">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low" <?php echo $ticket['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $ticket['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $ticket['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="critical" <?php echo $ticket['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>

                    <!-- Due Date -->
                    <div class="col-md-3 col-6">
                        <label class="form-label font-weight-semibold text-dark">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="<?php echo $ticket['due_date'] ? date('Y-m-d', strtotime($ticket['due_date'])) : ''; ?>">
                    </div>

                    <!-- Status Selector (Mainly for Admins, others transition via Workflow panel) -->
                    <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                        <div class="col-md-6 col-12">
                            <label class="form-label font-weight-semibold text-dark">Status (Admin Override)</label>
                            <select name="status" class="form-select">
                                <?php 
                                $allStatuses = ['Open', 'Awaiting Admin Approval', 'Awaiting Payment', 'Approved', 'In Development', 'Resolved', 'Reopened', 'Closed', 'Rejected', 'On Hold'];
                                foreach ($allStatuses as $st):
                                ?>
                                    <option value="<?php echo $st; ?>" <?php echo $ticket['status'] === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="status" value="<?php echo $ticket['status']; ?>">
                    <?php endif; ?>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="?page=tickets-view&id=<?php echo $ticket['id']; ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const projectMembers = <?php echo json_encode($projectMembersMap); ?>;
    const initialAssignee = <?php echo json_encode($ticket['assigned_to']); ?>;

    function populateAssignees(projectId) {
        const select = document.getElementById('assigneeSelect');
        select.innerHTML = '<option value="">-- Unassigned --</option>';

        if (projectId && projectMembers[projectId]) {
            const members = projectMembers[projectId];
            members.forEach(member => {
                const opt = document.createElement('option');
                opt.value = member.user_id;
                opt.textContent = `${member.first_name} ${member.last_name} (${member.role})`;
                if (initialAssignee && parseInt(member.user_id) === parseInt(initialAssignee)) {
                    opt.selected = true;
                }
                select.appendChild(opt);
            });
        }
    }

    document.getElementById('projectSelect').addEventListener('change', function() {
        populateAssignees(this.value);
    });

    window.addEventListener('DOMContentLoaded', () => {
        const val = document.getElementById('projectSelect').value;
        if (val) {
            populateAssignees(val);
        }
    });
</script>
