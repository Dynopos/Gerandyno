<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shift;
use DateTimeInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * SalesPlay doesn't expose its own "Shifts" (terminal cash sessions) report
 * through the public Developer API, so this is a self-contained equivalent:
 * the cashier explicitly starts and ends a shift within DynoPOS itself, and
 * "expected cash" is computed from cash payments recorded (via the normal
 * SalesPlay receipt sync) between those two timestamps.
 */
class ShiftController extends Controller
{
    private const CASH_METHOD = 'cash';

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $currentShift = Shift::whereNull('closed_at')->latest('opened_at')->first();

        $shifts = Shift::whereNotNull('closed_at')
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        return view('reports.shifts.index', [
            'currentShift' => $currentShift,
            'currentShiftCash' => $currentShift ? $this->cashSince($companyId, $currentShift->opened_at) : null,
            'shifts' => $shifts,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        if (Shift::whereNull('closed_at')->exists()) {
            return back()->with('status', __('app.reports.shifts.already_open'));
        }

        Shift::create([
            'opened_by' => $request->user()->id,
            'opened_at' => now(),
        ]);

        return redirect()->route('reports.shifts.index')
            ->with('status', __('app.reports.shifts.started'));
    }

    public function end(Request $request): RedirectResponse
    {
        $shift = Shift::whereNull('closed_at')->latest('opened_at')->first();

        if (! $shift) {
            return back()->with('status', __('app.reports.shifts.none_open'));
        }

        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $closedAt = now();
        $expectedCash = $this->cashSince($request->user()->company_id, $shift->opened_at, $closedAt);

        $shift->update([
            'closed_by' => $request->user()->id,
            'closed_at' => $closedAt,
            'expected_cash' => $expectedCash,
            'actual_cash' => $validated['actual_cash'],
            'difference' => round($validated['actual_cash'] - $expectedCash, 2),
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('reports.shifts.index')
            ->with('status', __('app.reports.shifts.ended'));
    }

    private function cashSince(int $companyId, DateTimeInterface $since, ?DateTimeInterface $until = null): float
    {
        return (float) Payment::query()
            ->join('receipts', 'receipts.id', '=', 'payments.receipt_id')
            ->where('receipts.company_id', $companyId)
            ->whereRaw('LOWER(payments.payment_method) = ?', [self::CASH_METHOD])
            ->whereBetween('receipts.transaction_date', [$since, $until ?? now()])
            ->sum('payments.amount');
    }
}
