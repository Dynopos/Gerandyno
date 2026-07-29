@props(['color' => 'slate'])

@php
    $colors = [
        'violet' => 'bg-violet-100 text-violet-700',
        'blue' => 'bg-sky-100 text-sky-700',
        'teal' => 'bg-teal-100 text-teal-700',
        'orange' => 'bg-amber-100 text-amber-700',
        'pink' => 'bg-pink-100 text-pink-700',
        'green' => 'bg-emerald-100 text-emerald-700',
        'red' => 'bg-rose-100 text-rose-700',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold '.($colors[$color] ?? $colors['slate'])]) }}>
    {{ $slot }}
</span>
