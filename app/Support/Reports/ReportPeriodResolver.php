<?php

namespace App\Support\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Resolves the `filter` (+ `from`/`to` for custom) query params on report
 * pages into a concrete date range. Falls back to "this month" for any
 * unrecognised or invalid input, so a report page never errors on a bad
 * query string.
 */
final class ReportPeriodResolver
{
    public const DEFAULT_FILTER = 'this_month';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'today' => 'Hari Ini',
            'yesterday' => 'Semalam',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lepas',
            'custom' => 'Julat Tarikh',
        ];
    }

    public static function resolve(Request $request): ReportPeriod
    {
        $filter = (string) $request->query('filter', self::DEFAULT_FILTER);
        $today = CarbonImmutable::today();

        return match ($filter) {
            'today' => new ReportPeriod('today', 'Hari Ini', $today->startOfDay(), $today->endOfDay()),
            'yesterday' => new ReportPeriod(
                'yesterday', 'Semalam', $today->subDay()->startOfDay(), $today->subDay()->endOfDay()
            ),
            'last_month' => self::monthPeriod($today->subMonthNoOverflow(), 'last_month', 'Bulan Lepas'),
            'custom' => self::customPeriod($request, $today),
            default => self::monthPeriod($today, 'this_month', 'Bulan Ini'),
        };
    }

    private static function monthPeriod(CarbonImmutable $reference, string $key, string $label): ReportPeriod
    {
        return new ReportPeriod($key, $label, $reference->startOfMonth(), $reference->endOfMonth());
    }

    private static function customPeriod(Request $request, CarbonImmutable $today): ReportPeriod
    {
        $from = self::parseDate($request->query('from'));
        $to = self::parseDate($request->query('to'));

        if (! $from || ! $to || $from->greaterThan($to)) {
            return self::monthPeriod($today, self::DEFAULT_FILTER, 'Bulan Ini');
        }

        $label = $from->isSameDay($to)
            ? $from->format('d M Y')
            : $from->format('d M Y').' - '.$to->format('d M Y');

        return new ReportPeriod('custom', $label, $from->startOfDay(), $to->endOfDay());
    }

    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)?->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
