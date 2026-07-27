<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.expenses.edit')" :subtitle="$expense->category" />

        <form method="POST" action="{{ route('expenses.update', $expense) }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('put')
            @include('expenses._form', ['expense' => $expense])
        </form>
    </div>
</x-app-layout>
