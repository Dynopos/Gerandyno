<?php

namespace App\Http\Controllers\Reports;

use App\Exports\ProductsExport;
use App\Http\Controllers\Concerns\ExportsReports;
use App\Http\Controllers\Controller;
use App\Support\Reports\ExportFormat;
use App\Support\Reports\ReportExport;
use App\Support\Reports\ReportPeriodResolver;
use App\Support\Reports\SalesReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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

    public function export(Request $request, ExportFormat $format): Response
    {
        $period = ReportPeriodResolver::resolve($request);

        $products = $this->reports->productSales($request->user()->company_id, $period->start, $period->end);

        $filename = 'laporan-produk-'.Str::slug($period->label).'-'.now()->format('Y-m-d');

        $export = new ReportExport(
            spreadsheet: new ProductsExport($products),
            pdfView: 'exports.pdf.products',
            pdfData: [
                'title' => __('app.reports.products.title'),
                'subtitle' => $period->label,
                'companyName' => $request->user()->company->name,
                'products' => $products,
            ],
        );

        return $this->downloadReport($export, $format, $filename);
    }
}
