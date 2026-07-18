@props(['exportRoute', 'emailRoute' => null, 'params' => []])

<div class="flex items-center gap-2">
    <a
        href="{{ route($exportRoute, array_merge($params, ['format' => 'csv'])) }}"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 3v12m0 0l-4-4m4 4l4-4" />
            <path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
        </svg>
        {{ __('app.export.csv') }}
    </a>
    <a
        href="{{ route($exportRoute, array_merge($params, ['format' => 'xlsx'])) }}"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 3v12m0 0l-4-4m4 4l4-4" />
            <path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
        </svg>
        {{ __('app.export.excel') }}
    </a>
    <a
        href="{{ route($exportRoute, array_merge($params, ['format' => 'pdf'])) }}"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 shadow-sm transition hover:bg-slate-50"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
            <path d="M12 3v12m0 0l-4-4m4 4l4-4" />
            <path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
        </svg>
        {{ __('app.export.pdf') }}
    </a>

    @if ($emailRoute)
        <form method="POST" action="{{ route($emailRoute) }}" x-data="{ sending: false }" @submit="sending = true">
            @csrf
            @foreach ($params as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button
                type="submit"
                :disabled="sending"
                class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 disabled:opacity-60"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <rect x="3" y="5" width="18" height="14" rx="2" />
                    <path d="M3 7l9 6 9-6" />
                </svg>
                <span x-text="sending ? @js(__('app.email_report.sending')) : @js(__('app.email_report.button'))"></span>
            </button>
        </form>
    @endif
</div>
