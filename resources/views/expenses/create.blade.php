<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.expenses.add')" />

        <form method="POST" action="{{ route('expenses.store') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @include('expenses._form')
        </form>
    </div>
</x-app-layout>
