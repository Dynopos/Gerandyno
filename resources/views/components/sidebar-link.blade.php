@props(['active' => false])

<a
    {{
        $attributes->merge([
            'class' => 'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition '
                . ($active
                    ? 'bg-gradient-to-r from-violet-500 to-purple-600 text-white shadow-md shadow-violet-500/30'
                    : 'text-slate-600 hover:bg-violet-50 hover:text-violet-700'),
        ])
    }}
>
    @isset($icon)
        <span class="{{ $active ? 'text-white' : 'text-slate-400 group-hover:text-violet-600' }}">
            {{ $icon }}
        </span>
    @endisset

    <span class="truncate">{{ $slot }}</span>
</a>
