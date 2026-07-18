<x-app-layout>
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('reports.sales.index') }}" class="font-medium text-red-600 hover:text-red-700">{!! __('app.reports.sales.back_to_list') !!}</a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900">
                            {{ $receipt->receipt_number ?? '#'.$receipt->id }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $receipt->transaction_date->translatedFormat('l, d F Y, h:i A') }}
                        </p>
                        @if ($receipt->salesplayAccount)
                            <p class="mt-1 text-xs text-slate-400">{{ $receipt->salesplayAccount->shop_name }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                        {{ ucfirst($receipt->payment_status ?? 'paid') }}
                    </span>
                </div>
            </div>

            <div class="px-6 py-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.receipt.item') }}</h2>

                <div class="mt-3 divide-y divide-slate-100">
                    @foreach ($receipt->items as $item)
                        <div class="flex items-center justify-between py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }} &times; <x-money :amount="$item->unit_price" />
                                </p>
                            </div>
                            <x-money :amount="$item->total" class="shrink-0 text-sm font-semibold text-slate-900" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-2 border-t border-slate-100 bg-slate-50 px-6 py-5">
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>{{ __('app.receipt.subtotal') }}</span>
                    <x-money :amount="$receipt->subtotal" />
                </div>
                @if ((float) $receipt->discount > 0)
                    <div class="flex items-center justify-between text-sm text-slate-600">
                        <span>{{ __('app.receipt.discount') }}</span>
                        <span>-<x-money :amount="$receipt->discount" /></span>
                    </div>
                @endif
                <div class="flex items-center justify-between text-sm text-slate-600">
                    <span>{{ __('app.receipt.tax') }}</span>
                    <x-money :amount="$receipt->tax" />
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-2 text-base font-semibold text-slate-900">
                    <span>{{ __('app.receipt.total') }}</span>
                    <x-money :amount="$receipt->total" />
                </div>
            </div>

            <div class="border-t border-slate-100 px-6 py-5">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.receipt.payment') }}</h2>
                <div class="mt-3 space-y-2">
                    @foreach ($receipt->payments as $payment)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">{{ ucfirst($payment->payment_method) }}</span>
                            <x-money :amount="$payment->amount" class="font-medium text-slate-900" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
