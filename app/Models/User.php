<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\EnforcesLazyLoading;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role_id', 'pegawai_id', 'opd_id', 'is_active', 'activation_required', 'activated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, EnforcesLazyLoading;

    /**
     * Relasi yang selalu dimuat bersama User.
     *
     * `role` dipakai oleh hampir seluruh logika otorisasi — termasuk
     * scopeForUser() pada Pemeriksaan/Temuan/Lhp/TindakLanjut yang dijalankan
     * pada setiap query — sehingga tanpa pemuatan awal akan memicu lazy loading.
     *
     * `pegawai` dipakai oleh accessor official_name, yang ditampilkan sebagai
     * nama penginput / penyetuju / verifikator di banyak halaman. Accessor ini
     * memakai `$this->pegawai?->nama`, dan operator `??` memicu pembacaan
     * relasi, sehingga relasi wajib dimuat lebih dulu.
     *
     * `opd` dipakai pada dashboard untuk menampilkan nama instansi pengguna OPD.
     *
     * User diambil sangat jarang (umumnya sekali per request untuk user
     * terautentikasi, dan sekali per baris pada halaman master data pengguna),
     * sehingga biaya eager load di sini jauh lebih murah daripada risiko
     * halaman 500.
     *
     * @var array<int, string>
     */
    protected $with = ['role', 'pegawai', 'opd'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'activated_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'activation_required' => 'boolean',
        ];
    }

    /**
     * Get the role of this user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the associated Pegawai Inspektorat (if any).
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Get the associated OPD (for OPD users).
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is an Admin Sumber.
     */
    public function isAdminSumber(): bool
    {
        return $this->role && $this->role->isAdminSumber();
    }

    /**
     * Check if user is Pemantau.
     */
    public function isPemantau(): bool
    {
        return $this->hasRole('pemantau');
    }

    /**
     * Check if user is OPD.
     */
    public function isOpd(): bool
    {
        return $this->hasRole('opd');
    }

    /**
     * Check if user is Admin Inspektorat.
     */
    public function isAdminInspektorat(): bool
    {
        return $this->hasRole('admin_inspektorat');
    }

    /**
     * Check if user is Admin BPK.
     */
    public function isAdminBpk(): bool
    {
        return $this->hasRole('admin_bpk');
    }

    /**
     * Check if user is Admin BPKP.
     */
    public function isAdminBpkp(): bool
    {
        return $this->hasRole('admin_bpkp');
    }

    /**
     * Check if user is Admin ITJEN.
     */
    public function isAdminItjen(): bool
    {
        return $this->hasRole('admin_itjen');
    }

    /**
     * Check if user can create or manage SPT.
     * In GATRA-GO, SPT is strictly created and managed by Super Admin and Admin Inspektorat.
     */
    public function canManageSpt(): bool
    {
        return $this->isSuperAdmin() || $this->isAdminInspektorat();
    }

    /**
     * Get the sumber pemeriksaan for this user (if admin sumber).
     */
    public function getSumberPemeriksaan(): ?string
    {
        return $this->role?->sumber_pemeriksaan;
    }

    /**
     * Get official full name (from Pegawai if linked, otherwise User name).
     */
    public function getOfficialNameAttribute(): string
    {
        return $this->pegawai?->nama ?? $this->name;
    }

    /**
     * Get count of pending action items for sidebar badge.
     */
    public function getPendingVerifikasiCount(): int
    {
        if ($this->isSuperAdmin() || $this->isAdminSumber()) {
            return \App\Models\TindakLanjut::forUser($this)->where('status_verifikasi', 'MENUNGGU VERIFIKASI')->count();
        }

        if ($this->isOpd() && $this->opd_id) {
            return \App\Models\TindakLanjut::forUser($this)->where('status_verifikasi', 'BELUM SESUAI')->count();
        }

        return 0;
    }
}
