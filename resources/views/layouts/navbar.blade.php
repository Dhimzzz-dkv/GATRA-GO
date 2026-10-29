{{-- Top Navbar --}}
<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-border bg-surface px-4 sm:px-6 lg:px-8">
    {{-- Left: Mobile menu button + Page title --}}
    <div class="flex min-w-0 flex-1 items-center gap-3">
        {{-- Mobile sidebar toggle --}}
        <button id="sidebar-toggle" type="button" onclick="toggleSidebar()" class="rounded-control p-2 text-ink-muted hover:bg-surface-muted lg:hidden" aria-label="Buka menu navigasi" aria-controls="sidebar" aria-expanded="false">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div class="min-w-0">
            <h1 class="idds-page-title truncate">@yield('page-title', 'Dashboard')</h1>
            @hasSection('page-subtitle')
                <p class="hidden truncate text-sm text-ink-muted sm:block">@yield('page-subtitle')</p>
            @endif
        </div>
    </div>

    {{-- Right: User dropdown --}}
    <div class="flex shrink-0 items-center gap-3">
        {{-- Role Badge --}}
        <span class="hidden rounded-control border border-primary-100 bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-800 sm:inline-flex">
            {{ auth()->user()->role->display_name ?? '-' }}
        </span>

        @if(auth()->user()->isAdminSumber())
            <span class="hidden rounded-control border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 sm:inline-flex">
                {{ auth()->user()->getSumberPemeriksaan() }}
            </span>
        @endif

        {{-- User dropdown --}}
        <div class="relative">
            <button id="user-menu-toggle" type="button" onclick="toggleUserMenu()" class="flex items-center gap-2 rounded-control p-2 text-ink hover:bg-surface-muted" aria-label="Buka menu pengguna" aria-controls="user-menu" aria-expanded="false">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="hidden text-sm font-medium text-ink-muted md:block">{{ auth()->user()->name }}</span>
                <svg class="hidden h-4 w-4 text-gray-500 md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Dropdown menu --}}
            <div id="user-menu" class="absolute right-0 mt-2 hidden w-48 rounded-card border border-border bg-surface py-2 shadow-popover">
                <div class="border-b border-border px-4 py-2">
                    <p class="text-sm font-medium text-ink">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-ink-subtle">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-state-danger hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
