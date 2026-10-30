<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\TwoFactorOtpNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    /**
     * Show the 2FA OTP verification screen.
     */
    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $userId = $request->session()->get('auth.2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user || ! $user->is_active || ! $user->requiresTwoFactor()) {
            $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);
            return redirect()->route('login');
        }

        $maskedEmail = $this->maskEmail($user->email);

        return view('auth.two-factor-otp', [
            'maskedEmail' => $maskedEmail,
            'expiresAt' => $user->otp_expires_at,
        ]);
    }

    /**
     * Verify the submitted OTP code and authenticate the user.
     */
    public function verify(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('auth.2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user || ! $user->is_active || ! $user->requiresTwoFactor()) {
            $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);
            return redirect()->route('login');
        }

        $throttleKey = '2fa-verify:' . $user->id . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            AuditLogger::log(
                'LOGIN_LOCKED',
                'AUTH',
                "Terlalu banyak percobaan kode OTP 2FA yang salah untuk Super Admin ({$user->email}) dari IP: " . $request->ip(),
                null,
                null,
                $user
            );

            return back()->withErrors([
                'otp' => "Terlalu banyak percobaan verifikasi yang salah. Silakan coba lagi dalam {$seconds} detik.",
            ]);
        }

        $validated = $request->validate([
            'otp' => ['required', 'string', 'min:6', 'max:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.min' => 'Kode OTP harus berupa 6 digit angka.',
            'otp.max' => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        if (! $user->verifyTwoFactorOtp($validated['otp'])) {
            RateLimiter::hit($throttleKey, 60);

            AuditLogger::log(
                'LOGIN_FAILED',
                'AUTH',
                "Percobaan verifikasi kode OTP 2FA tidak valid atau sudah kedaluwarsa untuk Super Admin ({$user->email}).",
                null,
                null,
                $user
            );

            return back()->withErrors([
                'otp' => 'Kode verifikasi OTP salah atau telah kedaluwarsa. Silakan periksa kembali email Anda.',
            ]);
        }

        // OTP verified successfully
        RateLimiter::clear($throttleKey);
        $user->clearTwoFactorOtp();

        $remember = (bool) $request->session()->pull('auth.2fa.remember', false);
        $request->session()->forget('auth.2fa.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        AuditLogger::log(
            'LOGIN',
            'AUTH',
            "Super Admin ({$user->email}) berhasil masuk ke sistem melalui verifikasi dua langkah (2FA OTP via Email).",
            null,
            null,
            $user
        );

        return redirect()->intended(route('dashboard'))->with('success', 'Verifikasi dua langkah (2FA) berhasil. Selamat datang di GATRA-GO!');
    }

    /**
     * Resend a fresh OTP code to the user's email.
     */
    public function resend(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('auth.2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user || ! $user->is_active || ! $user->requiresTwoFactor()) {
            $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);
            return redirect()->route('login');
        }

        $resendKey = '2fa-resend:' . $user->id;
        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            $seconds = RateLimiter::availableIn($resendKey);
            return back()->with('warning', "Mohon tunggu {$seconds} detik sebelum meminta kode OTP baru.");
        }

        RateLimiter::hit($resendKey, 60);

        $otp = $user->generateTwoFactorOtp();
        $emailFailed = false;

        try {
            $user->notify(new TwoFactorOtpNotification($otp));
        } catch (\Throwable $e) {
            report($e);
            $emailFailed = true;
        }

        AuditLogger::log(
            'LOGIN',
            'AUTH',
            "Pengiriman ulang kode OTP 2FA ke email untuk Super Admin ({$user->email}).",
            null,
            null,
            $user
        );

        if ($emailFailed && ! app()->environment('local')) {
            return back()->with('warning', 'Kode OTP baru telah dibangkitkan, namun email tidak dapat dikirimkan ke server mail. Periksa konfigurasi email.');
        }

        return back()->with('success', 'Kode OTP baru telah dikirimkan ke email Anda.');
    }

    /**
     * Cancel 2FA verification and return to login.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);
        return redirect()->route('login')->with('info', 'Proses masuk dengan 2FA telah dibatalkan.');
    }

    /**
     * Mask email address for display (e.g. s***n@inspektorat.go.id).
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0] ?? '';
        $domain = $parts[1] ?? '';

        if (strlen($name) <= 2) {
            $maskedName = substr($name, 0, 1) . '*';
        } else {
            $first = substr($name, 0, 1);
            $last = substr($name, -1);
            $maskedName = $first . str_repeat('*', min(6, strlen($name) - 2)) . $last;
        }

        return $maskedName . '@' . $domain;
    }
}
