<div class="row row-cards mb-4">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card shadow-sm border border-light">
            <div class="card-header bg-transparent border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-2 text-dark">
                    <i class="ti ti-folder-plus fs-3 text-primary"></i>
                    <h3 class="card-title mb-0 font-weight-bold">Create New Project</h3>
                </div>
            </div>
            
            <form action="?page=projects-create" method="POST" class="card-body px-4 py-4">
                <?php echo csrf_field(); ?>
                
                <div class="row g-3">
                    <!-- Project Name -->
                    <div class="col-md-8">
                        <label class="form-label font-weight-semibold text-dark required">Project Name</label>
                        <input type="text" name="project_name" class="form-control" placeholder="e.g. Acme Corp Portal Integration" required>
                    </div>

                    <!-- Project Code -->
                    <div class="col-md-4">
                        <label class="form-label font-weight-semibold text-dark required">Project Code</label>
                        <input type="text" name="project_code" class="form-control" placeholder="e.g. ACM-PORT" required>
                        <small class="text-muted fs-8">Unique uppercase code (max 20 chars)</small>
                    </div>

                    <!-- Client Name -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Client Sponsor Name</label>
                        <input type="text" name="client_name" class="form-control" placeholder="e.g. Mark Spencer">
                    </div>

                    <!-- Client Organization -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Organization Name</label>
                        <input type="text" name="organization_name" class="form-control" placeholder="e.g. Acme Corporation">
                    </div>

                    <!-- Project Type -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Project Type</label>
                        <select name="project_type" class="form-select">
                            <option value="Web Application">Web Application</option>
                            <option value="Mobile App Development">Mobile App Development</option>
                            <option value="API Integration">API Integration</option>
                            <option value="SaaS Platform">SaaS Platform</option>
                            <option value="Consultancy & Research">Consultancy & Research</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Priority Level</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <!-- Technology Stack -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark">Technology Stack</label>
                        <input type="text" name="technology_stack" class="form-control" placeholder="e.g. PHP 8, Laravel, MySQL, Bootstrap 5">
                        <small class="text-muted fs-8">Comma-separated list of major languages, frameworks, or databases.</small>
                    </div>

                    <!-- Start Date -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Start Date</label>
                        <input type="date" name="start_date" class="form-control">
                    </div>

                    <!-- Expected End Date -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Expected End Date</label>
                        <input type="date" name="expected_end_date" class="form-control">
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label class="form-label font-weight-semibold text-dark">Project Description</label>
                        <textarea name="project_description" rows="4" class="form-control" placeholder="Summarize project scope, deliverables, and major milestones..."></textarea>
                    </div>

                    <!-- Initial Status -->
                    <div class="col-md-6">
                        <label class="form-label font-weight-semibold text-dark">Project Status</label>
                        <select name="status" class="form-select">
                            <option value="Proposal Received" selected>Proposal Received</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4 text-muted">

                <div class="d-flex align-items-center justify-content-end gap-2">
                    <a href="?page=projects" class="btn btn-outline-secondary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
