<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login attempt with Rate Limiting.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $throttleKey = strtolower(trim($request->input('email'))) . '|' . $request->ip();

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            \App\Services\AuditLogger::log('LOGIN_LOCKED', 'AUTH', 'Terlalu banyak percobaan login gagal untuk email: ' . $request->input('email') . ' dari IP: ' . $request->ip());

            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login gagal. Silakan coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);
            $user = Auth::user();

            if ($user->activation_required) {
                \App\Services\AuditLogger::log('LOGIN_FAILED', 'AUTH', 'Percobaan login dengan akun yang belum diaktivasi: ' . $request->input('email'));
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda belum diaktivasi. Periksa email aktivasi atau hubungi Super Admin untuk mengirim ulang tautan.',
                ])->onlyInput('email');
            }

            if (! $user->is_active) {
                \App\Services\AuditLogger::log('LOGIN_FAILED', 'AUTH', 'Percobaan login dengan akun dinonaktifkan: ' . $request->input('email'));
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda dinonaktifkan. Silakan hubungi Super Admin.',
                ])->onlyInput('email');
            }

            // Check if user has a role
            if (! $user->role) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Akun Anda belum memiliki role. Hubungi administrator.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            \App\Services\AuditLogger::log('LOGIN', 'AUTH', 'Pengguna berhasil masuk ke sistem GATRA-GO.', null, null, $user);

            return redirect()->intended(route('dashboard'));
        }

        \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

        \App\Services\AuditLogger::log('LOGIN_FAILED', 'AUTH', 'Percobaan login gagal dengan email: ' . $request->input('email'));

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            \App\Services\AuditLogger::log('LOGOUT', 'AUTH', 'Pengguna telah keluar dari sistem.', null, null, $user);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
