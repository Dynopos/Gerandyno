<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Ai\Contracts\SalesInsightGenerator;
use App\Services\Ai\DTO\SalesInsight;
use App\Services\Ai\Exceptions\AiInsightException;
use App\Support\Reports\WeeklySalesReportService;
use App\Support\Reports\WeeklySalesSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * The AI weekly review: this week's (or last week's) sales figures, plus a
 * written review and next-week advice generated from them.
 *
 * Generating is a POST, never a side effect of viewing the page — every
 * generation costs an API call, so the page shows the last generated review
 * from the cache until the merchant presses the button again.
 */
class AiInsightController extends Controller
{
    /**
     * Long enough that a merchant reviewing last week still sees the review
     * they generated, short enough that the cache can't outlive the data.
     */
    private const CACHE_DAYS = 30;

    public function __construct(
        private readonly WeeklySalesReportService $weekly,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $week = $this->resolveWeek($request);
        $snapshot = $this->weekly->build($user->company_id, $week);
        $cached = Cache::get($this->cacheKey($user->company_id, $snapshot));

        return view('reports.ai', [
            'snapshot' => $snapshot,
            'insight' => is_array($cached) ? SalesInsight::fromArray($cached) : null,
            'weekOptions' => $this->weekOptions(),
            'selectedWeek' => $this->selectedWeek($request),
            'configured' => $this->isConfigured(),
        ]);
    }

    public function generate(Request $request, SalesInsightGenerator $generator): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return back()->with('ai_error', __('app.ai.not_configured'));
        }

        $user = $request->user();
        $snapshot = $this->weekly->build($user->company_id, $this->resolveWeek($request));

        if (! $snapshot->hasSales()) {
            return back()->with('ai_error', __('app.ai.no_data'));
        }

        try {
            $insight = $generator->generate($snapshot, $user->company->name, app()->getLocale());
        } catch (AiInsightException $e) {
            report($e);

            return back()->with('ai_error', __('app.ai.failed'));
        }

        Cache::put(
            $this->cacheKey($user->company_id, $snapshot),
            $insight->toArray(),
            now()->addDays(self::CACHE_DAYS)
        );

        return back()->with('status', __('app.ai.generated'));
    }

    private function isConfigured(): bool
    {
        return filled(config('services.openai.api_key'));
    }

    private function selectedWeek(Request $request): string
    {
        return $request->query('week') === 'last_week' ? 'last_week' : 'this_week';
    }

    private function resolveWeek(Request $request): CarbonImmutable
    {
        $today = CarbonImmutable::today();

        return $this->selectedWeek($request) === 'last_week' ? $today->subWeek() : $today;
    }

    /**
     * @return array<string, string>
     */
    private function weekOptions(): array
    {
        return [
            'this_week' => __('app.ai.this_week'),
            'last_week' => __('app.ai.last_week'),
        ];
    }

    /**
     * Scoped by company (never share one merchant's review with another),
     * by week, and by locale — a review written in BM is not the review an
     * English-reading user asked for.
     */
    private function cacheKey(int $companyId, WeeklySalesSnapshot $snapshot): string
    {
        return sprintf(
            'ai-insight:%d:%s:%s',
            $companyId,
            $snapshot->weekStart->toDateString(),
            app()->getLocale()
        );
    }
}
