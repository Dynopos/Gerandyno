<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('admin.companies.index') }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">{!! __('app.admin.back') !!}</a>

        <x-page-header :title="__('app.admin.companies.edit_title')" :subtitle="$company->name" />

        {{-- Company details --}}
        <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="name" :value="__('app.admin.companies.name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $company->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" :value="__('app.admin.companies.status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="active" @selected(old('status', $company->status) === 'active')>{{ __('app.admin.status.active') }}</option>
                    <option value="inactive" @selected(old('status', $company->status) === 'inactive')>{{ __('app.admin.status.inactive') }}</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <x-primary-button>{{ __('app.admin.save') }}</x-primary-button>
        </form>

        {{-- Customer users --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('app.admin.users.title') }}</h2>
                <p class="text-xs text-slate-500">{{ __('app.admin.users.subtitle') }}</p>
            </div>

            @if ($company->users->isEmpty())
                <p class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.users.empty') }}</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($company->users as $user)
                        <div class="flex items-center justify-between gap-3 px-6 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-sky-500 to-blue-600 text-sm font-semibold text-white">
                                    {{ Str::of($user->name)->substr(0, 1)->upper() }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.companies.users.destroy', [$company, $user]) }}" onsubmit="return confirm(@js(__('app.admin.users.delete_confirm')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-700">&times;</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.companies.users.store', $company) }}" class="space-y-4 border-t border-slate-100 bg-slate-50/60 px-6 py-5">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="user_name" :value="__('app.admin.users.name')" />
                        <x-text-input id="user_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user_email" :value="__('app.admin.users.email')" />
                        <x-text-input id="user_email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user_password" :value="__('app.admin.users.password')" />
                        <x-text-input id="user_password" name="password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="user_password_confirmation" :value="__('app.admin.users.password_confirmation')" />
                        <x-text-input id="user_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                    </div>
                </div>

                <x-primary-button>{{ __('app.admin.users.add') }}</x-primary-button>
            </form>
        </div>

        {{-- SalesPlay accounts --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">{{ __('app.admin.accounts.title') }}</h2>
                    <p class="text-xs text-slate-500">{{ __('app.admin.accounts.subtitle') }}</p>
                </div>
                <a href="{{ route('admin.salesplay-accounts.create', ['company_id' => $company->id]) }}" class="shrink-0 text-sm font-medium text-violet-600 hover:text-violet-700">
                    {{ __('app.admin.accounts.create') }}
                </a>
            </div>

            @if ($company->salesplayAccounts->isEmpty())
                <p class="px-6 py-8 text-center text-sm text-slate-500">{{ __('app.admin.accounts.empty') }}</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($company->salesplayAccounts as $account)
                        <a href="{{ route('admin.salesplay-accounts.edit', $account) }}" class="flex items-center justify-between gap-3 px-6 py-3 transition hover:bg-violet-50/40">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $account->shop_name }}</p>
                                <p class="truncate text-xs text-slate-500">
                                    {{ $account->last_synced_at?->translatedFormat('d M Y, h:i A') ?? __('app.admin.accounts.never_synced') }}
                                </p>
                            </div>
                            <x-status-pill :color="$account->isActive() ? 'green' : 'slate'">
                                {{ $account->isActive() ? __('app.admin.accounts.sync_enabled') : __('app.admin.accounts.sync_disabled') }}
                            </x-status-pill>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
