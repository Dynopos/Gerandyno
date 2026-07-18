<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyReportController extends Controller
{
    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $availableYears = $this->reports->availableYears();

        $year = (int) $request->integer('year', now()->year);

        if (! $availableYears->contains($year)) {
            $year = $availableYears->first();
        }

        $months = $this->reports->monthlySeries($year);

        return view('reports.monthly', [
            'year' => $year,
            'months' => $months,
            'yearTotal' => $months->sum('total'),
            'yearTransactions' => $months->sum('transactions'),
            'availableYears' => $availableYears,
        ]);
    }
}
