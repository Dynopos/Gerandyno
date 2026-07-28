<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('reports.customers.index') }}" class="font-medium text-gold-600 hover:text-gold-700">{!! __('app.reports.customers.back_to_list') !!}</a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card :label="__('app.reports.customers.transactions')" :value="number_format($totalTransactions)" color="blue">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
                        <path d="M9 8h6M9 12h6" />
                    </svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card :label="__('app.reports.customers.total_spent')" :value="\App\Support\Money::format($totalSpent)" color="red">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="2.5" y="6" width="19" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 9h.01M18 15h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h1 class="text-lg font-semibold text-slate-900">{{ $customer->name }}</h1>
            </div>

            <div class="px-6 py-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.customers.contact_details') }}</h2>
                <dl class="mt-3 max-w-sm space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('app.reports.customers.email') }}</dt>
                        <dd class="text-right text-slate-900">{{ $customer->email ?: __('app.reports.customers.not_available') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('app.reports.customers.phone') }}</dt>
                        <dd class="text-right text-slate-900">{{ $customer->phone ?: __('app.reports.customers.not_available') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('app.reports.customers.address') }}</dt>
                        <dd class="text-right text-slate-900">
                            {{ collect([$customer->address, $customer->city, $customer->region, $customer->postal_code])->filter()->implode(', ') ?: __('app.reports.customers.not_available') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="border-t border-slate-100 px-6 py-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.customers.purchase_history') }}</h2>

                @if ($receipts->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">{{ __('app.reports.customers.no_transactions') }}</p>
                @else
                    <div class="mt-3 divide-y divide-slate-100">
                        @foreach ($receipts as $receipt)
                            <a href="{{ route('reports.sales.show', $receipt) }}" class="flex items-center justify-between py-3 hover:bg-slate-50">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $receipt->receipt_number ?? '#'.$receipt->id }}</p>
                                    <p class="text-xs text-slate-500">{{ $receipt->transaction_date->translatedFormat('d M Y, h:i A') }}</p>
                                </div>
                                <x-money :amount="$receipt->total" class="shrink-0 text-sm font-semibold text-slate-900" />
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-2">
                        {{ $receipts->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
