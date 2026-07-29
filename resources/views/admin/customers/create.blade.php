<x-app-layout>
    <div class="mx-auto max-w-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.customers.title')" :subtitle="__('app.admin.customers.subtitle')" />

        <form method="POST" action="{{ route('admin.customers.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="space-y-4">
                <div>
                    <x-input-label for="company_name" :value="__('app.admin.customers.company_name')" />
                    <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name')" required autofocus />
                    <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="shop_name" :value="__('app.admin.customers.shop_name')" />
                    <x-text-input id="shop_name" name="shop_name" type="text" class="mt-1 block w-full" :value="old('shop_name')" required />
                    <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="salesplay_shop_id" :value="__('app.admin.customers.shop_id')" />
                    <x-text-input id="salesplay_shop_id" name="salesplay_shop_id" type="text" class="mt-1 block w-full" :value="old('salesplay_shop_id')" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.customers.shop_id_hint') }}</p>
                    <x-input-error :messages="$errors->get('salesplay_shop_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="api_token" :value="__('app.admin.customers.api_token')" />
                    <x-text-input id="api_token" name="api_token" type="password" autocomplete="off" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('api_token')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('app.admin.customers.cancel') }}
                </a>
                <x-primary-button>
                    {{ __('app.admin.customers.submit') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
