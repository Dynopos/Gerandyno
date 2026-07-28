<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.payment_types.title')" :subtitle="__('app.reports.payment_types.subtitle')">
            <x-slot name="actions">
                <x-export-buttons export-route="reports.payment-types.export" email-route="reports.payment-types.email" :params="request()->query()" />
            </x-slot>
        </x-page-header>

        <x-period-filter :period="$period" route-name="reports.payment-types.index" />

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($paymentTypes->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.payment_types.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.payment_types.payment_method') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.payment_types.transactions') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.payment_types.total_sales') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($paymentTypes as $paymentType)
                                <tr>
                                    <td class="px-5 py-3 text-sm font-medium text-slate-900">{{ $paymentType['payment_method'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        {{ number_format($paymentType['transactions']) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$paymentType['total']" />
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
