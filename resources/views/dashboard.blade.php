<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        {{-- Welcome banner --}}
        <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 px-6 py-8 text-white shadow-sm sm:px-8">
            <p class="text-sm font-medium text-indigo-100">{{ now()->translatedFormat('l, d F Y') }}</p>
            <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">
                {{ __('Selamat kembali') }}, {{ Str::of(auth()->user()->name)->before(' ') }}
            </h1>
            @if (auth()->user()->company)
                <p class="mt-2 text-sm text-indigo-100">{{ auth()->user()->company->name }}</p>
            @endif
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card
                label="Jualan Hari Ini"
                :value="\App\Support\Money::format($todaySales)"
                :delta="$todayDelta"
                deltaLabel="berbanding semalam"
            >
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="2.5" y="6" width="19" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 9h.01M18 15h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card label="Jualan Bulan Ini" :value="\App\Support\Money::format($monthSales)">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="3.5" y="5" width="17" height="16" rx="2" />
                        <path d="M3.5 10h17M8 3v4M16 3v4" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card label="Jualan Tahun Ini" :value="\App\Support\Money::format($yearSales)">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="4" y="12" width="3.5" height="7" rx="1" />
                        <rect x="10.25" y="7" width="3.5" height="12" rx="1" />
                        <rect x="16.5" y="3" width="3.5" height="16" rx="1" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card label="Transaksi Hari Ini" :value="number_format($todayTransactions)">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
                        <path d="M9 8h6M9 12h6" />
                    </svg>
                </x-slot>
            </x-stat-card>
        </div>

        {{-- Charts --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Jualan Harian &mdash; {{ now()->translatedFormat('F Y') }}</h2>
                <p class="text-xs text-slate-500">Jumlah jualan mengikut hari, bulan semasa</p>
                <div class="mt-4 h-64">
                    <canvas
                        data-chart="line"
                        data-labels="{{ $dailySales->pluck('label')->toJson() }}"
                        data-values="{{ $dailySales->pluck('total')->toJson() }}"
                    ></canvas>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">Jualan Bulanan &mdash; {{ now()->year }}</h2>
                <p class="text-xs text-slate-500">Jumlah jualan mengikut bulan, tahun semasa</p>
                <div class="mt-4 h-64">
                    <canvas
                        data-chart="bar"
                        data-labels="{{ $monthlySales->pluck('label')->toJson() }}"
                        data-values="{{ $monthlySales->pluck('total')->toJson() }}"
                    ></canvas>
                </div>
            </div>
        </div>

        {{-- Recent receipts --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Resit Terkini</h2>
                <a href="{{ route('reports.sales.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    Lihat Semua &rarr;
                </a>
            </div>

            @if ($recentReceipts->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-500">Tiada resit lagi.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($recentReceipts as $receipt)
                        <a href="{{ route('reports.sales.show', $receipt) }}" class="flex items-center justify-between px-5 py-3 transition hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">
                                    {{ $receipt->receipt_number ?? '#'.$receipt->id }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $receipt->transaction_date->translatedFormat('d M Y, h:i A') }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <x-money :amount="$receipt->total" class="text-sm font-semibold text-slate-900" />
                                <p class="text-xs text-slate-500">
                                    {{ $receipt->payments->pluck('payment_method')->map(fn ($m) => ucfirst($m))->implode(', ') ?: '-' }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
