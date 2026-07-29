<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.companies.title')" :subtitle="__('app.admin.companies.subtitle')">
            <x-slot name="actions">
                <a href="{{ route('admin.companies.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-violet-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('app.admin.companies.add') }}
                </a>
            </x-slot>
        </x-page-header>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($companies->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.admin.companies.no_companies') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.admin.companies.name') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.admin.companies.status') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.admin.companies.users') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.admin.companies.salesplay_accounts') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.admin.companies.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($companies as $company)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">
                                        {{ $company->name }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-emerald-50 text-emerald-700' => $company->isActive(),
                                            'bg-slate-100 text-slate-600' => ! $company->isActive(),
                                        ])>
                                            {{ $company->isActive() ? __('app.admin.companies.status_active') : __('app.admin.companies.status_inactive') }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ $company->users_count }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">{{ $company->salesplay_accounts_count }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.companies.edit', $company) }}" class="font-medium text-violet-600 hover:text-violet-700">
                                                {{ __('app.admin.companies.edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('{{ __('app.admin.companies.delete_confirm', ['name' => $company->name]) }}')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="font-medium text-slate-400 hover:text-rose-600">
                                                    {{ __('app.admin.companies.delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $companies->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
