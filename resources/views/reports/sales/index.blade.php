<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header title="Laporan Jualan" subtitle="Senarai transaksi mengikut tarikh" />

        <x-period-filter :period="$period" route-name="reports.sales.index" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card :label="'Jumlah Jualan - '.$period->label" :value="\App\Support\Money::format($periodTotal)" />
            <x-stat-card :label="'Bilangan Transaksi - '.$period->label" :value="number_format($periodCount)" />
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($receipts->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">Tiada transaksi untuk tempoh ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tarikh</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No. Resit</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kaedah Bayaran</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($receipts as $receipt)
                                <tr
                                    class="cursor-pointer transition hover:bg-slate-50"
                                    onclick="window.location='{{ route('reports.sales.show', $receipt) }}'"
                                >
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                        {{ $receipt->transaction_date->translatedFormat('d M Y, h:i A') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        <a href="{{ route('reports.sales.show', $receipt) }}" class="hover:text-indigo-600">
                                            {{ $receipt->receipt_number ?? '#'.$receipt->id }}
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">
                                        {{ $receipt->payments->pluck('payment_method')->map(fn ($m) => ucfirst($m))->implode(', ') ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$receipt->total" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $receipts->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
