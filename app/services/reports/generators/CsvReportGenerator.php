<?php

class CsvReportGenerator
{
    /**
     * @param array<string, mixed> $data
     */
    public function generate(array $data): string
    {
        $handle = fopen('php://temp', 'r+');

        // UTF-8 BOM for Excel compatibility
        fwrite($handle, "\xEF\xBB\xBF");

        $this->writeSectionTitle($handle, 'PROJECT INFORMATION');
        $project = $data['project'] ?? [];
        $this->writeRow($handle, ['Project Name', $project['project_name'] ?? '']);
        $this->writeRow($handle, ['Project Code', $project['project_code'] ?? '']);
        $this->writeRow($handle, ['Client', $project['client_name'] ?? '']);
        $this->writeRow($handle, ['Organization', $project['organization_name'] ?? '']);
        $this->writeRow($handle, ['Project Status', $project['status'] ?? '']);
        $this->writeRow($handle, ['Project Cost', $this->money($project['project_cost'] ?? 0)]);
        $this->writeRow($handle, ['Start Date', $project['start_date'] ?? 'N/A']);
        $this->writeRow($handle, ['Expected End Date', $project['expected_end_date'] ?? 'N/A']);
        $this->writeRow($handle, ['Generated On', $data['meta']['generated_on'] ?? date('M d, Y H:i:s')]);
        $this->writeBlank($handle);

        $summary = $data['financial_summary'] ?? [];
        $this->writeSectionTitle($handle, 'FINANCIAL SUMMARY');
        $this->writeRow($handle, ['Original Project Cost', $this->money($summary['original_project_cost'] ?? 0)]);
        $this->writeRow($handle, ['Current Project Cost', $this->money($summary['current_project_cost'] ?? 0)]);
        $this->writeRow($handle, ['Total Approved Ticket Cost', $this->money($summary['total_approved_ticket_cost'] ?? 0)]);
        $this->writeRow($handle, ['Total Tickets', (string)($summary['total_tickets'] ?? 0)]);
        $this->writeRow($handle, ['Completed Tickets', (string)($summary['completed_tickets'] ?? 0)]);
        $this->writeRow($handle, ['Pending Tickets', (string)($summary['pending_tickets'] ?? 0)]);
        $this->writeBlank($handle);

        $this->writeSectionTitle($handle, 'TICKET SUMMARY');
        $this->writeRow($handle, ['Ticket ID', 'Ticket Title', 'Category', 'Status', 'Current Ticket Cost']);
        foreach ($data['tickets'] ?? [] as $ticket) {
            $this->writeRow($handle, [
                (string)($ticket['id'] ?? ''),
                $ticket['title'] ?? '',
                $ticket['category'] ?? '',
                $ticket['status'] ?? '',
                $ticket['current_cost'] !== null ? $this->money($ticket['current_cost']) : 'Not set',
            ]);
        }
        $this->writeBlank($handle);

        $this->writeSectionTitle($handle, 'COST REVISION HISTORY');
        $revisions = $data['cost_revisions'] ?? [];
        if (empty($revisions)) {
            $this->writeRow($handle, ['No cost revisions recorded.']);
        } else {
            $this->writeRow($handle, [
                'Ticket',
                'Revision Number',
                'Old Cost',
                'New Cost',
                'Difference',
                'Reason',
                'Changed By',
                'Changed At',
            ]);
            foreach ($revisions as $revision) {
                $ticketLabel = '#' . ($revision['ticket_id'] ?? '') . ' - ' . ($revision['ticket_title'] ?? '');
                $this->writeRow($handle, [
                    $ticketLabel,
                    (string)($revision['revision_number'] ?? ''),
                    $revision['old_cost'] !== null ? $this->money($revision['old_cost']) : 'N/A',
                    $this->money($revision['new_cost'] ?? 0),
                    $this->money($revision['difference'] ?? 0),
                    $revision['reason'] !== '' ? $revision['reason'] : '—',
                    $revision['changed_by'] ?? '',
                    $revision['changed_at'] ?? '',
                ]);
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content === false ? '' : $content;
    }

    private function writeSectionTitle($handle, string $title): void
    {
        fputcsv($handle, [$title]);
    }

    private function writeRow($handle, array $row): void
    {
        fputcsv($handle, $row);
    }

    private function writeBlank($handle): void
    {
        fputcsv($handle, []);
    }

    private function money($amount): string
    {
        return format_rs_currency((float)$amount, 2);
    }
}
