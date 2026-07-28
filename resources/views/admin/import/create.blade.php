<x-app-layout>
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <x-page-header :title="__('app.admin.import.title')" :subtitle="__('app.admin.import.subtitle')" />

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('app.admin.import.columns_title') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('app.admin.import.columns_help') }}</p>

            <ul class="mt-3 grid grid-cols-1 gap-x-6 gap-y-1 text-sm text-slate-600 sm:grid-cols-2">
                <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">company_name</code> — {{ __('app.admin.import.col_company_name') }}</li>
                <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">shop_name</code> — {{ __('app.admin.import.col_shop_name') }}</li>
                <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">salesplay_shop_id</code> — {{ __('app.admin.import.col_shop_id') }}</li>
                <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">api_token</code> — {{ __('app.admin.import.col_api_token') }}</li>
                <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">customer_name</code> — {{ __('app.admin.import.col_customer_name') }}</li>
                <li><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">customer_email</code> — {{ __('app.admin.import.col_customer_email') }}</li>
            </ul>

            <a href="{{ asset('templates/customer-import-template.csv') }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-medium text-gold-600 hover:text-gold-700">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                    <path d="M12 3v12m0 0l-4-4m4 4l4-4" />
                    <path d="M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                </svg>
                {{ __('app.admin.import.download_template') }}
            </a>
        </div>

        <form method="POST" action="{{ route('admin.import.store') }}" enctype="multipart/form-data" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <x-input-label for="file" :value="__('app.admin.import.file_label')" />
            <input
                id="file"
                name="file"
                type="file"
                accept=".csv,text/csv"
                required
                class="mt-1 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-gold-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gold-700 hover:file:bg-gold-100"
            >
            <x-input-error :messages="$errors->get('file')" class="mt-2" />

            <div class="mt-4 flex justify-end">
                <x-primary-button class="bg-gold-600 hover:bg-gold-700 focus:bg-gold-700 focus:ring-gold-500">
                    {{ __('app.admin.import.submit') }}
                </x-primary-button>
            </div>
        </form>

        @isset($results)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold text-slate-900">{{ __('app.admin.import.results_title') }}</h2>

                <div class="mt-3 grid grid-cols-3 gap-3 text-center text-sm">
                    <div class="rounded-xl bg-emerald-50 px-3 py-3">
                        <p class="text-xl font-semibold text-emerald-700">{{ count($results['created']) }}</p>
                        <p class="mt-0.5 text-emerald-700">{{ __('app.admin.import.created') }}</p>
                    </div>
                    <div class="rounded-xl bg-amber-50 px-3 py-3">
                        <p class="text-xl font-semibold text-amber-700">{{ count($results['skipped']) }}</p>
                        <p class="mt-0.5 text-amber-700">{{ __('app.admin.import.skipped') }}</p>
                    </div>
                    <div class="rounded-xl bg-rose-50 px-3 py-3">
                        <p class="text-xl font-semibold text-rose-700">{{ count($results['failed']) }}</p>
                        <p class="mt-0.5 text-rose-700">{{ __('app.admin.import.failed') }}</p>
                    </div>
                </div>

                @foreach (['skipped', 'failed'] as $group)
                    @if (! empty($results[$group]))
                        <div class="mt-5">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('app.admin.import.'.$group) }}</p>
                            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                @foreach ($results[$group] as $item)
                                    <li>{{ __('app.admin.import.row', ['row' => $item['row']]) }}: {{ $item['reason'] }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        @endisset
    </div>
</x-app-layout>
