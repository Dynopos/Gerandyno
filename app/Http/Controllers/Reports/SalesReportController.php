<?php

namespace App\Http\Controllers\Reports;

use App\Exports\SalesExport;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use App\Support\Reports\ReportPeriodResolver;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SalesReportController extends Controller
{
    use ExportsReports;

    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $period = ReportPeriodResolver::resolve($request);

        $receipts = Receipt::with('payments')
            ->whereBetween('transaction_date', [$period->start, $period->end])
            ->orderByDesc('transaction_date')
            ->paginate(20)
            ->withQueryString();

        return view('reports.sales.index', [
            'period' => $period,
            'receipts' => $receipts,
            'periodTotal' => $this->reports->totalBetween($period->start, $period->end),
            'periodCount' => $this->reports->countBetween($period->start, $period->end),
            'filterOptions' => ReportPeriodResolver::options(),
        ]);
    }

    public function show(Receipt $receipt): View
    {
        $this->authorize('view', $receipt);

        $receipt->load(['items.product', 'payments', 'salesplayAccount']);

        return view('reports.sales.show', [
            'receipt' => $receipt,
        ]);
    }

    public function export(Request $request, ExportFormat $format): Response
    {
        $period = ReportPeriodResolver::resolve($request);

        $receipts = Receipt::with('payments')
            ->whereBetween('transaction_date', [$period->start, $period->end])
            ->orderByDesc('transaction_date')
            ->get();

        $filename = 'laporan-jualan-'.Str::slug($period->label).'-'.now()->format('Y-m-d');

        $export = new ReportExport(
            spreadsheet: new SalesExport($receipts),
            pdfView: 'exports.pdf.sales',
            pdfData: [
                'title' => __('app.reports.sales.title'),
                'subtitle' => $period->label,
                'companyName' => $request->user()->company->name,
                'receipts' => $receipts,
            ],
        );

        return $this->downloadReport($export, $format, $filename);
    }
}
