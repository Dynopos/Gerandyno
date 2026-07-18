<x-app-layout>
    <div class="mx-auto flex max-w-3xl flex-col items-center px-4 py-20 text-center sm:px-6 lg:px-8">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7">
                <path d="M12 3l8 4v5c0 4.5-3 8-8 9-5-1-8-4.5-8-9V7l8-4z" />
            </svg>
        </div>

        <h1 class="mt-6 text-xl font-semibold text-slate-900">Panel Admin akan datang</h1>
        <p class="mt-2 max-w-sm text-sm text-slate-500">
            Dashboard laporan jualan ini adalah untuk akaun customer. Panel pengurusan
            company dan SalesPlay account untuk admin sedang dibina.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                Log Keluar
            </button>
        </form>
    </div>
</x-app-layout>
