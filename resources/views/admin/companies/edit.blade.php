<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.companies.edit')" :subtitle="$company->name" />

        <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('put')
            @include('admin.companies._form', ['company' => $company])
        </form>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('app.admin.company_users.title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.company_users.subtitle', ['count' => $company->users->count()]) }}</p>
            @if ($company->users->isNotEmpty())
                <ul class="mt-4 divide-y divide-slate-100 border-y border-slate-100">
                    @foreach ($company->users as $user)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                            </div>
                            <a href="{{ route('admin.companies.users.password.edit', [$company, $user]) }}" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                    <rect x="4" y="10" width="16" height="10" rx="2" />
                                    <path d="M8 10V7a4 4 0 018 0v3" />
                                </svg>
                                {{ __('app.admin.company_users.reset_password') }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <a href="{{ route('admin.companies.users.create', $company) }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-violet-700">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                {{ __('app.admin.company_users.add') }}
            </a>
        </div>
    </div>
</x-app-layout>
