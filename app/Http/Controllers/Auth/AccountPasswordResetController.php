<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AccountPasswordResetNotification;
use App\Services\AuditLogger;
use App\Services\PasswordHistoryService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class AccountPasswordResetController extends Controller
{
    public function __construct(private readonly PasswordHistoryService $passwordHistoryService)
    {
    }

    /**
     * Show the password reset request form.
     */
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Send a reset link without disclosing whether an account exists.
     */
    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $genericStatus = 'Jika email tersebut terdaftar pada akun aktif, tautan reset kata sandi akan dikirim. Periksa kotak masuk dan folder spam.';
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! $this->isEligibleForPasswordReset($user)) {
            return redirect()->route('password.request')->with('status', $genericStatus);
        }

        try {
            if (app()->environment('production') && in_array(config('mail.default'), ['log', 'array', 'failover'], true)) {
                throw new RuntimeException('Mailer produksi harus dikonfigurasi untuk mengirim email reset kata sandi.');
            }

            Password::broker()->sendResetLink(
                ['email' => $user->email],
                function (User $account, string $token): string {
                    if (! $this->isEligibleForPasswordReset($account)) {
                        return Password::INVALID_USER;
                    }

                    $account->notify(new AccountPasswordResetNotification($token));

                    return Password::RESET_LINK_SENT;
                }
            );
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('password.request')->with('status', $genericStatus);
    }

    /**
     * Show the reset form only for a valid, unexpired reset token.
     */
    public function showResetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email');
        $user = is_string($email) ? User::query()->where('email', $email)->first() : null;

        if (! $user || ! $this->isEligibleForPasswordReset($user) || ! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('password.request')->with('error', 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.');
        }

        return view('auth.reset-password', [
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    /**
     * Validate and save a new password without flashing password fields to the session.
     */
    public function reset(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
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

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('email', 'token'));
        }

        $validated = $validator->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! $this->isEligibleForPasswordReset($user) || ! Password::broker()->tokenExists($user, $validated['token'])) {
            return redirect()->route('password.request')->with('error', 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.');
        }

        $status = Password::broker()->reset($validated, function (User $account, string $password): void {
            DB::transaction(function () use ($account, $password): void {
                $lockedAccount = User::query()->lockForUpdate()->find($account->id);

                if (! $lockedAccount || ! $this->isEligibleForPasswordReset($lockedAccount)) {
                    throw ValidationException::withMessages([
                        'token' => 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
                    ]);
                }

                if ($this->passwordHistoryService->hasBeenUsed($lockedAccount, $password)) {
                    throw ValidationException::withMessages([
                        'password' => 'Kata sandi sudah pernah digunakan, Gunakan sandi yang baru',
                    ]);
                }

                $this->passwordHistoryService->recordCurrentPassword($lockedAccount);

                $lockedAccount->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($lockedAccount));
                AuditLogger::log(
                    action: 'PASSWORD_RESET',
                    module: 'AUTH',
                    description: "Pengguna {$lockedAccount->email} mengatur ulang kata sandi melalui tautan email.",
                    user: $lockedAccount,
                );
            });
        });

        if ($status !== Password::PASSWORD_RESET) {
            return redirect()->route('password.request')->with('error', 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru.');
    }

    /**
     * Determine whether the account is active and has completed activation.
     */
    private function isEligibleForPasswordReset(User $user): bool
    {
        return $user->is_active && ! $user->activation_required;
    }
}
