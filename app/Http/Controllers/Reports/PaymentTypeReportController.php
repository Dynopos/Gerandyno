<?php

namespace App\Http\Controllers\Reports;

use App\Exports\PaymentTypesExport;
use App\Http\Controllers\Concerns\EmailsReports;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use App\Support\Reports\ReportPeriodResolver;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PaymentTypeReportController extends Controller
{
    use EmailsReports, ExportsReports;

    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $period = ReportPeriodResolver::resolve($request);

        $paymentTypes = $this->reports->paymentTypeSales($request->user()->company_id, $period->start, $period->end);

        return view('reports.payment-types', [
            'period' => $period,
            'paymentTypes' => $paymentTypes,
            'filterOptions' => ReportPeriodResolver::options(),
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
            __('app.reports.payment_types.title'),
            $export->pdfData['subtitle'],
        );

        return back()->with('status', __('app.email_report.sent', ['email' => $request->user()->email]));
    }

    /**
     * @return array{0: ReportExport, 1: string}
     */
    private function buildExport(Request $request): array
    {
        $period = ReportPeriodResolver::resolve($request);

        $paymentTypes = $this->reports->paymentTypeSales($request->user()->company_id, $period->start, $period->end);

        $filename = 'laporan-jenis-bayaran-'.Str::slug($period->label).'-'.now()->format('Y-m-d');

        $export = new ReportExport(
            spreadsheet: new PaymentTypesExport($paymentTypes),
            pdfView: 'exports.pdf.payment-types',
            pdfData: [
                'title' => __('app.reports.payment_types.title'),
                'subtitle' => $period->label,
                'companyName' => $request->user()->company->name,
                'paymentTypes' => $paymentTypes,
            ],
        );

        return [$export, $filename];
    }
}
