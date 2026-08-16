<?php
/** @var array<string, mixed> $data */
$data = $data ?? [];
$meta = $data['meta'] ?? [];
$project = $data['project'] ?? [];
$summary = $data['financial_summary'] ?? [];
$tickets = $data['tickets'] ?? [];
$revisions = $data['cost_revisions'] ?? [];

$money = function ($amount) {
    return htmlspecialchars(format_rs_currency((float)$amount, 2), ENT_QUOTES, 'UTF-8');
};
$e = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $e($meta['report_title'] ?? 'Project Financial Report'); ?></title>
    <style>
        @page { margin: 28mm 16mm 22mm 16mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1d273b;
            line-height: 1.45;
        }
        .report-header {
            border-bottom: 2px solid #206bc4;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .brand-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .brand-mark {
            display: table-cell;
            vertical-align: middle;
            width: 52px;
        }
        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: #206bc4;
            color: #fff;
            text-align: center;
            line-height: 44px;
            font-weight: bold;
            font-size: 14px;
        }
        .brand-text {
            display: table-cell;
            vertical-align: middle;
            padding-left: 10px;
        }
        .brand-text .app-name {
            font-size: 13px;
            font-weight: bold;
            color: #206bc4;
        }
        .brand-text .report-type {
            font-size: 11px;
            color: #5c6b7a;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
            color: #1d273b;
        }
        .meta-line {
            color: #5c6b7a;
            font-size: 9px;
        }
        h2 {
            font-size: 12px;
            color: #206bc4;
            border-bottom: 1px solid #e6e8eb;
            padding-bottom: 4px;
            margin: 18px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.info-table,
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.info-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        table.info-table td.label {
            width: 34%;
            color: #5c6b7a;
            font-weight: bold;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #dfe3e8;
            padding: 5px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data-table th {
            background: #f4f7fb;
            color: #334155;
            font-size: 9px;
            text-transform: uppercase;
        }
        .summary-box {
            background: #f8fafc;
            border: 1px solid #e6e8eb;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            width: 50%;
            padding: 4px 8px 4px 0;
            vertical-align: top;
        }
        .summary-item .label {
            color: #5c6b7a;
            font-size: 9px;
            text-transform: uppercase;
        }
        .summary-item .value {
            font-size: 11px;
            font-weight: bold;
            color: #1d273b;
        }
        .summary-item .value.success {
            color: #2fb344;
        }
        .empty-note {
            color: #6a7c9f;
            font-style: italic;
            padding: 8px 0;
        }
        .report-footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e6e8eb;
            font-size: 9px;
            color: #6a7c9f;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="brand-row">
            <div class="brand-mark">
                <div class="brand-logo">AES</div>
            </div>
            <div class="brand-text">
                <div class="app-name">AES Project Tracker</div>
                <div class="report-type">Project Financial Report</div>
            </div>
        </div>
        <h1><?php echo $e($project['project_name'] ?? 'Project'); ?></h1>
        <div class="meta-line">
            <?php echo $e($project['project_code'] ?? ''); ?>
            &nbsp;|&nbsp; Generated: <?php echo $e($meta['generated_on'] ?? ''); ?>
            &nbsp;|&nbsp; Prepared By: <?php echo $e($meta['prepared_by'] ?? ''); ?>
        </div>
    </div>

    <h2>Project Information</h2>
    <table class="info-table">
        <tr><td class="label">Project Name</td><td><?php echo $e($project['project_name'] ?? ''); ?></td></tr>
        <tr><td class="label">Project Code</td><td><?php echo $e($project['project_code'] ?? ''); ?></td></tr>
        <tr><td class="label">Client</td><td><?php echo $e($project['client_name'] ?? 'N/A'); ?></td></tr>
        <tr><td class="label">Organization</td><td><?php echo $e($project['organization_name'] ?? 'N/A'); ?></td></tr>
        <tr><td class="label">Project Status</td><td><?php echo $e($project['status'] ?? ''); ?></td></tr>
        <tr><td class="label">Project Cost</td><td><?php echo $money($project['project_cost'] ?? 0); ?></td></tr>
        <tr><td class="label">Start Date</td><td><?php echo $e($project['start_date'] ?? 'N/A'); ?></td></tr>
        <tr><td class="label">Expected End Date</td><td><?php echo $e($project['expected_end_date'] ?? 'N/A'); ?></td></tr>
    </table>

    <h2>Financial Summary</h2>
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Original Project Cost</div>
                <div class="value"><?php echo $money($summary['original_project_cost'] ?? 0); ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Current Project Cost</div>
                <div class="value"><?php echo $money($summary['current_project_cost'] ?? 0); ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Total Approved Ticket Cost</div>
                <div class="value success"><?php echo $money($summary['total_approved_ticket_cost'] ?? 0); ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Total Tickets</div>
                <div class="value"><?php echo $e((string)($summary['total_tickets'] ?? 0)); ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Completed Tickets</div>
                <div class="value"><?php echo $e((string)($summary['completed_tickets'] ?? 0)); ?></div>
            </div>
            <div class="summary-item">
                <div class="label">Pending Tickets</div>
                <div class="value"><?php echo $e((string)($summary['pending_tickets'] ?? 0)); ?></div>
            </div>
        </div>
    </div>

    <h2>Ticket Summary</h2>
    <?php if (empty($tickets)): ?>
        <p class="empty-note">No tickets recorded for this project.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Ticket Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Current Ticket Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?php echo $e((string)($ticket['id'] ?? '')); ?></td>
                        <td><?php echo $e($ticket['title'] ?? ''); ?></td>
                        <td><?php echo $e($ticket['category'] ?? ''); ?></td>
                        <td><?php echo $e($ticket['status'] ?? ''); ?></td>
                        <td><?php echo $ticket['current_cost'] !== null ? $money($ticket['current_cost']) : 'Not set'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Cost Revision History</h2>
    <?php if (empty($revisions)): ?>
        <p class="empty-note">No cost revisions recorded.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ticket</th>
                    <th>Rev #</th>
                    <th>Old Cost</th>
                    <th>New Cost</th>
                    <th>Difference</th>
                    <th>Reason</th>
                    <th>Changed By</th>
                    <th>Changed At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revisions as $revision): ?>
                    <tr>
                        <td>#<?php echo $e((string)($revision['ticket_id'] ?? '')); ?> - <?php echo $e($revision['ticket_title'] ?? ''); ?></td>
                        <td><?php echo $e((string)($revision['revision_number'] ?? '')); ?></td>
                        <td><?php echo $revision['old_cost'] !== null ? $money($revision['old_cost']) : 'N/A'; ?></td>
                        <td><?php echo $money($revision['new_cost'] ?? 0); ?></td>
                        <td><?php echo $money($revision['difference'] ?? 0); ?></td>
                        <td><?php echo $revision['reason'] !== '' ? $e($revision['reason']) : '—'; ?></td>
                        <td><?php echo $e($revision['changed_by'] ?? ''); ?></td>
                        <td><?php echo $e($revision['changed_at'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>External Work Log Summary</h2>
    <?php
        $externalWorkLogs = $data['external_work_logs'] ?? [];
        $ewlSummary = $data['external_work_log_summary'] ?? [];
    ?>
    <?php if (empty($externalWorkLogs)): ?>
        <p class="empty-note">No external work logs recorded for this project.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th>Assigned To</th>
                    <th>Communication Source</th>
                    <th>Hours Spent</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($externalWorkLogs as $ewl): ?>
                    <tr>
                        <td><?php echo $e($ewl['work_date'] ?? ''); ?></td>
                        <td><?php echo $e($ewl['title'] ?? ''); ?></td>
                        <td><?php echo $e($ewl['assigned_to'] ?? ''); ?></td>
                        <td><?php echo $e($ewl['communication_source'] ?? ''); ?></td>
                        <td><?php echo $e(number_format((float)($ewl['hours_spent'] ?? 0), 2)); ?></td>
                        <td><?php echo $e($ewl['status'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Total External Work Hours</div>
                <div class="value"><?php echo $e(number_format((float)($ewlSummary['total_hours'] ?? 0), 2)); ?></div>
            </div>
        </div>
    </div>

    <div class="report-footer">
        AES Project Tracker &mdash; Confidential financial report generated on <?php echo $e($meta['generated_on'] ?? ''); ?>.
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('DejaVu Sans');
            $pdf->page_text(500, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, array(0.35, 0.4, 0.45));
        }
    </script>
</body>
</html>
