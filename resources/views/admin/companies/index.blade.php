<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.companies.title')" :subtitle="__('app.admin.companies.subtitle')">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-violet-500 to-purple-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:from-violet-600 hover:to-purple-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('app.admin.companies.create') }}
                </a>
            </x-slot>
        </x-page-header>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($companies->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.admin.companies.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('app.admin.companies.name') }}</th>
                                <th class="px-5 py-3">{{ __('app.admin.companies.status') }}</th>
                                <th class="px-5 py-3">{{ __('app.admin.companies.users_count') }}</th>
                                <th class="px-5 py-3">{{ __('app.admin.companies.accounts_count') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($companies as $company)
                                <tr class="transition hover:bg-violet-50/40">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.companies.edit', $company) }}" class="font-medium text-violet-600 hover:text-violet-700">
                                            {{ $company->name }}
                                        </a>
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-status-pill :color="$company->isActive() ? 'green' : 'slate'">
                                            {{ __('app.admin.status.'.$company->status) }}
                                        </x-status-pill>
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-status-pill color="blue">{{ $company->users_count }}</x-status-pill>
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-status-pill color="pink">{{ $company->salesplay_accounts_count }}</x-status-pill>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $companies->links() }}
    </div>
</x-app-layout>
