<?php

namespace App\Http\Controllers\Reports;

use App\Exports\MonthlyExport;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MonthlyReportController extends Controller
{
    use ExportsReports;

    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $year = $this->resolveYear($request);
        $months = $this->reports->monthlySeries($year);

        return view('reports.monthly', [
            'year' => $year,
            'months' => $months,
            'yearTotal' => $months->sum('total'),
            'yearTransactions' => $months->sum('transactions'),
            'availableYears' => $this->reports->availableYears(),
        ]);
    }

    public function export(Request $request, ExportFormat $format): BinaryFileResponse
    {
        $year = $this->resolveYear($request);
        $months = $this->reports->monthlySeries($year);

        return $this->downloadReport(new MonthlyExport($months), $format, "laporan-bulanan-{$year}");
    }

    private function resolveYear(Request $request): int
    {
        $availableYears = $this->reports->availableYears();
        $year = (int) $request->integer('year', now()->year);

        return $availableYears->contains($year) ? $year : $availableYears->first();
    }
}
