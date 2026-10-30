<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Verifikasi Dua Langkah (2FA) — GATRA-GO Inspektorat Provinsi Gorontalo">

    <title>Verifikasi 2FA — {{ config('app.name') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-gorontalo.svg') }}">
    <link rel="preload" href="{{ asset('fonts/InterVariable.ttf') }}" as="font" type="font/ttf" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface-muted font-sans antialiased">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <main id="main-content" tabindex="-1" class="flex w-full flex-1 items-center justify-center">
            <div class="w-full max-w-md">
                {{-- Logo & Brand --}}
                <div class="mb-8 text-center">
                    <div class="idds-card mx-auto mb-4 flex h-20 w-20 items-center justify-center p-2">
                        <img src="{{ asset('images/logo-gorontalo.svg') }}" alt="Lambang Provinsi Gorontalo" width="64" height="64" class="h-16 w-16 object-contain drop-shadow-md">
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink">{{ config('app.name') }}</h1>
                    <p class="mt-1 text-xs text-ink-muted">Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit</p>
                    <p class="mt-1 text-xs font-medium text-ink-muted">Inspektorat Provinsi Gorontalo</p>
                </div>

                {{-- Card 2FA --}}
                <section aria-labelledby="two-factor-heading" class="idds-card p-6 sm:p-8 shadow-xl">
                    <div class="mb-5 flex items-center justify-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-700 ring-8 ring-primary-50/50">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>

                    <h2 id="two-factor-heading" class="mb-1 text-center text-lg font-bold text-ink">Verifikasi Dua Langkah (2FA)</h2>
                    <p class="mb-4 text-center text-xs text-ink-muted">Perlindungan Akun Tingkat Lanjut Super Admin</p>

                    <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50/80 p-3.5 text-xs text-blue-900 leading-relaxed">
                        <p class="font-medium text-blue-950 mb-1">Kode One-Time Password (OTP) telah dikirim ke:</p>
                        <p class="font-bold text-primary-700 tracking-wide font-mono text-sm">{{ $maskedEmail }}</p>
                        <p class="mt-1 text-[11px] text-blue-800">Kode ini berlaku selama 10 menit. Masukkan 6 digit kode untuk melanjutkan.</p>
                    </div>

                    <x-flash-message type="success" :message="session('success')" />
                    <x-flash-message type="error" :message="session('error')" />
                    <x-flash-message type="warning" :message="session('warning')" />
                    <x-flash-message type="info" :message="session('info')" />

                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('2fa.verify') }}" autocomplete="off" id="twoFactorForm" class="space-y-4">
                        @csrf

                        <div>
                            <label for="otp" class="idds-field-label mb-1.5 text-center block">Kode Verifikasi OTP (6 Digit)</label>
                            <input type="text"
                                   id="otp"
                                   name="otp"
                                   inputmode="numeric"
                                   pattern="[0-9]*"
                                   maxlength="6"
                                   required
                                   autofocus
                                   autocomplete="one-time-code"
                                   placeholder="••••••"
                                   class="w-full text-center tracking-[0.5em] font-mono text-2xl font-bold py-3 idds-form-control focus:border-primary-600 focus:ring-primary-600"
                                   @if ($errors->has('otp')) aria-invalid="true" aria-describedby="otp-error" @endif>
                            @error('otp')
                                <p id="otp-error" class="mt-1.5 text-center text-xs font-semibold text-state-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-action-button type="submit" id="verifyBtn" variant="primary" class="w-full py-2.5 active:scale-[0.99]">
                            Verifikasi & Masuk
                        </x-action-button>
                    </form>

                    <div class="mt-6 border-t border-border pt-4 space-y-3">
                        {{-- Resend OTP --}}
                        <div class="text-center">
                            <form method="POST" action="{{ route('2fa.resend') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-primary-700 hover:text-primary-800 hover:underline inline-flex items-center gap-1 cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Tidak menerima kode? Kirim Ulang OTP
                                </button>
                            </form>
                        </div>

                        {{-- Cancel and back to login --}}
                        <div class="text-center">
                            <form method="POST" action="{{ route('2fa.cancel') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs text-ink-muted hover:text-ink hover:underline cursor-pointer">
                                    Batal & Kembali ke Halaman Login
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="mt-6 w-full text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Inspektorat Provinsi Gorontalo
        </footer>
    </div>

    <script>
        // Auto-format OTP: only numbers, max 6
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
            });
        }

        document.getElementById('twoFactorForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('verifyBtn');
            if (!btn || btn.disabled) {
                e.preventDefault();
                return;
            }
            btn.disabled = true;
            btn.innerHTML = `<svg class="h-4 w-4 animate-spin inline mr-1.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Memverifikasi...`;
        });
    </script>
</body>
</html>
