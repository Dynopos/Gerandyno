<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\Reports\ReportPeriodResolver;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReportController extends Controller
{
    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $period = ReportPeriodResolver::resolve($request);

        $products = $this->reports->productSales($request->user()->company_id, $period->start, $period->end);

        return view('reports.products', [
            'period' => $period,
            'products' => $products,
            'filterOptions' => ReportPeriodResolver::options(),
        ]);
    }
}
