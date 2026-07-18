<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Support\Reports\ReportPeriodResolver;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesReportController extends Controller
{
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
}
