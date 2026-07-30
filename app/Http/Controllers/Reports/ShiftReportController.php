<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Support\Reports\ReportPeriodResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftReportController extends Controller
{
    /**
     * One row per shift (terminal open/close), synced from SalesPlay's
     * /shifts endpoint. A shift matches the period if it opened or closed
     * within it, since a long-running shift can open on one day and close
     * several days later.
     */
    public function index(Request $request): View
    {
        $period = ReportPeriodResolver::resolve($request);

        $shifts = Shift::query()
            ->where(fn (Builder $q) => $q
                ->whereBetween('opened_at', [$period->start, $period->end])
                ->orWhereBetween('closed_at', [$period->start, $period->end]))
            ->orderByDesc('opened_at')
            ->paginate(20)
            ->withQueryString();

        return view('reports.shifts.index', [
            'period' => $period,
            'shifts' => $shifts,
            'filterOptions' => ReportPeriodResolver::options(),
        ]);
    }
}
