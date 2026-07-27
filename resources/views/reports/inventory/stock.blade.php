<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.inventory.stock.title')" :subtitle="__('app.reports.inventory.stock.subtitle')" />

        <form method="GET" action="{{ route('reports.inventory.stock') }}" class="max-w-sm">
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="{{ __('app.reports.inventory.stock.search_placeholder') }}"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
            >
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($products->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.inventory.stock.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock.product_name') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock.sku') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock.stock_on_hand') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock.last_synced') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($products as $product)
                                <tr>
                                    <td class="px-5 py-3 text-sm font-medium text-slate-900">{{ $product->name }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $product->sku ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        {{ $product->stock_on_hand !== null ? rtrim(rtrim(number_format($product->stock_on_hand, 2), '0'), '.') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                        {{ $product->stock_synced_at?->translatedFormat('d M Y, h:i A') ?? __('app.reports.inventory.stock.never_synced') }}
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
