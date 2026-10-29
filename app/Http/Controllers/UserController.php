<?php

namespace App\Http\Controllers;

use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\AccountPasswordResetNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $roleId = $request->input('role_id');
        $status = $request->input('status');

        $users = User::with(['role', 'pegawai', 'opd'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($roleId, function ($query, $roleId) {
                $query->where('role_id', $roleId);
            })
            ->when($status === 'pending', function ($query): void {
                $query->where('is_active', false)
                    ->where('activation_required', true)
                    ->whereNull('activated_at');
            })
            ->when($status === '1', function ($query): void {
                $query->where('is_active', true);
            })
            ->when($status === '0', function ($query): void {
                $query->where('is_active', false)->where('activation_required', false);
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        $roles = Role::all();

        return view('user.index', compact('users', 'roles', 'search', 'roleId', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        $pegawais = Pegawai::active()
            ->whereDoesntHave('user')
            ->orderBy('nama')
            ->get();
        $opds = Opd::active()->orderBy('nama')->get();

        return view('user.create', compact('roles', 'pegawais', 'opds'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $role = Role::find($request->input('role_id'));
        $this->fillIdentityFromPegawai($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'pegawai_id' => $this->pegawaiIdRules($role),
            'opd_id' => ['nullable', 'exists:opds,id'],
        ], [
            'pegawai_id.required' => 'Pilih pegawai aktif dari Master Pegawai untuk akun Super Admin atau Admin Inspektorat.',
            'pegawai_id.exists' => 'Pegawai harus terdaftar dan berstatus aktif di Master Pegawai.',
            'pegawai_id.unique' => 'Pegawai ini sudah terhubung dengan akun pengguna. Pilih pegawai lain atau periksa akun yang sudah terhubung.',
        ]);

        // If role is OPD, make sure opd_id is set
        $role = Role::find($validated['role_id']);
        if ($role && $role->isOpd() && empty($validated['opd_id'])) {
            return back()->withInput()->withErrors(['opd_id' => 'Harap pilih instansi OPD untuk akun pengguna OPD.']);
        }

        $validated['password'] = Hash::make(Str::random(64));
        $validated['is_active'] = false;
        $validated['activation_required'] = true;

        $user = DB::transaction(function () use ($validated): User {
            if (! empty($validated['pegawai_id'])) {
                $this->assertPegawaiLinkAvailable((int) $validated['pegawai_id']);
            }

            return User::create($validated);
        }, 3);

        try {
            $status = $this->sendActivationLink($user);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('user.index')->with('warning', 'Akun dibuat dalam status menunggu aktivasi, tetapi email belum berhasil diproses. Periksa konfigurasi email lalu kirim ulang tautan aktivasi.');
        }

        return $this->activationLinkResponse($status, $user, 'Akun dibuat. Tautan aktivasi telah diproses untuk email pengguna.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = Role::all();
        $pegawais = Pegawai::withTrashed()
            ->where(function ($query) use ($user): void {
                $query->where(function ($availablePegawais): void {
                    $availablePegawais
                        ->where('is_active', true)
                        ->whereNull('deleted_at')
                        ->whereDoesntHave('user');
                });

                if ($user->pegawai_id) {
                    $query->orWhereKey($user->pegawai_id);
                }
            })
            ->orderBy('nama')
            ->get();
        $opds = Opd::active()->orderBy('nama')->get();
        $linkedAccountCount = $user->pegawai_id
            ? User::query()->where('pegawai_id', $user->pegawai_id)->count()
            : 0;

        return view('user.edit', compact('user', 'roles', 'pegawais', 'opds', 'linkedAccountCount'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $pendingActivation = $this->isPendingActivation($user);
        $oldEmail = $user->email;
        $role = Role::find($request->input('role_id'));
        $keepingCurrentPegawai = $user->pegawai_id !== null
            && (int) $request->input('pegawai_id') === (int) $user->pegawai_id;
        $this->fillIdentityFromPegawai($request, $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['prohibited'],
            'role_id' => ['required', 'exists:roles,id'],
            'pegawai_id' => $this->pegawaiIdRules($role, $user, $keepingCurrentPegawai),
            'opd_id' => ['nullable', 'exists:opds,id'],
            'is_active' => $pendingActivation ? ['prohibited'] : ['boolean'],
        ], [
            'pegawai_id.required' => 'Pilih pegawai aktif dari Master Pegawai untuk akun Super Admin atau Admin Inspektorat.',
            'pegawai_id.exists' => 'Pegawai harus terdaftar dan berstatus aktif di Master Pegawai.',
            'pegawai_id.unique' => 'Pegawai ini sudah terhubung dengan akun pengguna. Pilih pegawai lain atau periksa akun yang sudah terhubung.',
            'password.prohibited' => 'Kata sandi diatur melalui tautan reset yang dikirim ke email akun.',
        ]);

        $validated['is_active'] = $pendingActivation ? false : $request->has('is_active');

        // Prevent self-deactivation
        if ($user->id === auth()->id() && ! $validated['is_active']) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // If role is OPD, make sure opd_id is set
        $role = Role::find($validated['role_id']);
        if ($role && $role->isOpd() && empty($validated['opd_id'])) {
            return back()->withInput()->withErrors(['opd_id' => 'Harap pilih instansi OPD untuk akun pengguna OPD.']);
        }

        DB::transaction(function () use ($validated, $user, $keepingCurrentPegawai): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! empty($validated['pegawai_id']) && ! $keepingCurrentPegawai) {
                $this->assertPegawaiLinkAvailable((int) $validated['pegawai_id'], $lockedUser);
            }

            $lockedUser->update($validated);
        }, 3);

        $user->refresh();

        $emailChanged = strcasecmp($oldEmail, $validated['email']) !== 0;
        if ($emailChanged) {
            DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
                ->where('email', $oldEmail)
                ->delete();

            if (! $pendingActivation) {
                $user->forceFill(['email_verified_at' => null])->save();
            }
        }

        if ($pendingActivation && $emailChanged) {
            try {
                $status = $this->sendActivationLink($user->fresh());
            } catch (Throwable $exception) {
                report($exception);

                return redirect()->route('user.index')->with('warning', 'Data pengguna diperbarui, tetapi email aktivasi ke alamat baru belum berhasil diproses. Kirim ulang setelah konfigurasi email diperiksa.');
            }

            return $this->activationLinkResponse($status, $user, 'Data pengguna diperbarui. Tautan aktivasi diproses ke alamat email yang baru.');
        }

        return redirect()->route('user.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Toggle active status of user.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($this->isPendingActivation($user)) {
            return back()->with('warning', 'Akun ini harus diaktifkan melalui tautan email. Gunakan Kirim Ulang Aktivasi bila pengguna belum menerima email.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun pengguna {$user->name} berhasil {$statusText}.");
    }

    /**
     * Resend an activation link to a pending account.
     */
    public function sendActivation(User $user): RedirectResponse
    {
        if (! $this->isPendingActivation($user)) {
            return back()->with('error', 'Akun ini tidak sedang menunggu aktivasi.');
        }

        try {
            $status = $this->sendActivationLink($user);
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('warning', 'Email aktivasi belum berhasil diproses. Periksa konfigurasi email dan coba kembali.');
        }

        return $this->activationLinkResponse($status, $user, 'Tautan aktivasi telah diproses untuk email pengguna.');
    }

    /**
     * Send a password reset link to an active user's registered email address.
     */
    public function sendPasswordReset(User $user): RedirectResponse
    {
        if (! $this->isEligibleForPasswordReset($user)) {
            return back()->with('warning', 'Tautan reset hanya dapat dikirim ke akun yang sudah aktif.');
        }

        try {
            if (app()->environment('production') && in_array(config('mail.default'), ['log', 'array', 'failover'], true)) {
                throw new RuntimeException('Mailer produksi harus dikonfigurasi untuk mengirim email reset kata sandi.');
            }

            $status = Password::broker()->sendResetLink(
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

            return back()->with('error', 'Tautan reset sandi belum berhasil dikirim. Periksa konfigurasi email lalu coba kembali.');
        }

        if ($status === Password::RESET_LINK_SENT) {
            AuditLogger::log(
                action: 'PASSWORD_RESET_LINK_SENT',
                module: 'AUTH',
                description: "Tautan reset sandi dikirim ke email akun {$user->email}.",
            );

            $message = "Tautan reset sandi berhasil dikirim ke {$user->email}.";
            if (config('mail.default') === 'log') {
                $message .= ' Mailer lokal hanya mencatat email di storage/logs/laravel.log.';
            } elseif (config('mail.default') === 'array') {
                $message .= ' Mailer lokal tidak mengirim email ke kotak masuk.';
            }

            return back()->with('success', $message);
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->with('warning', 'Tautan reset baru saja dikirim. Tunggu sekitar 60 detik sebelum mengirim ulang.');
        }

        return back()->with('error', 'Tautan reset sandi tidak dapat diproses. Periksa kembali email akun.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('user.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Fill linked account identity fields from the employee master record.
     */
    private function fillIdentityFromPegawai(Request $request, ?User $user = null): void
    {
        if (! $request->filled('pegawai_id')) {
            return;
        }

        $pegawaiId = (int) $request->input('pegawai_id');
        $isCurrentPegawai = $user && (int) $user->pegawai_id === $pegawaiId;
        $pegawai = $isCurrentPegawai
            ? Pegawai::withTrashed()->find($pegawaiId)
            : Pegawai::active()->find($pegawaiId);

        if (! $pegawai) {
            return;
        }

        if (blank($pegawai->email)) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Lengkapi email dinas pada Master Pegawai sebelum menautkan akun.',
            ]);
        }

        $request->merge([
            'name' => $pegawai->nama,
            'email' => $pegawai->email,
        ]);
    }

    /**
     * Get validation rules for an employee link and its account uniqueness.
     *
     * @return array<int, mixed>
     */
    private function pegawaiIdRules(?Role $role, ?User $user = null, bool $keepingCurrentPegawai = false): array
    {
        $rules = [
            $role && in_array($role->name, ['super_admin', 'admin_inspektorat'], true) ? 'required' : 'nullable',
            'integer',
        ];

        $pegawaiExists = Rule::exists('pegawais', 'id');
        if (! $keepingCurrentPegawai) {
            $pegawaiExists->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'));
        }

        $rules[] = $pegawaiExists;

        if (! $keepingCurrentPegawai) {
            $pegawaiUnique = Rule::unique('users', 'pegawai_id')->withoutTrashed();
            if ($user) {
                $pegawaiUnique->ignore($user);
            }

            $rules[] = $pegawaiUnique;
        }

        return $rules;
    }

    /**
     * Lock an employee record and reject another active account link.
     */
    private function assertPegawaiLinkAvailable(int $pegawaiId, ?User $user = null): void
    {
        $pegawai = Pegawai::active()->lockForUpdate()->find($pegawaiId);
        if (! $pegawai) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Pegawai tidak aktif atau tidak ditemukan di Master Pegawai.',
            ]);
        }

        $accounts = User::query()->where('pegawai_id', $pegawai->id);
        if ($user) {
            $accounts->where('id', '!=', $user->id);
        }

        if ($accounts->exists()) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Pegawai ini sudah terhubung dengan akun pengguna. Periksa akun yang sudah terhubung.',
            ]);
        }
    }

    /**
     * Determine whether an account is awaiting its first activation.
     */
    private function isPendingActivation(User $user): bool
    {
        return $user->activation_required && ! $user->activated_at;
    }

    /**
     * Determine whether an account can receive a password reset link.
     */
    private function isEligibleForPasswordReset(User $user): bool
    {
        return $user->is_active && ! $user->activation_required;
    }

    /**
     * Send a short-lived password broker token as an account activation link.
     */
    private function sendActivationLink(User $user): string
    {
        if (! $this->isPendingActivation($user)) {
            return Password::INVALID_USER;
        }

        if (app()->environment('production') && in_array(config('mail.default'), ['log', 'array', 'failover'], true)) {
            throw new RuntimeException('Mailer produksi harus dikonfigurasi untuk mengirim email aktivasi.');
        }

        return Password::broker()->sendResetLink(
            ['email' => $user->email],
            function (User $account, string $token): string {
                if (! $this->isPendingActivation($account)) {
                    return Password::INVALID_USER;
                }

                $account->notify(new AccountActivationNotification($token));

                return Password::RESET_LINK_SENT;
            }
        );
    }

    /**
     * Return a user-facing response for activation-link dispatch.
     */
    private function activationLinkResponse(string $status, User $user, string $successMessage): RedirectResponse
    {
        if ($status === Password::RESET_LINK_SENT) {
            if (config('mail.default') === 'log') {
                $successMessage .= ' Mailer lokal hanya mencatat email di storage/logs/laravel.log.';
            } elseif (config('mail.default') === 'array') {
                $successMessage .= ' Mailer lokal tidak mengirim email ke kotak masuk.';
            }

            AuditLogger::log(
                action: 'ACCOUNT_ACTIVATION_EMAIL_PROCESSED',
                module: 'AUTH',
                description: "Tautan aktivasi diproses untuk akun {$user->email}.",
            );

            return redirect()->route('user.index')->with('success', $successMessage);
        }

        if ($status === Password::RESET_THROTTLED) {
            return redirect()->route('user.index')->with('warning', 'Tautan aktivasi baru saja diproses. Tunggu sekitar 60 detik sebelum mengirim ulang.');
        }

        return redirect()->route('user.index')->with('error', 'Tautan aktivasi tidak dapat diproses. Periksa kembali email pengguna.');
    }
}
