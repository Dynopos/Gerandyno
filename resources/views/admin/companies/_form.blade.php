@props(['company' => null])

<div class="space-y-4">
    <div>
        <x-input-label for="name" :value="__('app.admin.companies.name')" />
        <x-text-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $company?->name)"
            required
            autofocus
        />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" :value="__('app.admin.companies.status')" />
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
            <option value="active" @selected(old('status', $company?->status ?? 'active') === 'active')>{{ __('app.admin.companies.status_active') }}</option>
            <option value="inactive" @selected(old('status', $company?->status) === 'inactive')>{{ __('app.admin.companies.status_inactive') }}</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    {{-- Decides how this company's own figures are read: a registered
         business collects SST for the government (so it is not income and
         comes out before profit), an unregistered one keeps it. --}}
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4" x-data="{ sst: {{ old('sst_registered', $company?->sst_registered) ? 'true' : 'false' }} }">
        <label class="flex items-start gap-3">
            <input type="hidden" name="sst_registered" value="0">
            <input type="checkbox" name="sst_registered" value="1" x-model="sst" class="mt-0.5 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
            <span>
                <span class="text-sm font-medium text-slate-900">{{ __('app.admin.companies.sst_registered') }}</span>
                <span class="mt-0.5 block text-xs text-slate-500">{{ __('app.admin.companies.sst_registered_hint') }}</span>
            </span>
        </label>

        <div x-show="sst" style="display: none;" class="mt-4 space-y-4">
            <div>
                <x-input-label for="sst_no" :value="__('app.admin.companies.sst_no')" />
                <x-text-input id="sst_no" name="sst_no" type="text" class="mt-1 block w-full" :value="old('sst_no', $company?->sst_no)" />
                <x-input-error :messages="$errors->get('sst_no')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ssm_no" :value="__('app.admin.companies.ssm_no')" />
                <x-text-input id="ssm_no" name="ssm_no" type="text" class="mt-1 block w-full" :value="old('ssm_no', $company?->ssm_no)" />
                <x-input-error :messages="$errors->get('ssm_no')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="address" :value="__('app.admin.companies.address')" />
                <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $company?->address)" />
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>
        </div>
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
        {{ __('app.admin.companies.cancel') }}
    </a>
    <x-primary-button>
        {{ __('app.admin.companies.save') }}
    </x-primary-button>
</div>
