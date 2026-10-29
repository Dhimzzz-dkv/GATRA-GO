<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Pemulihan kata sandi akun GATRA-GO">

    <title>Lupa Kata Sandi — {{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-gorontalo.svg') }}">
    <link rel="preload" href="{{ asset('fonts/InterVariable.ttf') }}" as="font" type="font/ttf" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface-muted font-sans antialiased">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <main id="main-content" tabindex="-1" class="flex w-full flex-1 items-center justify-center">
            <div class="w-full max-w-md">
                <div class="mb-8 text-center">
                    <div class="idds-card mx-auto mb-4 flex h-20 w-20 items-center justify-center p-2">
                        <img src="{{ asset('images/logo-gorontalo.svg') }}" alt="Lambang Provinsi Gorontalo" width="64" height="64" class="h-16 w-16 object-contain">
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink">{{ config('app.name') }}</h1>
                    <p class="mt-1 text-xs text-ink-muted">Inspektorat Provinsi Gorontalo</p>
                </div>

                <section aria-labelledby="forgot-password-heading" class="idds-card p-6 sm:p-8">
                    <h2 id="forgot-password-heading" class="mb-2 text-center text-lg font-semibold text-ink">Lupa Kata Sandi?</h2>
                    <p class="mb-6 text-center text-sm text-ink-muted">Masukkan email akun Anda. Jika email terdaftar dan akun aktif, kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>

                    @if (session('status'))
                        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                            <p role="status">{{ session('status') }}</p>
                            <details class="mt-3 border-t border-green-200 pt-3">
                                <summary class="cursor-pointer font-medium text-green-800 underline underline-offset-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-700">
                                    Tidak menerima email? Masukkan email lagi
                                </summary>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-4 @if (session('status')) mt-3 @endif" autocomplete="off">
                        @csrf

                        <div>
                            <label for="email" class="idds-field-label mb-1.5">Email</label>
                            <input type="email" id="email" name="email" value="" required maxlength="255" autocomplete="off"
                                   class="w-full idds-form-control"
                                   placeholder="email@inspektorat.go.id"
                                   @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                            @error('email')
                                <p id="email-error" class="mt-1 text-sm text-state-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-action-button type="submit" variant="primary" class="w-full">
                            Kirim Tautan Reset
                        </x-action-button>
                    </form>

                    @if (session('status'))
                            </details>
                        </div>
                    @endif

                    <p class="mt-5 text-center text-sm">
                        <a href="{{ route('login') }}" class="font-medium text-primary-700 hover:text-primary-800">Kembali ke halaman login</a>
                    </p>
                </section>
            </div>
        </main>

        <footer class="mt-6 w-full text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Inspektorat Provinsi Gorontalo
        </footer>
    </div>
</body>
</html>
