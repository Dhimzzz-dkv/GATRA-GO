@php
    /**
     * Layout mandiri untuk halaman error.
     *
     * Sengaja TIDAK memakai layouts.app: layout tersebut mengakses
     * auth()->user() (sidebar membaca role dan pegawai). Bila error terjadi
     * pada request tanpa pengguna terautentikasi, memakai layout itu justru
     * dapat memicu error kedua saat menampilkan error pertama.
     *
     * Variabel yang diharapkan:
     *   $kode      - kode HTTP (403/404/419/500)
     *   $judul     - judul singkat
     *   $pesan     - penjelasan untuk pengguna
     *   $aksiLabel - teks tombol utama (opsional)
     *   $aksiUrl   - tautan tombol utama (opsional)
     *   $aksiReload- bool: tombol memuat ulang halaman
     */
    $kode = $kode ?? '500';
    $judul = $judul ?? 'Terjadi Kesalahan';
    $pesan = $pesan ?? 'Sistem mengalami kendala. Silakan coba beberapa saat lagi.';
    $aksiLabel = $aksiLabel ?? 'Kembali ke Dashboard';
    $aksiUrl = $aksiUrl ?? url('/dashboard');
    $aksiReload = $aksiReload ?? false;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $kode }} — {{ $judul }} | {{ config('app.name', 'GATRA-GO') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-gorontalo.svg') }}">
    <link rel="preload" href="{{ asset('fonts/InterVariable.ttf') }}" as="font" type="font/ttf" crossorigin>

    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-surface-muted p-6 font-sans antialiased">
    <main class="w-full max-w-lg">
        <div class="idds-card overflow-hidden">
            {{-- Kepala --}}
            <div class="flex items-center gap-3 border-b border-border bg-surface-muted px-6 py-4">
                <img src="{{ asset('images/logo-gorontalo.svg') }}" alt="" class="h-8 w-8" aria-hidden="true">
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold tracking-tight text-ink">
                        {{ config('app.name', 'GATRA-GO') }}
                    </p>
                    <p class="truncate text-xs text-ink-muted">
                        Inspektorat Provinsi Gorontalo
                    </p>
                </div>
            </div>

            {{-- Isi --}}
            <div class="px-6 py-8 text-center sm:px-10">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-card bg-red-50 text-state-danger">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                </div>

                <p class="mt-5 text-5xl font-bold tracking-tight text-ink">{{ $kode }}</p>
                <h1 class="mt-2 text-lg font-bold text-ink">{{ $judul }}</h1>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-ink-muted">
                    {{ $pesan }}
                </p>

                {{-- Aksi --}}
                <div class="mt-7 flex flex-col items-center justify-center gap-2 sm:flex-row">
                    @if($aksiReload)
                        <x-action-button type="button" variant="primary" class="w-full sm:w-auto" onclick="window.location.reload()">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ $aksiLabel }}
                        </x-action-button>
                    @else
                        <x-action-button href="{{ $aksiUrl }}" variant="primary" class="w-full sm:w-auto">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            {{ $aksiLabel }}
                        </x-action-button>
                    @endif

                    @unless($aksiReload)
                        <x-action-button type="button" variant="secondary" class="w-full sm:w-auto" onclick="window.location.reload()">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Muat Ulang Halaman
                        </x-action-button>
                    @endunless
                </div>

                {{-- Catatan khusus halaman 500 --}}
                @if($kode === '500')
                    <p class="mt-6 rounded-card border border-border bg-surface-muted px-4 py-3 text-left text-xs leading-relaxed text-ink-muted">
                        Kejadian ini sudah tercatat secara otomatis. Bila terus berulang,
                        sampaikan kepada administrator sistem beserta waktu kejadian
                        ({{ now()->translatedFormat('d F Y, H:i') }} WITA) agar dapat ditelusuri.
                    </p>
                @endif
            </div>

            {{-- Kaki --}}
            <div class="border-t border-border bg-surface-muted px-6 py-3">
                <p class="text-center text-xs text-ink-subtle">
                    &copy; {{ date('Y') }} Inspektorat Provinsi Gorontalo &middot;
                    GATRA-GO — Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit
                </p>
            </div>
        </div>
    </main>
</body>
</html>
