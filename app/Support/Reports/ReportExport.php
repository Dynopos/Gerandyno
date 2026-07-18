<?php

namespace App\Support\Reports;

use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Bundles the two representations a report export needs: a Laravel Excel
 * export object (for csv/xlsx) and a Blade view + data (for pdf). A report
 * controller builds one of these once; ExportsReports::downloadReport()
 * picks whichever side it needs based on the requested ExportFormat.
 */
final readonly class ReportExport
{
    /**
     * @param  array<string, mixed>  $pdfData
     */
    public function __construct(
        public FromCollection $spreadsheet,
        public string $pdfView,
        public array $pdfData,
    ) {}
}
