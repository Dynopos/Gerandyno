<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.customers.title')" :subtitle="__('app.reports.customers.subtitle')" />

        <form method="GET" action="{{ route('reports.customers.index') }}" class="max-w-sm">
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="{{ __('app.reports.customers.search_placeholder') }}"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gold-500 focus:ring-gold-500"
            >
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($customers->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.customers.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.customers.name') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.customers.contact') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.customers.transactions') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.customers.total_spent') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.customers.last_purchase') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($customers as $customer)
                                <tr
                                    class="cursor-pointer transition hover:bg-slate-50"
                                    onclick="window.location='{{ route('reports.customers.show', $customer) }}'"
                                >
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        <a href="{{ route('reports.customers.show', $customer) }}" class="hover:text-gold-600">
                                            {{ $customer->name }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-slate-600">
                                        {{ $customer->email ?: $customer->phone ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ number_format($customer->receipts_count) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$customer->receipts_sum_total ?? 0" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                        {{ $customer->receipts_max_transaction_date ? \Illuminate\Support\Carbon::parse($customer->receipts_max_transaction_date)->translatedFormat('d M Y') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $customers->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
