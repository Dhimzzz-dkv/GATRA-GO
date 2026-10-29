<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountActivationController extends Controller
{
    /**
     * Show the password setup form for a valid activation token.
     */
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email');
        $user = is_string($email) ? User::query()->where('email', $email)->first() : null;

        if (! $user || ! $this->isPendingActivation($user) || ! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('login')->with('error', 'Tautan aktivasi tidak valid atau sudah kedaluwarsa. Hubungi Super Admin untuk meminta tautan baru.');
        }

        return view('auth.activate-account', [
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    /**
     * Set the user's password and activate the account once.
     */
    public function activate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi harus memiliki minimal 8 karakter.',
            'password.mixed' => 'Kata sandi harus mengandung minimal satu huruf besar dan satu huruf kecil.',
            'password.numbers' => 'Kata sandi harus mengandung minimal satu angka.',
            'password.symbols' => 'Kata sandi harus mengandung minimal satu simbol.',
        ]);

        $rateLimitKey = 'account-activation:' . hash(
            'sha256',
            strtolower(trim($validated['email'])) . '|' . $request->ip()
        );

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->withErrors([
                'token' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ])->withInput($request->only('email', 'token'));
        }

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user || ! $this->isPendingActivation($user)) {
            RateLimiter::hit($rateLimitKey, 900);

            return back()->withErrors([
                'token' => 'Tautan aktivasi tidak valid atau sudah kedaluwarsa. Hubungi Super Admin untuk meminta tautan baru.',
            ])->withInput($request->only('email', 'token'));
        }

        $status = Password::broker()->reset($validated, function (User $account, string $password): void {
            DB::transaction(function () use ($account, $password): void {
                $lockedAccount = User::query()->lockForUpdate()->find($account->id);

                if (! $lockedAccount || ! $this->isPendingActivation($lockedAccount)) {
                    throw ValidationException::withMessages([
                        'token' => 'Tautan aktivasi tidak valid atau sudah digunakan.',
                    ]);
                }

                $lockedAccount->forceFill([
                    'password' => $password,
                    'is_active' => true,
                    'activation_required' => false,
                    'activated_at' => now(),
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($lockedAccount));
                AuditLogger::log(
                    action: 'ACCOUNT_ACTIVATED',
                    module: 'AUTH',
                    description: "Pengguna {$lockedAccount->email} mengaktifkan akun melalui tautan email.",
                    user: $lockedAccount,
                );
            });
        });

        if ($status === Password::PASSWORD_RESET) {
            RateLimiter::clear($rateLimitKey);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Akun berhasil diaktifkan. Silakan masuk menggunakan email dan kata sandi yang baru dibuat.');
        }

        RateLimiter::hit($rateLimitKey, 900);

        return back()->withErrors([
            'token' => 'Tautan aktivasi tidak valid atau sudah kedaluwarsa. Hubungi Super Admin untuk meminta tautan baru.',
        ])->withInput($request->only('email', 'token'));
    }

    /**
     * Determine whether the account is still awaiting first activation.
     */
    private function isPendingActivation(User $user): bool
    {
        return $user->activation_required && ! $user->activated_at;
    }
}
