<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.products.title')" :subtitle="__('app.reports.products.subtitle')">
            <x-slot name="actions">
                <x-export-buttons export-route="reports.products.export" email-route="reports.products.email" :params="request()->query()" />
            </x-slot>
        </x-page-header>

        <x-period-filter :period="$period" route-name="reports.products" />

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($products->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.products.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.products.product_name') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.products.quantity_sold') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.products.total_sales') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($products as $product)
                                <tr>
                                    <td class="px-5 py-3 text-sm font-medium text-slate-900">{{ $product['product_name'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ rtrim(rtrim(number_format($product['quantity_sold'], 2), '0'), '.') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$product['total_sales']" />
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
