<?php echo csrf_field(); ?>
<div class="row g-3">
    <div class="col-md-12">
        <label class="form-label required">Project Name</label>
        <input type="text" name="project_name" class="form-control" placeholder="e.g. AES Project Management System" required>
        <small class="text-muted fs-8">Project code will be generated automatically from the project name.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Client Name</label>
        <input type="text" name="client_name" class="form-control" placeholder="e.g. Mark Spencer">
    </div>
    <div class="col-md-6">
        <label class="form-label">Organization Name</label>
        <input type="text" name="organization_name" class="form-control" placeholder="e.g. Acme Corporation">
    </div>
    <div class="col-md-6">
        <label class="form-label">Project Type</label>
        <select name="project_type" class="form-select">
            <option value="Web Application" selected>Web Application</option>
            <option value="Mobile App Development">Mobile App Development</option>
            <option value="API Integration">API Integration</option>
            <option value="SaaS Platform">SaaS Platform</option>
            <option value="Consultancy & Research">Consultancy & Research</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Technology Stack</label>
        <input type="text" name="technology_stack" class="form-control" placeholder="e.g. PHP 8, Laravel, MySQL, Bootstrap 5">
        <small class="text-muted fs-8">Comma-separated list of major languages, frameworks, or databases.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Start Date</label>
        <input type="date" name="start_date" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Expected End Date</label>
        <input type="date" name="expected_end_date" class="form-control">
    </div>
    <div class="col-12">
        <label class="form-label">Project Description</label>
        <textarea name="project_description" rows="4" class="form-control" placeholder="Summarize project scope, deliverables, and major milestones..."></textarea>
    </div>
    <div class="col-md-6">
        <label class="form-label required">Project Cost</label>
        <div class="input-group">
            <span class="input-group-text">Rs.</span>
            <input type="number" name="project_cost" class="form-control" placeholder="150000" min="0.01" step="0.01" required>
        </div>
    </div>
    <div class="col-md-6">
        <label class="form-label">Project Status</label>
        <select name="status" class="form-select">
            <?php $selected = ''; require __DIR__ . '/_status_options.php'; ?>
        </select>
    </div>
</div>
