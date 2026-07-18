<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.companies.edit')" :subtitle="$company->name" />

        <form method="POST" action="{{ route('admin.companies.update', $company) }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('put')
            @include('admin.companies._form', ['company' => $company])
        </form>
    </div>
</x-app-layout>
