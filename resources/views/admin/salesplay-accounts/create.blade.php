<x-app-layout>
    <div class="mx-auto max-w-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('admin.salesplay-accounts.index') }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">{!! __('app.admin.back') !!}</a>

        <x-page-header :title="__('app.admin.accounts.create_title')" />

        <form method="POST" action="{{ route('admin.salesplay-accounts.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <x-input-label for="company_id" :value="__('app.admin.accounts.company')" />
                <select id="company_id" name="company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500" required>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected(old('company_id', $selectedCompanyId) == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="shop_name" :value="__('app.admin.accounts.shop_name')" />
                <x-text-input id="shop_name" name="shop_name" type="text" class="mt-1 block w-full" :value="old('shop_name')" required />
                <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="salesplay_shop_id" :value="__('app.admin.accounts.shop_id')" />
                <x-text-input id="salesplay_shop_id" name="salesplay_shop_id" type="text" class="mt-1 block w-full" :value="old('salesplay_shop_id')" />
                <x-input-error :messages="$errors->get('salesplay_shop_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="api_token" :value="__('app.admin.accounts.api_token')" />
                <x-text-input id="api_token" name="api_token" type="password" autocomplete="new-password" class="mt-1 block w-full" required />
                <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.accounts.api_token_help') }}</p>
                <x-input-error :messages="$errors->get('api_token')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" :value="__('app.admin.accounts.sync')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="active" @selected(old('status', 'active') === 'active')>{{ __('app.admin.accounts.sync_enabled') }}</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>{{ __('app.admin.accounts.sync_disabled') }}</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ __('app.admin.save') }}</x-primary-button>
                <a href="{{ route('admin.salesplay-accounts.index') }}" class="text-sm text-slate-500 hover:text-slate-700">{{ __('app.admin.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
