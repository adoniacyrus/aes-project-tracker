<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2 text-dark">
                    <i class="ti ti-edit fs-3 text-primary"></i>
                    <h3 class="card-title mb-0 font-weight-bold">Edit Project: <?php echo e($project['project_name']); ?></h3>
                </div>
            </div>
            
            <form action="?page=projects-edit&id=<?php echo $project['id']; ?>" method="POST" class="card-body px-4 py-4">
                <?php echo csrf_field(); ?>
                
                <div class="row g-3">
                    <!-- Project Name -->
                    <div class="col-md-8">
                        <label class="form-label font-weight-semibold text-dark required">Project Name</label>
                        <input type="text" name="project_name" class="form-control" value="<?php echo e($project['project_name']); ?>" required>
                    </div>

                    <!-- Project Code -->
                    <div class="col-md-4">
                        <label class="form-label font-weight-semibold text-dark required">Project Code</label>
                        <input type="text" name="project_code" class="form-control" value="<?php echo e($project['project_code']); ?>" required>
                        <small class="text-muted fs-8">Unique uppercase code (max 20 chars)</small>
                    </div>

                    <!-- Client Name -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Client Sponsor Name</label>
                        <input type="text" name="client_name" class="form-control" value="<?php echo e($project['client_name']); ?>">
                    </div>

                    <!-- Client Organization -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Organization Name</label>
                        <input type="text" name="organization_name" class="form-control" value="<?php echo e($project['organization_name']); ?>">
                    </div>

                    <!-- Project Type -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Project Type</label>
                        <select name="project_type" class="form-select">
                            <?php 
                            $types = ['Web Application', 'Mobile App Development', 'API Integration', 'SaaS Platform', 'Consultancy & Research'];
                            foreach ($types as $t):
                            ?>
                                <option value="<?php echo $t; ?>" <?php echo $project['project_type'] === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Priority Level</label>
                        <select name="priority" class="form-select">
                            <option value="low" <?php echo $project['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $project['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $project['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="critical" <?php echo $project['priority'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                        </select>
                    </div>

                    <!-- Technology Stack -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark">Technology Stack</label>
                        <input type="text" name="technology_stack" class="form-control" value="<?php echo e($project['technology_stack']); ?>" placeholder="e.g. PHP 8, Laravel, MySQL, Bootstrap 5">
                        <small class="text-muted fs-8">Comma-separated list of major languages, frameworks, or databases.</small>
                    </div>

                    <!-- Start Date -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $project['start_date'] ? date('Y-m-d', strtotime($project['start_date'])) : ''; ?>">
                    </div>

                    <!-- Expected End Date -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Expected End Date</label>
                        <input type="date" name="expected_end_date" class="form-control" value="<?php echo $project['expected_end_date'] ? date('Y-m-d', strtotime($project['expected_end_date'])) : ''; ?>">
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark">Project Description</label>
                        <textarea name="project_description" rows="4" class="form-control" placeholder="Summarize project scope, deliverables, and major milestones..."><?php echo e($project['project_description']); ?></textarea>
                    </div>

                    <!-- Project Status -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Project Status</label>
                        <select name="status" class="form-select">
                            <?php 
                            $statuses = ['Proposal Received', 'In Progress', 'Maintenance', 'On Hold', 'Cancelled', 'Completed'];
                            foreach ($statuses as $s):
                            ?>
                                <option value="<?php echo $s; ?>" <?php echo $project['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="?page=projects-view&id=<?php echo $project['id']; ?>" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
