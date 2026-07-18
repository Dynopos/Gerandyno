<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.salesplay_accounts.add')" />

        <form method="POST" action="{{ route('admin.salesplay-accounts.store') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @include('admin.salesplay-accounts._form', ['companies' => $companies])
        </form>
    </div>
</x-app-layout>
