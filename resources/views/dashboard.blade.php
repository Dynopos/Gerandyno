<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">

        {{-- Welcome banner --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-pink-500 px-6 py-8 text-white shadow-lg shadow-violet-500/20 sm:px-8">
            <div class="pointer-events-none absolute -right-10 -top-16 h-48 w-48 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -bottom-20 right-24 h-40 w-40 rounded-full bg-white/5"></div>

            <div class="relative">
                <p class="text-sm font-medium text-white/80">{{ now()->translatedFormat('l, d F Y') }}</p>
                <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">
                    {{ __('app.dashboard.welcome_back') }}, {{ Str::of(auth()->user()->name)->before(' ') }}
                </h1>
                @if (auth()->user()->company)
                    <p class="mt-2 text-sm text-white/80">{{ auth()->user()->company->name }}</p>
                @endif
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                :label="__('app.dashboard.today_sales')"
                :value="\App\Support\Money::format($todaySales)"
                :delta="$todayDelta"
                :deltaLabel="__('app.dashboard.vs_yesterday')"
                color="violet"
            >
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="2.5" y="6" width="19" height="12" rx="2" />
                        <circle cx="12" cy="12" r="2.5" />
                        <path d="M6 9h.01M18 15h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card :label="__('app.dashboard.this_month_sales')" :value="\App\Support\Money::format($monthSales)" color="blue">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="3.5" y="5" width="17" height="16" rx="2" />
                        <path d="M3.5 10h17M8 3v4M16 3v4" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card :label="__('app.dashboard.this_year_sales')" :value="\App\Support\Money::format($yearSales)" color="teal">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="4" y="12" width="3.5" height="7" rx="1" />
                        <rect x="10.25" y="7" width="3.5" height="12" rx="1" />
                        <rect x="16.5" y="3" width="3.5" height="16" rx="1" />
                    </svg>
                </x-slot>
            </x-stat-card>

            <x-stat-card :label="__('app.dashboard.today_transactions')" :value="number_format($todayTransactions)" color="pink">
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
                <h2 class="text-sm font-semibold text-slate-900">{!! __('app.dashboard.daily_sales_title', ['month' => now()->translatedFormat('F Y')]) !!}</h2>
                <p class="text-xs text-slate-500">{{ __('app.dashboard.daily_sales_subtitle') }}</p>
                <div class="mt-4 h-64">
                    <canvas
                        data-chart="line"
                        data-color="violet"
                        data-labels="{{ $dailySales->pluck('label')->toJson() }}"
                        data-values="{{ $dailySales->pluck('total')->toJson() }}"
                    ></canvas>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{!! __('app.dashboard.monthly_sales_title', ['year' => now()->year]) !!}</h2>
                <p class="text-xs text-slate-500">{{ __('app.dashboard.monthly_sales_subtitle') }}</p>
                <div class="mt-4 h-64">
                    <canvas
                        data-chart="bar"
                        data-color="teal"
                        data-labels="{{ $monthlySales->pluck('label')->toJson() }}"
                        data-values="{{ $monthlySales->pluck('total')->toJson() }}"
                    ></canvas>
                </div>
            </div>
        </div>

        {{-- Recent receipts + top categories --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('app.dashboard.recent_receipts') }}</h2>
                    <a href="{{ route('reports.sales.index') }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">
                        {!! __('app.dashboard.view_all') !!}
                    </a>
                </div>

                @if ($recentReceipts->isEmpty())
                    <p class="px-5 py-10 text-center text-sm text-slate-500">{{ __('app.dashboard.no_receipts') }}</p>
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

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('app.dashboard.top_categories') }}</h2>
                <p class="text-xs text-slate-500">{{ __('app.dashboard.this_month') }}</p>

                @php
                    $barColors = [
                        'from-violet-500 to-purple-600',
                        'from-sky-500 to-blue-600',
                        'from-teal-400 to-emerald-500',
                        'from-amber-400 to-orange-500',
                        'from-pink-500 to-rose-500',
                    ];
                @endphp

                <div class="mt-4 space-y-4">
                    @forelse ($topCategories as $category)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="truncate font-medium text-slate-700">{{ $category['category'] }}</span>
                                <span class="shrink-0 text-slate-500">{{ number_format($category['percentage'], 0) }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r {{ $barColors[$loop->index % count($barColors)] }}" style="width: {{ $category['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('app.dashboard.no_category_data') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
