@php
    $current = app()->getLocale();
@endphp

<div class="flex items-center gap-0.5 rounded-full border border-slate-200 bg-slate-50 p-0.5">
    @foreach (['ms' => 'BM', 'en' => 'EN'] as $locale => $label)
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf
            <input type="hidden" name="locale" value="{{ $locale }}">
            <button
                type="submit"
                @class([
                    'rounded-full px-2.5 py-1 text-xs font-semibold transition',
                    'bg-red-600 text-white shadow-sm' => $current === $locale,
                    'text-slate-500 hover:text-slate-700' => $current !== $locale,
                ])
            >
                {{ $label }}
            </button>
        </form>
    @endforeach
</div>
