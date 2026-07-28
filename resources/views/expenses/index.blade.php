<x-app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.expenses.title')" :subtitle="__('app.expenses.subtitle')">
            <x-slot name="actions">
                <a href="{{ route('expenses.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-gold-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition hover:bg-gold-700">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    {{ __('app.expenses.add') }}
                </a>
            </x-slot>
        </x-page-header>

        <x-period-filter :period="$period" route-name="expenses.index" />

        <x-stat-card :label="__('app.expenses.total_for_period', ['period' => $period->label])" :value="\App\Support\Money::format($total)" color="red">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <rect x="2.5" y="6" width="19" height="12" rx="2" />
                    <circle cx="12" cy="12" r="2.5" />
                    <path d="M6 9h.01M18 15h.01" />
                </svg>
            </x-slot>
        </x-stat-card>

        <form method="GET" action="{{ route('expenses.index') }}" class="max-w-sm">
            <input type="hidden" name="filter" value="{{ $period->key }}">
            @if ($period->key === 'custom')
                <input type="hidden" name="from" value="{{ $period->start->format('Y-m-d') }}">
                <input type="hidden" name="to" value="{{ $period->end->format('Y-m-d') }}">
            @endif
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                placeholder="{{ __('app.expenses.search_placeholder') }}"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-gold-500 focus:ring-gold-500"
            >
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($expenses->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.expenses.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.expenses.date') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.expenses.category') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.expenses.description') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.expenses.amount') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.expenses.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($expenses as $expense)
                                <tr class="transition hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $expense->expense_date->translatedFormat('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm font-medium text-slate-900">{{ $expense->category }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-600">{{ $expense->description ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm font-semibold text-slate-900">
                                        <x-money :amount="$expense->amount" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('expenses.edit', $expense) }}" class="font-medium text-gold-600 hover:text-gold-700">
                                                {{ __('app.expenses.edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('{{ __('app.expenses.delete_confirm') }}')">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="font-medium text-slate-400 hover:text-rose-600">
                                                    {{ __('app.expenses.delete') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $expenses->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
