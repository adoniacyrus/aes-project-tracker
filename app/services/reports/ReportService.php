<?php

/**
 * Base report service — extend for each report type (financial, productivity, billing, etc.).
 */
abstract class ReportService
{
    protected function assertFinancialReportAccess(?string $role = null): void
    {
        if (!can_view_project_financials($role)) {
            abort_403();
        }
    }

    abstract public function getReportKey(): string;

    abstract public function getReportTitle(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function buildReportData(int $projectId, int $preparedByUserId): array;

    protected function formatReportDate(?string $date, string $fallback = 'N/A'): string
    {
        if (empty($date)) {
            return $fallback;
        }

        $timestamp = strtotime($date);

        return $timestamp ? date('M d, Y', $timestamp) : $fallback;
    }

    protected function formatReportDateTime(?string $datetime, string $fallback = 'N/A'): string
    {
        if (empty($datetime)) {
            return $fallback;
        }

        $timestamp = strtotime($datetime);

        return $timestamp ? date('M d, Y H:i:s', $timestamp) : $fallback;
    }

    protected function formatMoney($amount): string
    {
        return format_rs_currency((float)$amount, 2);
    }

    protected function buildExportFilename(string $projectCode, string $extension): string
    {
        $safeCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', $projectCode) ?: 'project';
        $timestamp = date('Ymd-His');

        return strtolower($safeCode) . '-financial-report-' . $timestamp . '.' . ltrim($extension, '.');
    }
}
