<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Laporan SST: ringkasan nilai perkhidmatan bercukai dan SST dikutip
 * untuk membantu penyediaan Penyata SST-02 (portal MySST).
 *
 * Receipt totals are tax-inclusive (see SalesReportService::taxBetween),
 * so the taxable service value reported to Customs is total - tax.
 * Tenant isolation comes from Receipt's CompanyScope global scope.
 */
class SstReportController extends Controller
{
    private const MAX_MONTHS = 12;

    public function index(Request $request): View
    {
        [$from, $until] = $this->resolvePeriod($request);

        $months = $this->monthlyRows($from, $until);

        return view('reports.sst', [
            'company' => $request->user()->company,
            'months' => $months,
            'from' => $from,
            'until' => $until,
            'totalNilai' => $months->sum('nilai'),
            'totalSst' => $months->sum('sst'),
            'totalKutipan' => $months->sum('total'),
            'totalTransaksi' => $months->sum('transactions'),
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolvePeriod(Request $request): array
    {
        $from = $this->parseMonth($request->query('dari')) ?? CarbonImmutable::now()->subMonth()->startOfMonth();
        $until = $this->parseMonth($request->query('hingga')) ?? CarbonImmutable::now()->startOfMonth();

        if ($until->lessThan($from)) {
            [$from, $until] = [$until, $from];
        }

        // Guard against an absurdly wide range from a hand-edited URL.
        if ($from->diffInMonths($until) >= self::MAX_MONTHS) {
            $from = $until->subMonths(self::MAX_MONTHS - 1);
        }

        return [$from, $until];
    }

    private function parseMonth(?string $value): ?CarbonImmutable
    {
        if ($value === null || preg_match('/^\d{4}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $month = CarbonImmutable::createFromFormat('Y-m', $value);

        return $month === false ? null : $month->startOfMonth();
    }

    /**
     * @return Collection<int, array{label: string, month: string, transactions: int, nilai: float, sst: float, total: float}>
     */
    private function monthlyRows(CarbonImmutable $from, CarbonImmutable $until): Collection
    {
        $rows = collect();

        for ($month = $from; $month->lessThanOrEqualTo($until); $month = $month->addMonth()) {
            $receipts = Receipt::whereBetween('transaction_date', [$month->startOfMonth(), $month->endOfMonth()])
                ->select('total', 'tax')
                ->get();

            $total = (float) $receipts->sum('total');
            $sst = (float) $receipts->sum('tax');

            $rows->push([
                'label' => $month->translatedFormat('F Y'),
                'month' => $month->format('Y-m'),
                'transactions' => $receipts->count(),
                'nilai' => $total - $sst,
                'sst' => $sst,
                'total' => $total,
            ]);
        }

        return $rows;
    }
}
