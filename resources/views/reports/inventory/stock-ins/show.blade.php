<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center gap-2 text-sm">
            <a href="{{ route('reports.inventory.stock-ins.index') }}" class="font-medium text-red-600 hover:text-red-700">{!! __('app.reports.inventory.stock_ins.back_to_list') !!}</a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h1 class="text-lg font-semibold text-slate-900">{{ $stockIn->received_at->translatedFormat('d M Y, h:i A') }}</h1>
                <dl class="mt-3 max-w-sm space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('app.reports.inventory.stock_ins.supplier') }}</dt>
                        <dd class="text-right text-slate-900">{{ $stockIn->supplier_name ?: __('app.reports.inventory.stock_ins.not_available') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('app.reports.inventory.stock_ins.invoice_no') }}</dt>
                        <dd class="text-right text-slate-900">{{ $stockIn->invoice_no ?: __('app.reports.inventory.stock_ins.not_available') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('app.reports.inventory.stock_ins.total') }}</dt>
                        <dd class="text-right font-semibold text-slate-900"><x-money :amount="$stockIn->total" /></dd>
                    </div>
                </dl>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.item_name') }}</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.quantity') }}</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.unit_cost') }}</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($stockIn->items as $item)
                            <tr>
                                <td class="px-5 py-3 text-sm font-medium text-slate-900">{{ $item->product_name }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                    {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                    <x-money :amount="$item->unit_cost" />
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                    <x-money :amount="$item->total" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
