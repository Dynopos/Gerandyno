<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.inventory.title')" :subtitle="__('app.reports.inventory.subtitle')" />

        <x-period-filter :period="$period" route-name="reports.inventory.index" />

        <form method="GET" action="{{ route('reports.inventory.index') }}" class="max-w-sm">
            <input type="hidden" name="filter" value="{{ $period->key }}">
            @if ($period->key === 'custom')
                <input type="hidden" name="from" value="{{ $period->start->format('Y-m-d') }}">
                <input type="hidden" name="to" value="{{ $period->end->format('Y-m-d') }}">
            @endif
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="{{ __('app.reports.inventory.search_placeholder') }}"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
            >
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($products->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.inventory.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.product_name') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.sku') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.opening_balance') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_in') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_out') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($products as $product)
                                @php
                                    $stockIn = (float) ($product->stock_in ?? 0);
                                    $stockOut = (float) ($product->stock_out ?? 0);
                                    $stockInAfterRange = (float) ($product->stock_in_after_range ?? 0);
                                    $stockOutAfterRange = (float) ($product->stock_out_after_range ?? 0);
                                    $currentBalance = $product->stock_on_hand;
                                    $closingBalance = $currentBalance !== null ? $currentBalance - $stockInAfterRange + $stockOutAfterRange : null;
                                    $openingBalance = $closingBalance !== null ? $closingBalance - $stockIn + $stockOut : null;
                                @endphp
                                <tr>
                                    <td class="px-5 py-3 text-sm font-medium text-slate-900">{{ $product->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $product->sku ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ $openingBalance !== null ? rtrim(rtrim(number_format($openingBalance, 2), '0'), '.') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ rtrim(rtrim(number_format($stockIn, 2), '0'), '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ rtrim(rtrim(number_format($stockOut, 2), '0'), '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        {{ $closingBalance !== null ? rtrim(rtrim(number_format($closingBalance, 2), '0'), '.') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $products->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
