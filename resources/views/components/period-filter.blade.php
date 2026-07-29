@props(['period', 'routeName'])

@php
    $presets = collect(\App\Support\Reports\ReportPeriodResolver::options())->except('custom');
@endphp

<div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
    <div class="flex flex-wrap items-center gap-1">
        @foreach ($presets as $key => $label)
            <a
                href="{{ route($routeName, ['filter' => $key]) }}"
                @class([
                    'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                    'bg-violet-600 text-white shadow-sm' => $period->key === $key,
                    'text-slate-600 hover:bg-slate-100' => $period->key !== $key,
                ])
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route($routeName) }}" class="flex flex-wrap items-center gap-2 border-slate-200 pl-0 sm:ml-auto sm:border-l sm:pl-3">
        <input type="hidden" name="filter" value="custom">
        <input
            type="date"
            name="from"
            value="{{ $period->key === 'custom' ? $period->start->format('Y-m-d') : '' }}"
            class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500"
        >
        <span class="text-slate-400">&ndash;</span>
        <input
            type="date"
            name="to"
            value="{{ $period->key === 'custom' ? $period->end->format('Y-m-d') : '' }}"
            class="rounded-lg border-slate-300 text-sm text-slate-700 focus:border-violet-500 focus:ring-violet-500"
        >
        <button type="submit" class="rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-violet-700">
            {{ __('app.filters.apply') }}
        </button>
    </form>
</div>
