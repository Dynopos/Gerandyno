<?php

namespace App\Http\Controllers\Reports;

use App\Exports\PnlExport;
use App\Http\Controllers\Concerns\EmailsReports;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use App\Support\Reports\ReportPeriod;
use App\Support\Reports\ReportPeriodResolver;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * A simple profit & loss statement: total sales (from synced SalesPlay
 * receipts) minus total expenses (manually entered by the customer),
 * broken down by expense category. Does not account for cost of goods
 * sold — this is net sales less overhead, not gross margin.
 */
class PnlReportController extends Controller
{
    use EmailsReports, ExportsReports;

    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $period = ReportPeriodResolver::resolve($request);
        $data = $this->buildData($request, $period);

        return view('reports.pnl', [
            'period' => $period,
            'filterOptions' => ReportPeriodResolver::options(),
            ...$data,
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
            __('app.reports.pnl.title'),
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
        $data = $this->buildData($request, $period);

        $export = new ReportExport(
            spreadsheet: new PnlExport($data['totalSales'], $data['totalTax'], $data['netSales'], $data['expensesByCategory'], $data['totalExpenses'], $data['netProfit']),
            pdfView: 'exports.pdf.pnl',
            pdfData: [
                'title' => __('app.reports.pnl.title'),
                'subtitle' => $period->label,
                'companyName' => $request->user()->company->name,
                ...$data,
            ],
        );

        return [$export, 'penyata-untung-rugi-'.$period->key.'-'.now()->format('Y-m-d')];
    }

    /**
     * @return array{totalSales: float, totalTax: float, netSales: float, expensesByCategory: Collection<int, array{category: string, total: float}>, totalExpenses: float, netProfit: float}
     */
    private function buildData(Request $request, ReportPeriod $period): array
    {
        $totalSales = $this->reports->totalBetween($period->start, $period->end);
        $totalTax = $this->reports->taxBetween($period->start, $period->end);

        $expensesByCategory = Expense::query()
            ->whereBetween('expense_date', [$period->start, $period->end])
            ->groupBy('category')
            ->selectRaw('category, SUM(amount) as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['category' => $row->category, 'total' => (float) $row->total]);

        $totalExpenses = (float) $expensesByCategory->sum('total');

        // Sales are recorded tax-inclusive (what the customer paid). Whether
        // the tax inside them is income depends on the shop, and nothing in
        // the receipts says which: a registered business collects SST on the
        // government's behalf, so it must come out before profit; an
        // unregistered one keeps it, and deducting it would understate their
        // earnings just as counting it overstates a registered shop's.
        $totalTax = $request->user()->company->sst_registered ? $totalTax : 0.0;
        $netSales = $totalSales - $totalTax;

        return [
            'totalSales' => $totalSales,
            'totalTax' => $totalTax,
            'netSales' => $netSales,
            'expensesByCategory' => $expensesByCategory,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $netSales - $totalExpenses,
        ];
    }
}
