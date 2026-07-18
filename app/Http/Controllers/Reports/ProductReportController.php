<?php

namespace App\Http\Controllers\Reports;

use App\Exports\ProductsExport;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportPeriodResolver;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductReportController extends Controller
{
    use ExportsReports;

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

    public function export(Request $request, ExportFormat $format): BinaryFileResponse
    {
        $period = ReportPeriodResolver::resolve($request);

        $products = $this->reports->productSales($request->user()->company_id, $period->start, $period->end);

        $filename = 'laporan-produk-'.Str::slug($period->label).'-'.now()->format('Y-m-d');

        return $this->downloadReport(new ProductsExport($products), $format, $filename);
    }
}
