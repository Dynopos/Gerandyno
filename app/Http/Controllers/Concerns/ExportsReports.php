<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Reports\ExportFormat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait ExportsReports
{
    /**
     * Streams $exportable to the browser as a CSV/XLSX download. The single
     * chokepoint every report's export() action goes through, so a future
     * PDF format only needs a new match arm here.
     */
    private function downloadReport(FromCollection $exportable, ExportFormat $format, string $baseFilename): BinaryFileResponse
    {
        return Excel::download($exportable, "{$baseFilename}.{$format->value}", $format->writerType());
    }
}
