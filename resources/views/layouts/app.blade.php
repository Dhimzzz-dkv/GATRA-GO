<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="GATRA-GO — Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit Inspektorat Provinsi Gorontalo">

    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-gorontalo.svg') }}">
    <link rel="preload" href="{{ asset('fonts/InterVariable.ttf') }}" as="font" type="font/ttf" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-surface-muted font-sans antialiased">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <div class="flex min-h-screen min-w-0">
        {{-- Sidebar --}}
        @include('layouts.sidebar')

        {{-- Main Content --}}
        <div class="flex min-w-0 flex-1 flex-col lg:ml-64">
            {{-- Navbar --}}
            @include('layouts.navbar')

            {{-- Page Content --}}
            <main id="main-content" tabindex="-1" class="page-enter min-w-0 flex-1 px-4 py-page-block sm:px-page-gutter">
                {{-- Flash Messages --}}
                <x-flash-message type="success" :message="session('success')" />
                <x-flash-message type="error" :message="session('error')" />
                <x-flash-message type="warning" :message="session('warning')" />
                <x-flash-message type="info" :message="session('info')" />
                {{-- Ringkasan error validasi ($errors) --}}
                @include('components.validation-errors')

                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="border-t border-border bg-surface px-6 py-4">
                <p class="text-center text-sm text-ink-subtle">
                    &copy; {{ date('Y') }} Inspektorat Provinsi Gorontalo. GATRA-GO — Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit.
                </p>
            </footer>
        </div>
    </div>

    {{-- Mobile sidebar overlay --}}
    <button id="sidebar-overlay" type="button" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden" onclick="toggleSidebar(false)" aria-label="Tutup menu navigasi"></button>

    <div id="app-confirm-dialog" class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/45 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="app-confirm-title" aria-describedby="app-confirm-message">
        <div class="w-full max-w-md overflow-hidden rounded-3xl border border-white/80 bg-white shadow-2xl shadow-slate-950/20">
            <div class="flex items-start gap-4 p-6 pb-5">
                <div id="app-confirm-icon" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1 pt-0.5">
                    <p id="app-confirm-eyebrow" class="mb-1 text-[10px] font-extrabold uppercase tracking-[0.16em] text-emerald-700">Konfirmasi Persetujuan</p>
                    <h2 id="app-confirm-title" class="text-lg font-bold leading-snug text-slate-900">Setujui pengajuan ini?</h2>
                    <p id="app-confirm-message" class="mt-2 text-sm leading-relaxed text-slate-600"></p>
                </div>
                <button type="button" id="app-confirm-close" class="-mr-2 -mt-2 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500" aria-label="Tutup konfirmasi">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 bg-slate-50/80 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" id="app-confirm-cancel" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
                    Batal
                </button>
                <button type="button" id="app-confirm-accept" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span id="app-confirm-accept-label">Ya, Setujui</span>
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
