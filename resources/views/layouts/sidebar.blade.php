{{-- Brand --}}
<div class="flex h-16 shrink-0 items-center justify-between gap-2 px-5">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full ring-1 ring-white/10">
            <img src="{{ asset('images/dynopos-logo.jpg') }}" alt="DynoPOS" class="h-full w-full scale-125 object-cover">
        </span>
        <span class="text-lg font-semibold tracking-tight text-white">DynoPOS</span>
    </a>

    <button @click="sidebarOpen = false" class="text-slate-500 hover:text-white lg:hidden" aria-label="{{ __('app.nav.close_menu') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
            <path d="M6 6l12 12M18 6L6 18" />
        </svg>
    </button>
</div>

{{-- Nav --}}
<nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
    <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
        <x-slot name="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <path d="M3 10.5L12 3l9 7.5" />
                <path d="M5 9.5V21h14V9.5" />
            </svg>
        </x-slot>
        {{ __('app.nav.dashboard') }}
    </x-sidebar-link>

    @if (Auth::user()->company_id)
        <p class="mt-6 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.nav.reports') }}</p>

        <x-sidebar-link :href="route('reports.sales.index')" :active="request()->routeIs('reports.sales.*')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3z" />
                    <path d="M9 8h6M9 12h6" />
                </svg>
            </x-slot>
            {{ __('app.nav.sales') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('reports.monthly')" :active="request()->routeIs('reports.monthly')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <rect x="3.5" y="5" width="17" height="16" rx="2" />
                    <path d="M3.5 10h17M8 3v4M16 3v4" />
                </svg>
            </x-slot>
            {{ __('app.nav.monthly') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('reports.yearly')" :active="request()->routeIs('reports.yearly')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <rect x="4" y="12" width="3.5" height="7" rx="1" />
                    <rect x="10.25" y="7" width="3.5" height="12" rx="1" />
                    <rect x="16.5" y="3" width="3.5" height="16" rx="1" />
                </svg>
            </x-slot>
            {{ __('app.nav.yearly') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('reports.products')" :active="request()->routeIs('reports.products')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M6 8h12l-1 12H7L6 8z" />
                    <path d="M9 8V6a3 3 0 016 0v2" />
                </svg>
            </x-slot>
            {{ __('app.nav.products') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('reports.customers.index')" :active="request()->routeIs('reports.customers.*')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <circle cx="9" cy="8" r="3.25" />
                    <path d="M2.75 20a6.25 6.25 0 0112.5 0" />
                    <path d="M16.5 8.5a3 3 0 010 5.75M20.5 20a5.5 5.5 0 00-4-5.3" />
                </svg>
            </x-slot>
            {{ __('app.nav.customers') }}
        </x-sidebar-link>
    @endif

    @if (Auth::user()->isAdmin())
        <p class="mt-6 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('app.admin.nav.section') }}</p>

        <x-sidebar-link :href="route('admin.companies.index')" :active="request()->routeIs('admin.companies.*')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M4 21V7l8-4 8 4v14" />
                    <path d="M9 21v-6h6v6M9 11h.01M15 11h.01M9 15h.01M15 15h.01" />
                </svg>
            </x-slot>
            {{ __('app.admin.nav.companies') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('admin.salesplay-accounts.index')" :active="request()->routeIs('admin.salesplay-accounts.*')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <rect x="3" y="4" width="18" height="16" rx="2" />
                    <path d="M3 9h18M8 13h.01M8 17h.01" />
                </svg>
            </x-slot>
            {{ __('app.admin.nav.salesplay_accounts') }}
        </x-sidebar-link>
    @endif
</nav>

{{-- User / logout --}}
<div class="shrink-0 border-t border-slate-800 p-3">
    <div class="flex items-center gap-3 rounded-xl bg-slate-800/60 p-3">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-600 text-sm font-semibold text-white">
            {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-white">{{ Auth::user()->name }}</p>
            <p class="truncate text-xs text-slate-400">{{ Auth::user()->company->name ?? 'DynoPOS Admin' }}</p>
        </div>
    </div>

    <div class="mt-2 space-y-0.5">
        <x-sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
            <x-slot name="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <circle cx="12" cy="8" r="3.25" />
                    <path d="M4.75 20a7.25 7.25 0 0114.5 0" />
                </svg>
            </x-slot>
            {{ __('app.nav.profile') }}
        </x-sidebar-link>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="group flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-400 transition hover:bg-slate-800 hover:text-white">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-slate-500 group-hover:text-slate-300">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" />
                    <path d="M16 17l5-5-5-5M21 12H9" />
                </svg>
                {{ __('app.nav.logout') }}
            </button>
        </form>
    </div>
</div>
