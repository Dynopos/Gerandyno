<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.company_users.password_title')" :subtitle="$company->name" />

        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="rounded-xl bg-slate-50 px-4 py-3">
                <p class="text-sm font-medium text-slate-900">{{ $user->name }}</p>
                <p class="text-sm text-slate-500">{{ $user->email }}</p>
            </div>

            <p class="mt-4 text-sm text-slate-600">{{ __('app.admin.company_users.password_intro') }}</p>

            <form method="POST" action="{{ route('admin.companies.users.password.update', [$company, $user]) }}" class="mt-5">
                @csrf
                @method('put')

                <div class="space-y-4">
                    <div>
                        <x-input-label for="password" :value="__('app.admin.company_users.new_password')" />
                        <x-text-input id="password" name="password" type="password" autocomplete="new-password" class="mt-1 block w-full" required autofocus />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" :value="__('app.admin.company_users.confirm_password')" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>

                <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800">{{ __('app.admin.company_users.password_warning') }}</p>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.companies.edit', $company) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
                        {{ __('app.admin.company_users.cancel') }}
                    </a>
                    <x-primary-button>
                        {{ __('app.admin.company_users.password_submit') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
