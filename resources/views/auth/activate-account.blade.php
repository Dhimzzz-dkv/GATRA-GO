<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Aktivasi akun GATRA-GO Inspektorat Provinsi Gorontalo">

    <title>Aktivasi Akun — {{ config('app.name') }}</title>

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

                <section aria-labelledby="activation-heading" class="idds-card p-6 sm:p-8">
                    <h2 id="activation-heading" class="mb-2 text-center text-lg font-semibold text-ink">Aktifkan Akun</h2>
                    <p class="mb-6 text-center text-sm text-ink-muted">Buat kata sandi untuk menyelesaikan aktivasi akun {{ config('app.name') }} Anda.</p>

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('account.activation.complete') }}" class="space-y-4" autocomplete="on">
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email', $email) }}">
                        <input type="hidden" name="token" value="{{ old('token', $token) }}">

                        <div>
                            <label for="password" class="idds-field-label mb-1.5">Kata Sandi Baru</label>
                            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                                   class="w-full idds-form-control"
                                   placeholder="Minimal 8 karakter"
                                   aria-describedby="password-requirements @if ($errors->has('password')) password-error @endif"
                                   @if ($errors->has('password')) aria-invalid="true" @endif>
                            <p id="password-requirements" class="mt-1 text-xs text-ink-muted">Gunakan minimal 8 karakter, termasuk huruf besar, huruf kecil, angka, dan simbol.</p>
                            @error('password')
                                <p id="password-error" class="mt-1 text-sm text-state-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="idds-field-label mb-1.5">Konfirmasi Kata Sandi</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"
                                   class="w-full idds-form-control"
                                   placeholder="Ulangi kata sandi baru">
                        </div>

                        <p class="text-xs text-ink-muted">Tautan aktivasi hanya dapat digunakan sekali dan memiliki batas waktu.</p>

                        <x-action-button type="submit" variant="primary" class="w-full">
                            Aktifkan Akun
                        </x-action-button>
                    </form>
                </section>
            </div>
        </main>

        <footer class="mt-6 w-full text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Inspektorat Provinsi Gorontalo
        </footer>
    </div>
</body>
</html>
