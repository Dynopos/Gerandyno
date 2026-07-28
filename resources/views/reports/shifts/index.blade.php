<x-app-layout>
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.reports.shifts.title')" :subtitle="__('app.reports.shifts.subtitle')" />

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if ($currentShift)
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">{{ __('app.reports.shifts.open') }}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ __('app.reports.shifts.opened_at') }}: {{ $currentShift->opened_at->translatedFormat('d M Y, h:i A') }}
                        </p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ __('app.reports.shifts.cash_so_far') }}: <span class="font-semibold text-slate-900"><x-money :amount="$currentShiftCash" /></span>
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('reports.shifts.end') }}" class="mt-5 border-t border-slate-100 pt-5">
                    @csrf

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="actual_cash" :value="__('app.reports.shifts.actual_cash')" />
                            <x-text-input
                                id="actual_cash"
                                name="actual_cash"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full"
                                :value="old('actual_cash')"
                                required
                                autofocus
                            />
                            <x-input-error :messages="$errors->get('actual_cash')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="note" :value="__('app.reports.shifts.note')" />
                            <x-text-input
                                id="note"
                                name="note"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('note')"
                            />
                            <x-input-error :messages="$errors->get('note')" class="mt-2" />
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <x-primary-button class="bg-rose-600 hover:bg-rose-700 focus:bg-rose-700 focus:ring-rose-500">
                            {{ __('app.reports.shifts.end_shift') }}
                        </x-primary-button>
                    </div>
                </form>
            @else
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.shifts.closed') }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ __('app.reports.shifts.no_open_shift') }}</p>
                    </div>

                    <form method="POST" action="{{ route('reports.shifts.start') }}">
                        @csrf
                        <x-primary-button class="bg-red-600 hover:bg-red-700 focus:bg-red-700 focus:ring-red-500">
                            {{ __('app.reports.shifts.start_shift') }}
                        </x-primary-button>
                    </form>
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('app.reports.shifts.history_title') }}</h2>
            </div>

            @if ($shifts->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-slate-500">{{ __('app.reports.shifts.no_data') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.shifts.opened_at') }}</th>
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.shifts.closed_at') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.shifts.expected_cash') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.shifts.actual_cash') }}</th>
                                <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.reports.shifts.difference') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($shifts as $shift)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $shift->opened_at->translatedFormat('d M Y, h:i A') }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-sm text-slate-600">{{ $shift->closed_at->translatedFormat('d M Y, h:i A') }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        <x-money :amount="$shift->expected_cash" />
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right text-sm text-slate-600">
                                        <x-money :amount="$shift->actual_cash" />
                                    </td>
                                    <td @class([
                                        'whitespace-nowrap px-5 py-3 text-right text-sm font-semibold',
                                        'text-rose-600' => (float) $shift->difference !== 0.0,
                                        'text-emerald-600' => (float) $shift->difference === 0.0,
                                    ])>
                                        <x-money :amount="$shift->difference" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $shifts->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
