<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.pnl.title')" :subtitle="__('app.reports.pnl.subtitle')">
            <x-slot name="actions">
                <x-export-buttons export-route="reports.pnl.export" email-route="reports.pnl.email" :params="request()->query()" />
            </x-slot>
        </x-page-header>

        <x-period-filter :period="$period" route-name="reports.pnl" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat-card :label="__('app.reports.pnl.total_sales')" :value="\App\Support\Money::format($totalSales)" color="blue">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
                        <path d="M9 8h6M9 12h6" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card :label="__('app.reports.pnl.total_expenses')" :value="\App\Support\Money::format($totalExpenses)" color="orange">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="2.5" y="6" width="19" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 9h.01M18 15h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <div @class([
                'rounded-2xl border p-5 shadow-sm',
                'border-emerald-200 bg-emerald-50' => $netProfit >= 0,
                'border-rose-200 bg-rose-50' => $netProfit < 0,
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p @class([
                            'truncate text-sm font-medium',
                            'text-emerald-700' => $netProfit >= 0,
                            'text-rose-700' => $netProfit < 0,
                        ])>{{ __('app.reports.pnl.net_profit') }}</p>
                        <p @class([
                            'mt-2 whitespace-nowrap text-xl font-semibold leading-tight',
                            'text-emerald-900' => $netProfit >= 0,
                            'text-rose-900' => $netProfit < 0,
                        ])>{{ \App\Support\Money::format($netProfit) }}</p>
                    </div>
                    <div @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                        'bg-emerald-100 text-emerald-700' => $netProfit >= 0,
                        'bg-rose-100 text-rose-700' => $netProfit < 0,
                    ])>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            @if ($netProfit >= 0)
                                <path d="M4 15l6-6 4 4 6-6" />
                                <path d="M14 6h6v6" />
                            @else
                                <path d="M4 9l6 6 4-4 6 6" />
                                <path d="M14 18h6v-6" />
                            @endif
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b-2 border-violet-600 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">DynoPOS Cloud Report</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900">{{ auth()->user()->company->name }}</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ __('app.reports.pnl.title') }} &mdash; {{ $period->label }}</p>
            </div>

            <div class="px-6 py-5">
                <dl class="divide-y divide-slate-100 text-sm">
                    <div class="flex items-center justify-between py-3">
                        <dt class="font-semibold text-slate-900">{{ __('app.reports.pnl.total_sales') }}</dt>
                        <dd class="font-semibold text-slate-900"><x-money :amount="$totalSales" /></dd>
                    </div>

                    <div class="py-3">
                        <p class="font-semibold text-slate-900">{{ __('app.reports.pnl.expenses_heading') }}</p>

                        @if ($expensesByCategory->isEmpty())
                            <p class="mt-2 text-sm text-slate-400">{{ __('app.reports.pnl.no_expenses') }}</p>
                        @else
                            <div class="mt-2 space-y-1.5">
                                @foreach ($expensesByCategory as $expense)
                                    <div class="flex items-center justify-between pl-4 text-slate-600">
                                        <span>{{ $expense['category'] }}</span>
                                        <span>(<x-money :amount="$expense['total']" />)</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center justify-between py-3">
                        <dt class="font-semibold text-slate-900">{{ __('app.reports.pnl.total_expenses') }}</dt>
                        <dd class="font-semibold text-rose-600">(<x-money :amount="$totalExpenses" />)</dd>
                    </div>
                </dl>

                <div @class([
                    'mt-2 flex items-center justify-between rounded-xl px-4 py-4',
                    'bg-emerald-50' => $netProfit >= 0,
                    'bg-rose-50' => $netProfit < 0,
                ])>
                    <span @class([
                        'text-sm font-semibold uppercase tracking-wide',
                        'text-emerald-700' => $netProfit >= 0,
                        'text-rose-700' => $netProfit < 0,
                    ])>{{ __('app.reports.pnl.net_profit') }}</span>
                    <span @class([
                        'text-lg font-bold',
                        'text-emerald-700' => $netProfit >= 0,
                        'text-rose-700' => $netProfit < 0,
                    ])><x-money :amount="$netProfit" /></span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
