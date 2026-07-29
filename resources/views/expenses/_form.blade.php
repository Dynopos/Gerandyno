@props(['expense' => null])

<div class="space-y-4">
    <div>
        <x-input-label for="category" :value="__('app.expenses.category')" />
        <x-text-input
            id="category"
            name="category"
            type="text"
            class="mt-1 block w-full"
            :value="old('category', $expense?->category)"
            placeholder="{{ __('app.expenses.category_placeholder') }}"
            required
            autofocus
        />
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="amount" :value="__('app.expenses.amount')" />
        <x-text-input
            id="amount"
            name="amount"
            type="number"
            step="0.01"
            min="0.01"
            class="mt-1 block w-full"
            :value="old('amount', $expense?->amount)"
            required
        />
        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="expense_date" :value="__('app.expenses.expense_date')" />
        <x-text-input
            id="expense_date"
            name="expense_date"
            type="date"
            class="mt-1 block w-full"
            :value="old('expense_date', $expense?->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d'))"
            required
        />
        <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" :value="__('app.expenses.description')" />
        <textarea
            id="description"
            name="description"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500"
        >{{ old('description', $expense?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ route('expenses.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
        {{ __('app.expenses.cancel') }}
    </a>
    <x-primary-button>
        {{ __('app.expenses.save') }}
    </x-primary-button>
</div>
