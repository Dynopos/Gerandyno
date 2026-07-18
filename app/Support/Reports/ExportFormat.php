<?php

namespace App\Support\Reports;

use Maatwebsite\Excel\Excel;

/**
 * Supported report export formats. Adding PDF later is a matter of adding a
 * `Pdf` case here and a matching branch in
 * App\Http\Controllers\Concerns\ExportsReports::downloadReport() — every
 * report controller already funnels through that one method, so nothing
 * else needs to change.
 */
enum ExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public function writerType(): string
    {
        return match ($this) {
            self::Csv => Excel::CSV,
            self::Xlsx => Excel::XLSX,
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
