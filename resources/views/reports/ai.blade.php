<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.ai.title')" :subtitle="__('app.ai.subtitle')">
            <x-slot name="actions">
                @if ($configured && $snapshot->hasSales())
                    <form method="POST" action="{{ route('reports.ai.generate', ['week' => $selectedWeek]) }}" x-data="{ busy: false }" @submit="busy = true">
                        @csrf
                        <button
                            type="submit"
                            x-bind:disabled="busy"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-violet-600 to-pink-500 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                <path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" />
                                <path d="M18 16l.8 2.2L21 19l-2.2.8L18 22l-.8-2.2L15 19l2.2-.8L18 16z" />
                            </svg>
                            <span x-show="! busy">{{ $insight ? __('app.ai.regenerate') : __('app.ai.generate') }}</span>
                            {{-- Hidden until Alpine flips it: the project has no global [x-cloak] rule. --}}
                            <span x-show="busy" style="display: none;">{{ __('app.ai.generating') }}</span>
                        </button>
                    </form>
                @endif
            </x-slot>
        </x-page-header>

        @if (session('ai_error'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                {{ session('ai_error') }}
            </div>
        @endif

        {{-- Week switcher --}}
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="flex flex-wrap items-center gap-1">
                @foreach ($weekOptions as $key => $label)
                    <a
                        href="{{ route('reports.ai.index', ['week' => $key]) }}"
                        @class([
                            'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                            'bg-violet-600 text-white shadow-sm' => $selectedWeek === $key,
                            'text-slate-600 hover:bg-slate-100' => $selectedWeek !== $key,
                        ])
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <p class="text-sm text-slate-500 sm:ml-auto">{{ __('app.ai.period', ['period' => $snapshot->periodLabel()]) }}</p>
        </div>

        @unless ($configured)
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                <p class="text-sm font-semibold text-slate-900">{{ __('app.ai.not_configured_title') }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ __('app.ai.not_configured_body') }}</p>
            </div>
        @endunless

        {{-- The generated review --}}
        @if ($insight)
            <div class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
                <div class="bg-gradient-to-r from-violet-600 to-pink-500 px-6 py-5 text-white">
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/80">{{ __('app.ai.title') }}</p>
                    <h2 class="mt-1 text-lg font-semibold">{{ $insight->headline }}</h2>
                    <p class="mt-0.5 text-xs text-white/70">
                        {{ __('app.ai.generated_at', ['time' => $insight->generatedAt->translatedFormat('d M Y, h:i A')]) }}
                    </p>
                </div>

                <div class="space-y-5 px-6 py-5">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">{{ __('app.ai.summary_heading') }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $insight->summary }}</p>
                    </div>

                    @if ($insight->highlights)
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('app.ai.highlights_heading') }}</h3>
                            <ul class="mt-1.5 space-y-1.5">
                                @foreach ($insight->highlights as $highlight)
                                    <li class="flex gap-2 text-sm text-slate-600">
                                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-violet-500"></span>
                                        <span>{{ $highlight }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($insight->advice)
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('app.ai.advice_heading') }}</h3>
                            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach ($insight->advice as $index => $item)
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <div class="flex items-start gap-2.5">
                                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-violet-600 text-xs font-semibold text-white">{{ $index + 1 }}</span>
                                            <div class="min-w-0">
                                                @if ($item['title'] !== '')
                                                    <p class="text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                                                @endif
                                                <p class="mt-0.5 text-sm leading-relaxed text-slate-600">{{ $item['detail'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <p class="border-t border-slate-100 pt-4 text-xs text-slate-400">{{ __('app.ai.disclaimer') }}</p>
                </div>
            </div>
        @elseif ($configured)
            <div class="rounded-2xl border border-dashed border-violet-300 bg-violet-50/50 px-6 py-8 text-center">
                <p class="text-sm font-semibold text-slate-900">{{ __('app.ai.empty_title') }}</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-600">
                    {{ $snapshot->hasSales() ? __('app.ai.empty_body') : __('app.ai.no_data') }}
                </p>
            </div>
        @endif

        {{-- The figures the review is based on --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                :label="__('app.ai.stats.total_sales')"
                :value="\App\Support\Money::format($snapshot->totalSales)"
                :delta="$snapshot->deltaPercent"
                :delta-label="__('app.ai.stats.vs_last_week')"
                color="violet"
            />

            <x-stat-card
                :label="__('app.ai.stats.transactions')"
                :value="number_format($snapshot->transactions)"
                color="blue"
            />

            <x-stat-card
                :label="__('app.ai.stats.average_basket')"
                :value="\App\Support\Money::format($snapshot->averageBasket)"
                color="teal"
            />

            <x-stat-card
                :label="__('app.ai.stats.net_profit')"
                :value="\App\Support\Money::format($snapshot->netProfit)"
                :color="$snapshot->netProfit >= 0 ? 'orange' : 'red'"
            />
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Top products --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('app.ai.top_products') }}</h2>
                </div>

                @if ($snapshot->topProducts->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-400">{{ __('app.ai.no_products') }}</p>
                @else
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-2.5 text-left font-medium">{{ __('app.reports.products.product_name') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">{{ __('app.ai.quantity') }}</th>
                                <th scope="col" class="px-5 py-2.5 text-right font-medium">{{ __('app.ai.sales') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($snapshot->topProducts as $product)
                                <tr>
                                    <td class="px-5 py-2.5 text-slate-700">{{ $product['product_name'] }}</td>
                                    <td class="px-5 py-2.5 text-right text-slate-600">{{ number_format($product['quantity_sold'], 0) }}</td>
                                    <td class="px-5 py-2.5 text-right font-medium text-slate-900"><x-money :amount="$product['total_sales']" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Day by day --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('app.ai.daily_heading') }}</h2>

                    <div class="flex flex-wrap gap-1.5">
                        @if ($snapshot->bestDay)
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                {{ __('app.ai.best_day') }}: {{ $snapshot->bestDay['label'] }}
                            </span>
                        @endif
                        @if ($snapshot->quietestDay)
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                {{ __('app.ai.quietest_day') }}: {{ $snapshot->quietestDay['label'] }}
                            </span>
                        @endif
                    </div>
                </div>

                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-2.5 text-left font-medium">{{ __('app.ai.day') }}</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">{{ __('app.ai.transactions_col') }}</th>
                            <th scope="col" class="px-5 py-2.5 text-right font-medium">{{ __('app.ai.sales') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($snapshot->dailySeries as $day)
                            <tr>
                                <td class="px-5 py-2.5 text-slate-700">{{ $day['label'] }}</td>
                                <td class="px-5 py-2.5 text-right text-slate-600">{{ number_format($day['transactions']) }}</td>
                                <td class="px-5 py-2.5 text-right font-medium text-slate-900"><x-money :amount="$day['total']" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
