<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header title="Laporan Tahunan" subtitle="Ringkasan jualan mengikut tahun" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card label="Jumlah Jualan Keseluruhan" :value="\App\Support\Money::format($grandTotal)" />
            <x-stat-card label="Jumlah Transaksi Keseluruhan" :value="number_format($grandTransactions)" />
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($years->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">Tiada data jualan lagi.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Tahun</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Bilangan Transaksi</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Jualan</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($years as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">{{ $row['label'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ number_format($row['transactions']) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$row['total']" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm">
                                        <a href="{{ route('reports.monthly', ['year' => $row['year']]) }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                                            Lihat Bulanan &rarr;
                                        </a>
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
