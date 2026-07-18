<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\Reports\SalesReportService;
use Illuminate\View\View;

class YearlyReportController extends Controller
{
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
}
