<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header title="Laporan Bulanan" subtitle="Ringkasan jualan mengikut bulan">
            <x-slot name="actions">
                <select
                    onchange="window.location = this.value"
                    class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"
                >
                    @foreach ($availableYears as $availableYear)
                        <option value="{{ route('reports.monthly', ['year' => $availableYear]) }}" @selected($availableYear === $year)>
                            {{ $availableYear }}
                        </option>
                    @endforeach
                </select>
            </x-slot>
        </x-page-header>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <x-stat-card :label="'Jumlah Jualan '.$year" :value="\App\Support\Money::format($yearTotal)" />
            <x-stat-card :label="'Bilangan Transaksi '.$year" :value="number_format($yearTransactions)" />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Jualan Bulanan &mdash; {{ $year }}</h2>
            <div class="mt-4 h-64">
                <canvas
                    data-chart="bar"
                    data-labels="{{ $months->pluck('label')->toJson() }}"
                    data-values="{{ $months->pluck('total')->toJson() }}"
                ></canvas>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Bulan</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Bilangan Transaksi</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Jualan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($months as $month)
                            <tr class="{{ $month['total'] > 0 ? '' : 'opacity-50' }}">
                                <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">{{ $month['label'] }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ number_format($month['transactions']) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                    <x-money :amount="$month['total']" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
