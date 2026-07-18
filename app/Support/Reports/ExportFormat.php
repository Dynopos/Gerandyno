<?php

namespace App\Support\Reports;

use Maatwebsite\Excel\Excel;

/**
 * Supported report export formats. Each report controller's export() action
 * builds a ReportExport and hands it, together with this enum, to
 * App\Http\Controllers\Concerns\ExportsReports::downloadReport() — the one
 * place that branches on format. Adding a new format is just a new case
 * here plus a matching branch there.
 */
enum ExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';
    case Pdf = 'pdf';

    public function writerType(): ?string
    {
        return match ($this) {
            self::Csv => Excel::CSV,
            self::Xlsx => Excel::XLSX,
            self::Pdf => null,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
