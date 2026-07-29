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
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.company_users.subtitle', ['count' => $company->users_count ?? $company->users()->count()]) }}</p>
            <a href="{{ route('admin.companies.users.create', $company) }}" class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-gold-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-gold-700">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                {{ __('app.admin.company_users.add') }}
            </a>
        </div>
    </div>
</x-app-layout>
