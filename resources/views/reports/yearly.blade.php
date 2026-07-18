<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.yearly.title')" :subtitle="__('app.reports.yearly.subtitle')" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card :label="__('app.reports.yearly.grand_total')" :value="\App\Support\Money::format($grandTotal)" color="red">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="2.5" y="6" width="19" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 9h.01M18 15h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card :label="__('app.reports.yearly.grand_transactions')" :value="number_format($grandTransactions)" color="blue">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
                        <path d="M9 8h6M9 12h6" />
                    </svg>
                </x-slot>
            </x-stat-card>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($years->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.yearly.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.yearly.year') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.yearly.transactions') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.yearly.total_sales') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($years as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">{{ $row['label'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ number_format($row['transactions']) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$row['total']" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm">
                                        <a href="{{ route('reports.monthly', ['year' => $row['year']]) }}" class="font-medium text-red-600 hover:text-red-700">
                                            {!! __('app.reports.yearly.view_monthly') !!}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
