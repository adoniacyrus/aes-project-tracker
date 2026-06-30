<?php
/** @var array $project */
$project = $project ?? [];
?>
<div class="card mb-4 shadow-sm border border-light" id="project-financial-reports-card">
    <div class="card-header bg-transparent border-bottom py-3 px-4">
        <i class="ti ti-report-analytics text-primary me-2 fs-4"></i> Project Financial Reports
    </div>
    <div class="card-body px-4 py-3">
        <p class="text-secondary fs-7 mb-3">Generate professional financial reports with ticket cost audit history.</p>
        <div class="d-flex flex-wrap gap-2">
            <button type="button"
                    class="btn btn-outline-primary btn-sm project-financial-export-btn"
                    data-export-url="<?php echo e(route('projects-financial-report-csv', ['project_code' => $project['project_code']])); ?>">
                <i class="ti ti-file-spreadsheet me-1"></i> Export CSV
            </button>
            <button type="button"
                    class="btn btn-primary btn-sm project-financial-export-btn"
                    data-export-url="<?php echo e(route('projects-financial-report-pdf', ['project_code' => $project['project_code']])); ?>">
                <i class="ti ti-file-type-pdf me-1"></i> Export PDF
            </button>
        </div>
    </div>
</div>
