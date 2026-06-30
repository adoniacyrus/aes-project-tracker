<?php

if (!class_exists(Dompdf\Dompdf::class)) {
    require_once __DIR__ . '/../../../../vendor/autoload.php';
}

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfReportGenerator
{
    /**
     * @param array<string, mixed> $data
     */
    public function generate(array $data): string
    {
        $html = $this->renderHtml($data);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderHtml(array $data): string
    {
        ob_start();
        require __DIR__ . '/../../../views/reports/financial_report_pdf.php';
        $html = ob_get_clean();

        return $html === false ? '' : $html;
    }
}
