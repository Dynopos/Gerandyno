<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.company_users.create_title')" :subtitle="$company->name" />

        <form method="POST" action="{{ route('admin.companies.users.store', $company) }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="space-y-4">
                <div>
                    <x-input-label for="name" :value="__('app.admin.company_users.name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('app.admin.company_users.email')" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.company_users.email_hint') }}</p>
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('app.admin.company_users.password')" />
                    <x-text-input id="password" name="password" type="password" autocomplete="new-password" class="mt-1 block w-full" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.company_users.password_hint') }}</p>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.companies.edit', $company) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('app.admin.company_users.cancel') }}
                </a>
                <x-primary-button class="bg-gold-600 hover:bg-gold-700 focus:bg-gold-700 focus:ring-gold-500">
                    {{ __('app.admin.company_users.submit') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
