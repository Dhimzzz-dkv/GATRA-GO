<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\EnforcesLazyLoading;

class Pegawai extends Model
{
    use HasFactory, SoftDeletes, EnforcesLazyLoading;

    public const INSPEKTUR_JABATAN = 'Inspektur Daerah Provinsi Gorontalo';

    protected $fillable = [
        'nip',
        'nama',
        'jabatan',
        'pangkat_golongan',
        'unit_bidang',
        'no_hp',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get user account linked to this pegawai.
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Penugasan tim pemeriksaan untuk pegawai ini.
     */
    public function timPemeriksas(): HasMany
    {
        return $this->hasMany(TimPemeriksa::class);
    }

    /**
     * Kegiatan pemeriksaan yang diikuti pegawai ini.
     */
    public function pemeriksaans(): BelongsToMany
    {
        return $this->belongsToMany(Pemeriksaan::class, 'tim_pemeriksas')
            ->withPivot('peran_tim', 'keterangan')
            ->withTimestamps();
    }

    /**
     * Scope query to only active pegawais.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInspekturDaerah(Builder $query): Builder
    {
        return $query->active()->where('jabatan', self::INSPEKTUR_JABATAN);
    }

    /**
     * Nama resmi pegawai untuk ditampilkan pada pilihan.
     *
     * Diselaraskan dengan User::getOfficialNameAttribute() agar pemakaian
     * `official_name` konsisten pada kedua model. Sebelumnya accessor ini
     * hanya ada di User, sehingga daftar pilihan anggota tim pemeriksa pada
     * wizard menampilkan nama kosong.
     */
    public function getOfficialNameAttribute(): string
    {
        return $this->nama ?? '';
    }

    /**
     * Booted model events.
     */
    protected static function booted(): void
    {
        static::updated(function (Pegawai $pegawai) {
            if ($pegawai->wasChanged('nama')) {
                User::where('pegawai_id', $pegawai->id)->update([
                    'name' => $pegawai->nama,
                ]);
            }
        });
    }
}

