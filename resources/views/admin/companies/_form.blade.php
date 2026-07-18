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
        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="active" @selected(old('status', $company?->status ?? 'active') === 'active')>{{ __('app.admin.companies.status_active') }}</option>
            <option value="inactive" @selected(old('status', $company?->status) === 'inactive')>{{ __('app.admin.companies.status_inactive') }}</option>
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('admin.companies.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
        {{ __('app.admin.companies.cancel') }}
    </a>
    <x-primary-button class="bg-red-600 hover:bg-red-700 focus:bg-red-700 focus:ring-red-500">
        {{ __('app.admin.companies.save') }}
    </x-primary-button>
</div>
