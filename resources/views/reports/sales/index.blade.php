<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.sales.title')" :subtitle="__('app.reports.sales.subtitle')">
            <x-slot name="actions">
                <x-export-buttons export-route="reports.sales.export" email-route="reports.sales.email" :params="request()->query()" />
            </x-slot>
        </x-page-header>

        <x-period-filter :period="$period" route-name="reports.sales.index" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card :label="__('app.reports.sales.total_sales', ['period' => $period->label])" :value="\App\Support\Money::format($periodTotal)" color="red">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="2.5" y="6" width="19" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 9h.01M18 15h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card :label="__('app.reports.sales.total_transactions', ['period' => $period->label])" :value="number_format($periodCount)" color="blue">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
                        <path d="M9 8h6M9 12h6" />
                    </svg>
                </x-slot>
            </x-stat-card>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($receipts->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.sales.no_transactions') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.sales.date') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.sales.receipt_number') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.sales.payment_method') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.sales.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($receipts as $receipt)
                                <tr
                                    class="cursor-pointer transition hover:bg-slate-50"
                                    onclick="window.location='{{ route('reports.sales.show', $receipt) }}'"
                                >
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                        {{ $receipt->transaction_date->translatedFormat('d M Y, h:i A') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        <a href="{{ route('reports.sales.show', $receipt) }}" class="hover:text-gold-600">
                                            {{ $receipt->receipt_number ?? '#'.$receipt->id }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                        {{ $receipt->payments->pluck('payment_method')->map(fn ($m) => ucfirst($m))->implode(', ') ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$receipt->total" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $receipts->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
