@props(['label', 'value', 'delta' => null, 'deltaLabel' => null, 'color' => 'red'])

@php
    $iconColors = [
        'red' => 'bg-red-50 text-red-600',
        'orange' => 'bg-orange-50 text-orange-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'teal' => 'bg-teal-50 text-teal-600',
        'pink' => 'bg-pink-50 text-pink-600',
    ];
    $iconClasses = $iconColors[$color] ?? $iconColors['red'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 whitespace-nowrap text-xl font-semibold leading-tight text-slate-900">{{ $value }}</p>

            @if (! is_null($delta))
                <p @class([
                    'mt-2 flex items-center gap-1 text-xs font-medium',
                    'text-emerald-600' => $delta >= 0,
                    'text-rose-600' => $delta < 0,
                ])>
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        @if ($delta >= 0)
                            <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04L10.75 5.612V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                        @else
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                        @endif
                    </svg>
                    <span>{{ number_format(abs($delta), 1) }}%</span>
                    @if ($deltaLabel)
                        <span class="font-normal text-slate-400">{{ $deltaLabel }}</span>
                    @endif
                </p>
            @endif
        </div>

        @isset($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $iconClasses }}">
                {{ $icon }}
            </div>
        @endisset
    </div>
</div>
