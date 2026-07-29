<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.accounts.title')" :subtitle="__('app.admin.accounts.subtitle')">
            <x-slot name="actions">
                <a href="{{ route('admin.salesplay-accounts.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-violet-500 to-purple-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:from-violet-600 hover:to-purple-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('app.admin.accounts.create') }}
                </a>
            </x-slot>
        </x-page-header>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($accounts->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.admin.accounts.empty') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3">{{ __('app.admin.accounts.shop_name') }}</th>
                                <th class="px-5 py-3">{{ __('app.admin.accounts.company') }}</th>
                                <th class="px-5 py-3">{{ __('app.admin.accounts.sync') }}</th>
                                <th class="px-5 py-3">{{ __('app.admin.accounts.last_synced') }}</th>
                                <th class="px-5 py-3">{{ __('app.admin.accounts.last_sync_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($accounts as $account)
                                <tr class="transition hover:bg-violet-50/40">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.salesplay-accounts.edit', $account) }}" class="font-medium text-violet-600 hover:text-violet-700">
                                            {{ $account->shop_name }}
                                        </a>
                                        @if ($account->salesplay_shop_id)
                                            <p class="text-xs text-slate-400">{{ $account->salesplay_shop_id }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">{{ $account->company->name }}</td>
                                    <td class="px-5 py-3">
                                        <x-status-pill :color="$account->isActive() ? 'green' : 'slate'">
                                            {{ $account->isActive() ? __('app.admin.accounts.sync_enabled') : __('app.admin.accounts.sync_disabled') }}
                                        </x-status-pill>
                                    </td>
                                    <td class="px-5 py-3 text-slate-600">
                                        {{ $account->last_synced_at?->translatedFormat('d M Y, h:i A') ?? __('app.admin.accounts.never_synced') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($account->last_sync_status)
                                            <x-status-pill :color="$account->last_sync_status === 'success' ? 'teal' : 'red'">
                                                {{ __('app.admin.status.'.$account->last_sync_status) }}
                                            </x-status-pill>
                                        @else
                                            <span class="text-slate-400">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $accounts->links() }}
    </div>
</x-app-layout>
