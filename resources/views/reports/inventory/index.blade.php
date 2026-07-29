<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.inventory.title')" :subtitle="__('app.reports.inventory.subtitle')">
            <x-slot name="actions">
                <button
                    type="button"
                    x-data=""
                    x-on:click="$dispatch('open-modal', 'confirm-stock-reset')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-sm font-medium text-rose-600 shadow-sm transition hover:bg-rose-50"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2m3 0-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
                    </svg>
                    {{ __('app.reports.inventory.reset') }}
                </button>

                <a href="{{ route('reports.inventory.adjustment.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gold-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-gold-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M4 4v5h5M20 20v-5h-5" />
                        <path d="M5.5 9a7 7 0 0112.6-2.5M18.5 15a7 7 0 01-12.6 2.5" />
                    </svg>
                    {{ __('app.reports.inventory.adjustment') }}
                </a>
            </x-slot>
        </x-page-header>

        <x-modal name="confirm-stock-reset" focusable>
            <form method="POST" action="{{ route('reports.inventory.reset') }}" class="p-6">
                @csrf

                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('app.reports.inventory.reset_confirm_title') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('app.reports.inventory.reset_confirm_description') }}
                </p>

                <div class="mt-6 flex justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">
                        {{ __('app.expenses.cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3">
                        {{ __('app.reports.inventory.reset_confirm_button') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>

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
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gold-500 focus:ring-gold-500"
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
                                    $openingBalance = $product->opening_balance;
                                    $closingBalance = $product->closing_balance;
                                @endphp
                                <tr>
                                    <td class="px-5 py-3 text-sm font-medium text-slate-900">{{ $product->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $product->sku ?: '-' }}</td>
                                    <td @class([
                                        'whitespace-nowrap px-5 py-3 text-right text-sm',
                                        'text-rose-600' => $openingBalance < 0,
                                        'text-slate-600' => $openingBalance >= 0,
                                    ])>
                                        {{ rtrim(rtrim(number_format($openingBalance, 2), '0'), '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ rtrim(rtrim(number_format($stockIn, 2), '0'), '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ rtrim(rtrim(number_format($stockOut, 2), '0'), '.') }}
                                    </td>
                                    <td @class([
                                        'whitespace-nowrap px-5 py-3 text-right text-sm font-semibold',
                                        'text-rose-600' => $closingBalance < 0,
                                        'text-slate-900' => $closingBalance >= 0,
                                    ])>
                                        {{ rtrim(rtrim(number_format($closingBalance, 2), '0'), '.') }}
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
