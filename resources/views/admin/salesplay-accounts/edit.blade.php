<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.salesplay_accounts.edit')" :subtitle="$account->shop_name" />

        <form method="POST" action="{{ route('admin.salesplay-accounts.update', $account) }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('put')
            @include('admin.salesplay-accounts._form', ['account' => $account, 'companies' => $companies])
        </form>
    </div>
</x-app-layout>
