<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header title="Laporan SST" subtitle="Ringkasan cukai perkhidmatan bagi penyediaan Penyata SST-02">
            <x-slot name="actions">
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <input type="month" name="dari" value="{{ $from->format('Y-m') }}" class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500" />
                    <span class="text-sm text-slate-500">hingga</span>
                    <input type="month" name="hingga" value="{{ $until->format('Y-m') }}" class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500" />
                    <button type="submit" class="inline-flex items-center rounded-lg bg-violet-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-500">Papar</button>
                </form>
                <button type="button" onclick="window.print()" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Print / PDF</button>
            </x-slot>
        </x-page-header>

        <div id="sst-print" class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm print:border-0 print:shadow-none">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b-2 border-slate-900 pb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Laporan Jualan &amp; Cukai Perkhidmatan (SST)</h2>
                        <p class="mt-1 text-xs text-slate-500">Ringkasan dalaman bagi penyediaan Penyata SST-02 &middot; Akta Cukai Perkhidmatan 2018</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Tempoh Laporan</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $from->translatedFormat('F Y') }} &ndash; {{ $until->translatedFormat('F Y') }}</p>
                    </div>
                </div>

                <dl class="mt-4 grid grid-cols-1 gap-x-10 gap-y-2 border-b border-slate-200 pb-4 text-sm sm:grid-cols-2">
                    <div class="flex gap-3"><dt class="w-36 shrink-0 text-slate-500">Nama Syarikat</dt><dd class="font-semibold text-slate-900">{{ $company->name }}</dd></div>
                    <div class="flex gap-3"><dt class="w-36 shrink-0 text-slate-500">No. Pendaftaran SST</dt><dd class="font-semibold text-slate-900">{{ $company->sst_no ?: '&mdash;' }}</dd></div>
                    <div class="flex gap-3"><dt class="w-36 shrink-0 text-slate-500">No. Pendaftaran SSM</dt><dd class="font-semibold text-slate-900">{{ $company->ssm_no ?: '&mdash;' }}</dd></div>
                    <div class="flex gap-3"><dt class="w-36 shrink-0 text-slate-500">Kadar Cukai</dt><dd class="font-semibold text-slate-900">6% (Cukai Perkhidmatan, inklusif dalam harga)</dd></div>
                    <div class="flex gap-3"><dt class="w-36 shrink-0 text-slate-500">Alamat Premis</dt><dd class="font-semibold text-slate-900">{{ $company->address ?: '&mdash;' }}</dd></div>
                    <div class="flex gap-3"><dt class="w-36 shrink-0 text-slate-500">Sumber Data</dt><dd class="font-semibold text-slate-900">Sistem POS (rekod resit jualan)</dd></div>
                </dl>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-slate-500">Jumlah Nilai Perkhidmatan Bercukai</p>
                        <p class="mt-1 text-xl font-bold text-slate-900">{{ \App\Support\Money::format($totalNilai) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Bilangan Resit</p>
                        <p class="mt-1 text-xl font-bold text-slate-900">{{ number_format($totalTransaksi) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-amber-700">Jumlah SST Dikutip (6%)</p>
                        <p class="mt-1 text-2xl font-extrabold text-amber-700">{{ \App\Support\Money::format($totalSst) }}</p>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm print:border-0 print:shadow-none">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Bulan</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Bil. Resit</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai Perkhidmatan</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">SST 6%</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Jumlah Kutipan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($months as $row)
                                <tr class="{{ $row['total'] > 0 ? '' : 'opacity-50' }}">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">{{ $row['label'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ number_format($row['transactions']) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ \App\Support\Money::format($row['nilai']) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-amber-700">{{ \App\Support\Money::format($row['sst']) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($row['total']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-6 text-center text-sm text-slate-500">Tiada rekod bagi tempoh ini.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="border-t-2 border-slate-900">
                            <tr>
                                <td class="whitespace-nowrap px-5 py-3 text-sm font-bold text-slate-900">Jumlah</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-bold text-slate-900">{{ number_format($totalTransaksi) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-bold text-slate-900">{{ \App\Support\Money::format($totalNilai) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-extrabold text-amber-700">{{ \App\Support\Money::format($totalSst) }}</td>
                                <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-bold text-slate-900">{{ \App\Support\Money::format($totalKutipan) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 text-xs text-slate-500 shadow-sm print:border-0 print:shadow-none">
                <ul class="list-disc space-y-1 pl-4">
                    <li>Laporan ini adalah ringkasan dalaman bagi membantu penyediaan Penyata SST-02 melalui portal MySST (mysst.customs.gov.my).</li>
                    <li>Harga jualan adalah inklusif SST; Nilai Perkhidmatan = Jumlah Kutipan &minus; SST Dikutip.</li>
                    <li>Angka dijana daripada rekod resit jualan sistem POS bagi tempoh dinyatakan. Dijana pada {{ now()->format('d/m/Y H:i') }}.</li>
                </ul>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body * { visibility: hidden; }
            #sst-print, #sst-print * { visibility: visible; }
            #sst-print { position: absolute; top: 0; left: 0; width: 100%; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</x-app-layout>
