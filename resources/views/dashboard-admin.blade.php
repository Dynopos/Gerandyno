<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.dashboard.title')" :subtitle="__('app.admin.dashboard.description')" />

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat-card :label="__('app.admin.dashboard.companies')" :value="number_format($companyCount)" color="red">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M4 21V7l8-4 8 4v14" />
                        <path d="M9 21v-6h6v6" />
                    </svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card :label="__('app.admin.dashboard.active_companies')" :value="number_format($activeCompanyCount)" color="teal">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M5 13l4 4L19 7" />
                    </svg>
                </x-slot>
            </x-stat-card>
            <x-stat-card :label="__('app.admin.dashboard.salesplay_accounts')" :value="number_format($salesplayAccountCount)" color="blue">
                <x-slot name="icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path d="M3 9h18M8 13h.01M8 17h.01" />
                    </svg>
                </x-slot>
            </x-stat-card>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <a href="{{ route('admin.companies.index') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-gold-200 hover:bg-gold-50/40">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('app.admin.dashboard.manage_companies') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.companies.subtitle') }}</p>
                </div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0 text-slate-400">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </a>
            <a href="{{ route('admin.salesplay-accounts.index') }}" class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-gold-200 hover:bg-gold-50/40">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('app.admin.dashboard.manage_salesplay_accounts') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.salesplay_accounts.subtitle') }}</p>
                </div>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 shrink-0 text-slate-400">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </a>
        </div>
    </div>
</x-app-layout>
