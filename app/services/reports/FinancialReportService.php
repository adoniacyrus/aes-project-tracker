<?php

require_once __DIR__ . '/ReportService.php';
require_once __DIR__ . '/generators/CsvReportGenerator.php';
require_once __DIR__ . '/generators/PdfReportGenerator.php';
require_once __DIR__ . '/../../models/ProjectModel.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/TicketCostHistoryModel.php';

class FinancialReportService extends ReportService
{
    private ProjectModel $projectModel;
    private UserModel $userModel;
    private TicketCostHistoryModel $costHistoryModel;
    private CsvReportGenerator $csvGenerator;
    private PdfReportGenerator $pdfGenerator;

    public function __construct()
    {
        $this->projectModel = new ProjectModel();
        $this->userModel = new UserModel();
        $this->costHistoryModel = new TicketCostHistoryModel();
        $this->csvGenerator = new CsvReportGenerator();
        $this->pdfGenerator = new PdfReportGenerator();
    }

    public function getReportKey(): string
    {
        return 'project_financial';
    }

    public function getReportTitle(): string
    {
        return 'Project Financial Report';
    }

    public function buildReportData(int $projectId, int $preparedByUserId): array
    {
        $project = $this->projectModel->findById($projectId);
        if (!$project) {
            throw new RuntimeException('Project not found.');
        }

        $preparedBy = $this->userModel->findById($preparedByUserId);
        $ticketStats = $this->projectModel->getProjectTicketSummaryStats($projectId);
        $tickets = $this->projectModel->getProjectTicketsForFinancialReport($projectId);
        $revisions = $this->costHistoryModel->getRevisionHistoryForProject($projectId);
        $totalApprovedTicketCost = $this->projectModel->getTotalApprovedTicketRevenue($projectId);
        $projectCost = (float)($project['project_cost'] ?? 0);

        return [
            'meta' => [
                'report_key' => $this->getReportKey(),
                'report_title' => $this->getReportTitle(),
                'generated_on' => date('M d, Y H:i:s'),
                'prepared_by' => $preparedBy['full_name'] ?? 'System User',
                'prepared_by_email' => $preparedBy['email'] ?? '',
            ],
            'project' => [
                'id' => (int)$project['id'],
                'project_name' => $project['project_name'] ?? '',
                'project_code' => $project['project_code'] ?? '',
                'client_name' => $project['client_name'] ?? '',
                'organization_name' => $project['organization_name'] ?? '',
                'status' => project_display_status($project['status'] ?? ''),
                'project_cost' => $projectCost,
                'start_date' => $this->formatReportDate($project['start_date'] ?? null),
                'expected_end_date' => $this->formatReportDate($project['expected_end_date'] ?? null),
            ],
            'financial_summary' => [
                'original_project_cost' => $projectCost,
                'current_project_cost' => $projectCost,
                'total_approved_ticket_cost' => $totalApprovedTicketCost,
                'total_tickets' => (int)$ticketStats['total'],
                'completed_tickets' => (int)$ticketStats['completed'],
                'pending_tickets' => (int)$ticketStats['pending'],
                'processing_tickets' => (int)$ticketStats['processing'],
            ],
            'tickets' => array_map(function (array $ticket) {
                return [
                    'id' => (int)$ticket['id'],
                    'title' => $ticket['title'] ?? '',
                    'category' => $ticket['category'] ?? '',
                    'status' => $ticket['display_status'] ?? ticket_display_status($ticket),
                    'current_cost' => ($ticket['estimated_cost'] !== null && $ticket['estimated_cost'] !== '')
                        ? (float)$ticket['estimated_cost']
                        : null,
                ];
            }, $tickets),
            'cost_revisions' => array_map(function (array $row) {
                return [
                    'ticket_id' => (int)$row['ticket_id'],
                    'ticket_title' => $row['ticket_title'] ?? '',
                    'revision_number' => (int)$row['revision_number'],
                    'old_cost' => ($row['old_cost'] !== null && $row['old_cost'] !== '') ? (float)$row['old_cost'] : null,
                    'new_cost' => (float)$row['new_cost'],
                    'difference' => (float)$row['difference'],
                    'reason' => trim((string)($row['reason'] ?? '')),
                    'changed_by' => $row['changed_by_name'] ?? '',
                    'changed_at' => $this->formatReportDateTime($row['changed_at'] ?? null),
                ];
            }, $revisions),
        ];
    }

    /**
     * @return array{content: string, filename: string, mime: string}
     */
    public function exportCsv(int $projectId, int $preparedByUserId): array
    {
        $this->assertFinancialReportAccess();
        $data = $this->buildReportData($projectId, $preparedByUserId);

        return [
            'content' => $this->csvGenerator->generate($data),
            'filename' => $this->buildExportFilename($data['project']['project_code'], 'csv'),
            'mime' => 'text/csv; charset=UTF-8',
        ];
    }

    /**
     * @return array{content: string, filename: string, mime: string}
     */
    public function exportPdf(int $projectId, int $preparedByUserId): array
    {
        $this->assertFinancialReportAccess();
        $data = $this->buildReportData($projectId, $preparedByUserId);

        return [
            'content' => $this->pdfGenerator->generate($data),
            'filename' => $this->buildExportFilename($data['project']['project_code'], 'pdf'),
            'mime' => 'application/pdf',
        ];
    }
}
