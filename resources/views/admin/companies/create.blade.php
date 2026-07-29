<x-app-layout>
    <div class="mx-auto max-w-2xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('admin.companies.index') }}" class="text-sm font-medium text-violet-600 hover:text-violet-700">{!! __('app.admin.back') !!}</a>

        <x-page-header :title="__('app.admin.companies.create_title')" />

        <form method="POST" action="{{ route('admin.companies.store') }}" class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <x-input-label for="name" :value="__('app.admin.companies.name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" :value="__('app.admin.companies.status')" />
                <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500">
                    <option value="active" @selected(old('status', 'active') === 'active')>{{ __('app.admin.status.active') }}</option>
                    <option value="inactive" @selected(old('status') === 'inactive')>{{ __('app.admin.status.inactive') }}</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ __('app.admin.save') }}</x-primary-button>
                <a href="{{ route('admin.companies.index') }}" class="text-sm text-slate-500 hover:text-slate-700">{{ __('app.admin.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
