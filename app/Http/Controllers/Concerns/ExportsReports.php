<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

trait ExportsReports
{
    /**
     * Streams $export to the browser in the requested format. The single
     * chokepoint every report's export() action goes through.
     */
    private function downloadReport(ReportExport $export, ExportFormat $format, string $baseFilename): Response
    {
        return match ($format) {
            ExportFormat::Csv, ExportFormat::Xlsx => Excel::download(
                $export->spreadsheet, "{$baseFilename}.{$format->value}", $format->writerType()
            ),
            ExportFormat::Pdf => Pdf::loadView($export->pdfView, $export->pdfData)
                ->setPaper('a4')
                ->download("{$baseFilename}.pdf"),
        };
    }
}
