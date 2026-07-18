<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Support\Reports\SalesReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SalesReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('dashboard-admin');
        }

        $today = CarbonImmutable::today();
        $yesterday = $today->subDay();

        $todaySales = $this->reports->totalBetween($today->startOfDay(), $today->endOfDay());
        $todayTransactions = $this->reports->countBetween($today->startOfDay(), $today->endOfDay());
        $yesterdaySales = $this->reports->totalBetween($yesterday->startOfDay(), $yesterday->endOfDay());

        $monthSales = $this->reports->totalBetween($today->startOfMonth(), $today->endOfMonth());
        $yearSales = $this->reports->totalBetween($today->startOfYear(), $today->endOfYear());

        $dailySales = $this->reports->dailySeries($today->startOfMonth());
        $monthlySales = $this->reports->monthlySeries($today->year);
        $topCategories = $this->reports->categorySales($user->company_id, $today->startOfMonth(), $today->endOfMonth());

        $recentReceipts = Receipt::with('payments')
            ->latest('transaction_date')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'todaySales' => $todaySales,
            'todayTransactions' => $todayTransactions,
            'todayDelta' => $this->percentDelta($todaySales, $yesterdaySales),
            'monthSales' => $monthSales,
            'yearSales' => $yearSales,
            'dailySales' => $dailySales,
            'monthlySales' => $monthlySales,
            'topCategories' => $topCategories,
            'recentReceipts' => $recentReceipts,
        ]);
    }

    private function percentDelta(float $current, float $previous): ?float
    {
        if ($previous <= 0.0) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }
}
