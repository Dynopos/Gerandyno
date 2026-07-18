@props(['active' => false])

<a
    {{
        $attributes->merge([
            'class' => 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition '
                . ($active ? 'bg-red-600 text-white shadow-sm' : 'text-slate-400 hover:bg-slate-800 hover:text-white'),
        ])
    }}
>
    @isset($icon)
        <span class="{{ $active ? 'text-white' : 'text-slate-500 group-hover:text-slate-300' }}">
            {{ $icon }}
        </span>
    @endisset

    <span class="truncate">{{ $slot }}</span>
</a>
