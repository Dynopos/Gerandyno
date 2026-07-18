<?php

namespace App\Http\Controllers\Reports;

use App\Exports\YearlyExport;
use App\Http\Controllers\Concerns\EmailsReports;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class YearlyReportController extends Controller
{
    use EmailsReports, ExportsReports;

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
        [$export, $filename] = $this->buildExport($request);

        return $this->downloadReport($export, $format, $filename);
    }

    public function email(Request $request): RedirectResponse
    {
        [$export, $filename] = $this->buildExport($request);

        $this->emailReport(
            $export,
            ExportFormat::Pdf,
            $filename,
            $request->user(),
            __('app.reports.yearly.title'),
            __('app.reports.yearly.all_time'),
        );

        return back()->with('status', __('app.email_report.sent', ['email' => $request->user()->email]));
    }

    /**
     * @return array{0: ReportExport, 1: string}
     */
    private function buildExport(Request $request): array
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

        return [$export, 'laporan-tahunan-'.now()->format('Y-m-d')];
    }
}
