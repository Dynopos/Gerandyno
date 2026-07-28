<x-app-layout>
    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.inventory.adjustment')" />

        <p class="mt-2 text-sm text-slate-500">{{ __('app.reports.inventory.adjustment_help') }}</p>

        <form method="POST" action="{{ route('reports.inventory.adjustment.store') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="space-y-4">
                <div>
                    <x-input-label for="product_id" :value="__('app.reports.inventory.adjustment_product')" />
                    <select
                        id="product_id"
                        name="product_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500"
                        required
                        autofocus
                    >
                        <option value="">{{ __('app.reports.inventory.adjustment_product_placeholder') }}</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                {{ $product->name }}{{ $product->sku ? " ({$product->sku})" : '' }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="quantity" :value="__('app.reports.inventory.adjustment_quantity')" />
                    <x-text-input
                        id="quantity"
                        name="quantity"
                        type="number"
                        step="0.01"
                        min="0"
                        class="mt-1 block w-full"
                        :value="old('quantity')"
                        required
                    />
                    <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="adjusted_at" :value="__('app.reports.inventory.adjustment_date')" />
                    <x-text-input
                        id="adjusted_at"
                        name="adjusted_at"
                        type="date"
                        class="mt-1 block w-full"
                        :value="old('adjusted_at', now()->format('Y-m-d'))"
                        required
                    />
                    <x-input-error :messages="$errors->get('adjusted_at')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="note" :value="__('app.reports.inventory.adjustment_note')" />
                    <textarea
                        id="note"
                        name="note"
                        rows="2"
                        placeholder="{{ __('app.reports.inventory.adjustment_note_placeholder') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-500 focus:ring-gold-500"
                    >{{ old('note') }}</textarea>
                    <x-input-error :messages="$errors->get('note')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('reports.inventory.index') }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('app.expenses.cancel') }}
                </a>
                <x-primary-button class="bg-gold-600 hover:bg-gold-700 focus:bg-gold-700 focus:ring-gold-500">
                    {{ __('app.expenses.save') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
