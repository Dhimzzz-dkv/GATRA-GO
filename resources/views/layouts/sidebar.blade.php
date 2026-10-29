{{-- Sidebar Navigation --}}
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-gray-200 bg-white transition-transform duration-300 lg:translate-x-0" aria-label="Navigasi aplikasi" aria-hidden="true" inert>
    {{-- Logo / Brand --}}
    <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-5">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-primary-100 bg-primary-50 primary-gradient-mark p-1">
            <img src="{{ asset('images/logo-gorontalo.svg') }}" alt="Logo Gorontalo" width="32" height="32" style="width: 32px; height: 32px; max-width: 100%; object-fit: contain;" class="h-8 w-8 object-contain">
        </div>
        <div>
            <p class="text-sm font-bold tracking-wide text-gray-900 ">GATRA-GO</p>
            <p class="text-xs font-medium text-gray-500">Inspektorat Gorontalo</p>
        </div>
    </div>

    {{-- Navigation Links --}}
    <nav aria-label="Navigasi utama" class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        {{-- Dashboard --}}
        <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif
           class="sidebar-link mb-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>

        @if(auth()->user()->isOpd())
            {{-- ========================================================================= --}}
            {{-- MENU: OPD (Ringkas & Terarah)                                             --}}
            {{-- ========================================================================= --}}
            <div class="pt-3 pb-1">
                <p class="px-3 text-xs font-bold  tracking-wider text-gray-500">Pengawasan OPD</p>
            </div>

            @php
                $opdTemuanMenuActive = request()->routeIs('temuan.*')
                    || request()->routeIs('pemeriksaan.show')
                    || request()->routeIs('pemeriksaan.lhp.*');
            @endphp
            <a href="{{ route('temuan.index') }}" @if($opdTemuanMenuActive) aria-current="page" @endif
               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ $opdTemuanMenuActive ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span>Temuan OPD</span>
            </a>

            @php $opdPendingCount = auth()->user()->getPendingVerifikasiCount(); @endphp
            <a href="{{ route('tindak-lanjut.index') }}" @if(request()->routeIs('tindak-lanjut.*')) aria-current="page" @endif
               class="sidebar-link flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('tindak-lanjut.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <div class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-current shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Progres Tindak Lanjut</span>
                </div>
                @if($opdPendingCount > 0)
                    <span class="ml-auto inline-flex items-center justify-center rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-amber-950 shadow-xs" title="{{ $opdPendingCount }} tindak lanjut perlu revisi">
                        {{ $opdPendingCount }}
                    </span>
                @endif
            </a>

        @else
            {{-- ========================================================================= --}}
            {{-- MENU OPERASIONAL PENGAWASAN (Super Admin, Admin Sumber, & Pemantau)       --}}
            {{-- ========================================================================= --}}
            <div class="pt-3 pb-1">
                <p class="px-3 text-xs font-bold  tracking-wider text-gray-500">Pengawasan &amp; Temuan</p>
            </div>

            {{-- 1. Antrean Persetujuan (Super Admin) --}}
            @if(auth()->user()->isSuperAdmin())
                @php $pendingApprovalCount = \App\Models\Pemeriksaan::where('status_pengajuan', 'MENUNGGU PERSETUJUAN')->count(); @endphp
                <a href="{{ route('pengajuan.index') }}" @if(request()->routeIs('pengajuan.*')) aria-current="page" @endif
                   class="sidebar-link flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('pengajuan.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <div class="flex items-center gap-3">
                        <svg class="h-4 w-4 text-current shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Antrean Persetujuan</span>
                    </div>
                    @if($pendingApprovalCount > 0)
                        <span class="ml-auto inline-flex items-center justify-center rounded-full bg-rose-500 px-2 py-0.5 text-xs font-bold text-white shadow-xs"
                              title="{{ $pendingApprovalCount }} pengajuan menunggu keputusan Anda">
                            {{ $pendingApprovalCount }}
                        </span>
                    @endif
                </a>
            @endif

            {{-- 2. Admin Sumber non-Inspektorat memakai alur LHP langsung tanpa menu SPT --}}
            @if(!auth()->user()->isAdminSumber() || auth()->user()->canManageSpt())
                <a href="{{ route('pemeriksaan.index') }}" @if(request()->routeIs('pemeriksaan.*')) aria-current="page" @endif
                   class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('pemeriksaan.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span>SPT &amp; Pemeriksaan</span>
                </a>
            @endif

            {{-- Admin Sumber non-Inspektorat memantau status laporan yang diajukan --}}
            @if(auth()->user()->isAdminSumber() && !auth()->user()->canManageSpt())
                <a href="{{ route('pengajuan-saya.index') }}" @if(request()->routeIs('pengajuan-saya.*')) aria-current="page" @endif
                   class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('pengajuan-saya.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h8m-8 4h8m-8 4h5m-9 4h14a2 2 0 002-2V7l-5-5H6a2 2 0 00-2 2v13a2 2 0 002 2z"/>
                    </svg>
                    <span>Pengajuan Saya</span>
                </a>
            @endif

            {{-- 3. LHP --}}
            <a href="{{ route('lhp.index') }}" @if(request()->routeIs('lhp.*') || request()->routeIs('pemeriksaan.lhp.*')) aria-current="page" @endif
               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('lhp.*') || request()->routeIs('pemeriksaan.lhp.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>LHP</span>
            </a>

            {{-- 4. Daftar Temuan --}}
            <a href="{{ route('temuan.index') }}" @if(request()->routeIs('temuan.*')) aria-current="page" @endif
               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('temuan.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <span>Daftar Temuan</span>
            </a>

            {{-- 5. Tindak Lanjut & Verifikasi --}}
            @php $pendingCount = auth()->user()->getPendingVerifikasiCount(); @endphp
            <a href="{{ route('tindak-lanjut.index') }}" @if(request()->routeIs('tindak-lanjut.*')) aria-current="page" @endif
               class="sidebar-link flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('tindak-lanjut.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <div class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-current shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span>Tindak Lanjut &amp; Verifikasi</span>
                </div>
                @if($pendingCount > 0)
                    <span class="ml-auto inline-flex items-center justify-center rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-amber-950 shadow-xs" title="{{ $pendingCount }} data menunggu tindakan">
                        {{ $pendingCount }}
                    </span>
                @endif
            </a>

            {{-- 6. Laporan & Rekap --}}
            <a href="{{ route('laporan.index') }}" @if(request()->routeIs('laporan.*')) aria-current="page" @endif
               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs('laporan.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Laporan &amp; Rekap</span>
            </a>

            {{-- ========================================================================= --}}
            {{-- MENU KHUSUS SUPER ADMIN: DROPDOWN MASTER DATA & LOG ARSIP                --}}
            {{-- ========================================================================= --}}
            @if(auth()->user()->isSuperAdmin())
                <div class="pt-4 pb-1">
                    <p class="px-3 text-xs font-bold  tracking-wider text-gray-500">Pengaturan Sistem</p>
                </div>

                {{-- Accordion 1: Master Data --}}
                <details class="group select-none" {{ (request()->routeIs('tahun-anggaran.*') || request()->routeIs('opd.*') || request()->routeIs('pegawai.*') || request()->routeIs('user.*')) ? 'open' : '' }}>
                    <summary class="sidebar-link flex cursor-pointer items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors list-none [&::-webkit-details-marker]:hidden {{ (request()->routeIs('tahun-anggaran.*') || request()->routeIs('opd.*') || request()->routeIs('pegawai.*') || request()->routeIs('user.*')) ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            <span>Master Data</span>
                        </div>
                        <svg class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="mt-1 space-y-1 pl-4 border-l-2 border-gray-200 ml-5 py-1">
                        <a href="{{ route('tahun-anggaran.index') }}" @if(request()->routeIs('tahun-anggaran.*')) aria-current="page" @endif
                           class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('tahun-anggaran.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>Tahun Anggaran</span>
                        </a>
                        <a href="{{ route('opd.index') }}" @if(request()->routeIs('opd.*')) aria-current="page" @endif
                           class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('opd.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>Master OPD</span>
                        </a>
                        <a href="{{ route('pegawai.index') }}" @if(request()->routeIs('pegawai.*')) aria-current="page" @endif
                           class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('pegawai.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>Pegawai Inspektorat</span>
                        </a>
                        <a href="{{ route('user.index') }}" @if(request()->routeIs('user.*')) aria-current="page" @endif
                           class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('user.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>Manajemen Pengguna</span>
                        </a>
                    </div>
                </details>

                {{-- Accordion 2: Log & Arsip --}}
                <details class="group select-none mt-1" {{ (request()->routeIs('audit-log.*')) ? 'open' : '' }}>
                    <summary class="sidebar-link flex cursor-pointer items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium transition-colors list-none [&::-webkit-details-marker]:hidden {{ request()->routeIs('audit-log.*') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <div class="flex items-center gap-3">
                            <svg class="h-4 w-4 text-current" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span>Log &amp; Arsip</span>
                        </div>
                        <svg class="h-3.5 w-3.5 text-gray-500 transition-transform duration-200 group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </summary>
                    <div class="mt-1 space-y-1 pl-4 border-l-2 border-gray-200 ml-5 py-1">
                        <a href="{{ route('audit-log.index') }}" @if(request()->routeIs('audit-log.index')) aria-current="page" @endif
                           class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('audit-log.index') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>Audit Trail</span>
                        </a>
                        <a href="{{ route('audit-log.trash') }}" @if(request()->routeIs('audit-log.trash')) aria-current="page" @endif
                           class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors {{ request()->routeIs('audit-log.trash') ? 'border-l-2 border-primary-600 bg-primary-50 primary-gradient-wash text-primary-800 font-semibold' : 'border-l-2 border-transparent text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                            <span>Arsip &amp; Pemulihan</span>
                        </a>
                    </div>
                </details>
            @endif
        @endif
    </nav>

    {{-- User Info at bottom --}}
    <div class="border-t border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-100 text-sm font-bold text-primary-800">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 overflow-hidden">
                <p class="truncate text-xs font-semibold text-gray-900">{{ auth()->user()->official_name }}</p>
                <p class="truncate text-xs text-gray-500">{{ auth()->user()->role->display_name ?? '-' }}</p>
            </div>
        </div>
    </div>
</aside>
