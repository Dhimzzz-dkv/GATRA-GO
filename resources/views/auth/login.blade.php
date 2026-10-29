<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="GATRA-GO — Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit Inspektorat Provinsi Gorontalo">

    <title>Login — {{ config('app.name') }}</title>

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
                        <img src="{{ asset('images/logo-gorontalo.svg') }}" alt="Lambang Provinsi Gorontalo" width="64" height="64" style="width: 64px; height: 64px; max-width: 100%; object-fit: contain;" class="h-16 w-16 object-contain drop-shadow-md">
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight text-ink ">{{ config('app.name') }}</h1>
                    <p class="mt-1 text-xs text-ink-muted">Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit</p>
                    <p class="mt-1 text-xs font-medium text-ink-muted">Inspektorat Provinsi Gorontalo</p>
                </div>

                {{-- Login Card --}}
                <section aria-labelledby="login-heading" class="idds-card p-6 sm:p-8">
                    <h2 id="login-heading" class="mb-6 text-center text-lg font-semibold text-ink">Masuk ke Sistem</h2>

                    <x-flash-message type="success" :message="session('success')" />

                    {{-- Error Messages --}}
                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" autocomplete="off" id="loginForm">
                        @csrf

                        {{-- Email --}}
                        <div class="mb-4">
                            <label for="email" class="idds-field-label mb-1.5">Email</label>
                                <input type="email" id="email" name="email" value="" required autocomplete="off"
                                       @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif
                                   class="w-full idds-form-control"
                                   placeholder="email@inspektorat.go.id">
                                @error('email')
                                    <p id="email-error" class="mt-1 text-sm text-state-danger">{{ $message }}</p>
                                @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-4">
                            <label for="password" class="idds-field-label mb-1.5">Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required autocomplete="new-password"
                                       @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif
                                       class="w-full idds-form-control"
                                       placeholder="••••••••">
                                <button type="button" onclick="togglePassword()" aria-label="Tampilkan kata sandi" aria-pressed="false" title="Tampilkan kata sandi" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-800">
                                    <svg id="eye-icon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p id="password-error" class="mt-1 text-sm text-state-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Remember Me --}}
                        <div class="mb-6 flex items-center justify-between gap-3">
                            <div class="flex items-center">
                                <input type="checkbox" id="remember" name="remember" autocomplete="off"
                                       class="h-4 w-4 rounded border-gray-300 bg-white text-primary-700 focus:ring-primary-600">
                                <label for="remember" class="ml-2 text-sm text-gray-600">Ingat saya</label>
                            </div>
                            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-700 hover:text-primary-800">Lupa kata sandi?</a>
                        </div>

                        {{-- Submit --}}
                        <x-action-button type="submit" id="loginBtn" variant="primary" class="w-full active:scale-[0.99]">
                            Masuk
                        </x-action-button>
                    </form>
                </section>
                </div>
        </main>

        {{-- Footer --}}
        <footer class="mt-6 w-full text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Inspektorat Provinsi Gorontalo
        </footer>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const button = input?.nextElementSibling;
            if (!input || !(button instanceof HTMLButtonElement)) return;

            const isVisible = input.type === 'password';
            input.type = isVisible ? 'text' : 'password';
            const label = isVisible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi';
            button.setAttribute('aria-label', label);
            button.setAttribute('aria-pressed', String(isVisible));
            button.title = label;
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            if (btn.disabled) {
                e.preventDefault();
                return;
            }
            btn.disabled = true;
            btn.innerHTML = `<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Memproses...`;
        });
    </script>
</body>
</html>
