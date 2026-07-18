<?php

namespace App\Http\Controllers\Reports;

use App\Exports\YearlyExport;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\SalesReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class YearlyReportController extends Controller
{
    use ExportsReports;

    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(): View
    {
        $years = $this->reports->yearlySeries();

        return view('reports.yearly', [
            'years' => $years,
            'grandTotal' => $years->sum('total'),
            'grandTransactions' => $years->sum('transactions'),
        ]);
    }

    public function export(ExportFormat $format): BinaryFileResponse
    {
        $years = $this->reports->yearlySeries();

        return $this->downloadReport(new YearlyExport($years), $format, 'laporan-tahunan-'.now()->format('Y-m-d'));
    }
}
