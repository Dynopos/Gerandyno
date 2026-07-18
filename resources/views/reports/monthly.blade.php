<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.monthly.title')" :subtitle="__('app.reports.monthly.subtitle')">
            <x-slot name="actions">
                <select
                    onchange="window.location = this.value"
                    class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-red-500 focus:ring-red-500"
                >
                    @foreach ($availableYears as $availableYear)
                        <option value="{{ route('reports.monthly', ['year' => $availableYear]) }}" @selected($availableYear === $year)>
                            {{ $availableYear }}
                        </option>
                    @endforeach
                </select>

                <x-export-buttons export-route="reports.monthly.export" email-route="reports.monthly.email" :params="['year' => $year]" />
            </x-slot>
        </x-page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card :label="__('app.reports.monthly.total_sales', ['year' => $year])" :value="\App\Support\Money::format($yearTotal)" color="red">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="2.5" y="6" width="19" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 9h.01M18 15h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card :label="__('app.reports.monthly.total_transactions', ['year' => $year])" :value="number_format($yearTransactions)" color="blue">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
                        <path d="M9 8h6M9 12h6" />
                    </svg>
                </x-slot>
            </x-stat-card>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{!! __('app.reports.monthly.chart_title', ['year' => $year]) !!}</h2>
            <div class="mt-4 h-64">
                <canvas
                    data-chart="bar"
                    data-labels="{{ $months->pluck('label')->toJson() }}"
                    data-values="{{ $months->pluck('total')->toJson() }}"
                ></canvas>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.monthly.month') }}</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.monthly.transactions') }}</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.monthly.total_sales_col') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($months as $month)
                            <tr class="{{ $month['total'] > 0 ? '' : 'opacity-50' }}">
                                <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">{{ $month['label'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ number_format($month['transactions']) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                    <x-money :amount="$month['total']" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
