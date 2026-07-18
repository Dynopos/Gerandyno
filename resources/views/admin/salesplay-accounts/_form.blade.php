@props(['account' => null, 'companies'])

<div class="space-y-4">
    <div>
        <x-input-label for="company_id" :value="__('app.admin.salesplay_accounts.company')" />
        <select id="company_id" name="company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected((int) old('company_id', $account?->company_id) === $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="shop_name" :value="__('app.admin.salesplay_accounts.shop_name')" />
        <x-text-input
            id="shop_name"
            name="shop_name"
            type="text"
            class="mt-1 block w-full"
            :value="old('shop_name', $account?->shop_name)"
            required
            autofocus
        />
        <x-input-error :messages="$errors->get('shop_name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="salesplay_shop_id" :value="__('app.admin.salesplay_accounts.shop_id')" />
        <x-text-input
            id="salesplay_shop_id"
            name="salesplay_shop_id"
            type="text"
            class="mt-1 block w-full"
            :value="old('salesplay_shop_id', $account?->salesplay_shop_id)"
        />
        <x-input-error :messages="$errors->get('salesplay_shop_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="api_token" :value="__('app.admin.salesplay_accounts.api_token')" />
        <x-text-input
            id="api_token"
            name="api_token"
            type="password"
            autocomplete="off"
            class="mt-1 block w-full"
            :required="! $account"
        />
        @if ($account)
            <p class="mt-1 text-xs text-slate-500">{{ __('app.admin.salesplay_accounts.api_token_hint') }}</p>
        @endif
        <x-input-error :messages="$errors->get('api_token')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('app.admin.salesplay_accounts.status')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="active" @selected(old('status', $account?->status ?? 'active') === 'active')>{{ __('app.admin.companies.status_active') }}</option>
            <option value="inactive" @selected(old('status', $account?->status) === 'inactive')>{{ __('app.admin.companies.status_inactive') }}</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('admin.salesplay-accounts.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
        {{ __('app.admin.salesplay_accounts.cancel') }}
    </a>
    <x-primary-button class="bg-red-600 hover:bg-red-700 focus:bg-red-700 focus:ring-red-500">
        {{ __('app.admin.salesplay_accounts.save') }}
    </x-primary-button>
</div>
