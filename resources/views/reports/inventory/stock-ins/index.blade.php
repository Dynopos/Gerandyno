<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.inventory.stock_ins.title')" :subtitle="__('app.reports.inventory.stock_ins.subtitle')" />

        <form method="GET" action="{{ route('reports.inventory.stock-ins.index') }}" class="max-w-sm">
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="{{ __('app.reports.inventory.stock_ins.search_placeholder') }}"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
            >
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($stockIns->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.inventory.stock_ins.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.received_at') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.supplier') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.invoice_no') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.items') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.inventory.stock_ins.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($stockIns as $stockIn)
                                <tr
                                    class="cursor-pointer transition hover:bg-slate-50"
                                    onclick="window.location='{{ route('reports.inventory.stock-ins.show', $stockIn) }}'"
                                >
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        <a href="{{ route('reports.inventory.stock-ins.show', $stockIn) }}" class="hover:text-red-600">
                                            {{ $stockIn->received_at->translatedFormat('d M Y, h:i A') }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-slate-600">{{ $stockIn->supplier_name ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $stockIn->invoice_no ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ number_format($stockIn->items_count) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$stockIn->total" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $stockIns->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
