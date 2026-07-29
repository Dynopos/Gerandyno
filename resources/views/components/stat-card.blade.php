@props(['label', 'value', 'delta' => null, 'deltaLabel' => null, 'color' => 'violet'])

@php
    $gradients = [
        'violet' => 'from-violet-500 to-purple-600',
        'blue' => 'from-sky-500 to-blue-600',
        'teal' => 'from-teal-400 to-emerald-500',
        'orange' => 'from-amber-400 to-orange-500',
        'pink' => 'from-pink-500 to-rose-500',
        'red' => 'from-rose-500 to-red-600',
    ];
    $gradient = $gradients[$color] ?? $gradients['violet'];
@endphp

<div {{ $attributes->merge(['class' => "relative overflow-hidden rounded-2xl bg-gradient-to-br {$gradient} p-5 text-white shadow-lg shadow-slate-900/5"]) }}>
    <div class="pointer-events-none absolute -right-6 -top-10 h-28 w-28 rounded-full bg-white/10"></div>
    <div class="pointer-events-none absolute -bottom-12 -left-4 h-24 w-24 rounded-full bg-white/5"></div>

    <div class="relative flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-white/80">{{ $label }}</p>
            <p class="mt-2 whitespace-nowrap text-xl font-semibold leading-tight">{{ $value }}</p>

            @if (! is_null($delta))
                <p class="mt-2 flex items-center gap-1 text-xs font-medium text-white/90">
                    <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        @if ($delta >= 0)
                            <path fill-rule="evenodd" d="M10 17a.75.75 0 01-.75-.75V5.612L5.29 9.77a.75.75 0 01-1.08-1.04l5.25-5.5a.75.75 0 011.08 0l5.25 5.5a.75.75 0 11-1.08 1.04L10.75 5.612V16.25A.75.75 0 0110 17z" clip-rule="evenodd" />
                        @else
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 01.75.75v10.638l3.96-4.158a.75.75 0 111.08 1.04l-5.25 5.5a.75.75 0 01-1.08 0l-5.25-5.5a.75.75 0 111.08-1.04l3.96 4.158V3.75A.75.75 0 0110 3z" clip-rule="evenodd" />
                        @endif
                    </svg>
                    <span>{{ number_format(abs($delta), 1) }}%</span>
                    @if ($deltaLabel)
                        <span class="font-normal text-white/70">{{ $deltaLabel }}</span>
                    @endif
                </p>
            @endif
        </div>

        @isset($icon)
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20 text-white">
                {{ $icon }}
            </div>
        @endisset
    </div>
</div>
