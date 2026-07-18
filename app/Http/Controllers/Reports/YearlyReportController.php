<?php

namespace App\Http\Controllers\Reports;

use App\Exports\YearlyExport;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

    public function export(Request $request, ExportFormat $format): Response
    {
        $years = $this->reports->yearlySeries();

        $export = new ReportExport(
            spreadsheet: new YearlyExport($years),
            pdfView: 'exports.pdf.yearly',
            pdfData: [
                'title' => __('app.reports.yearly.title'),
                'subtitle' => null,
                'companyName' => $request->user()->company->name,
                'years' => $years,
            ],
        );

        return $this->downloadReport($export, $format, 'laporan-tahunan-'.now()->format('Y-m-d'));
    }
}
