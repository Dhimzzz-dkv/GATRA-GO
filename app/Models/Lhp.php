<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\EnforcesLazyLoading;

class Lhp extends Model
{
    use HasFactory, SoftDeletes, EnforcesLazyLoading;

    protected $fillable = [
        'pemeriksaan_id',
        'nomor_lhp',
        'tanggal_lhp',
        'judul_lhp',
        'dokumen_lhp',
        'nama_dokumen_lhp_asli',
        'ringkasan',
        'created_by',
    ];

    protected $casts = [
        'tanggal_lhp' => 'date',
    ];

    /**
     * Kegiatan pemeriksaan terkait.
     */
    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class);
    }

    /**
     * Pengguna yang menginput LHP.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Daftar OPD target dari LHP ini (Many-to-Many via lhp_opd).
     */
    public function opds(): BelongsToMany
    {
        return $this->belongsToMany(Opd::class, 'lhp_opd')->withTimestamps();
    }

    /**
     * Daftar temuan di dalam LHP ini.
     */
    public function temuans(): HasMany
    {
        return $this->hasMany(Temuan::class);
    }

    /**
     * Riwayat tindak lanjut terkait LHP ini.
     */
    public function tindakLanjuts(): HasMany
    {
        return $this->hasMany(TindakLanjut::class)->orderBy('tanggal_tindak_lanjut', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Scope query berdasarkan hak akses pengguna.
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->isSuperAdmin() || $user->isPemantau()) {
            return $query;
        }

        if ($user->isAdminSumber()) {
            return $query->whereHas('pemeriksaan', function ($pq) use ($user) {
                $pq->where('sumber_pemeriksaan', $user->getSumberPemeriksaan());
            });
        }

        if ($user->isOpd() && $user->opd_id) {
            return $query->where(function ($q) use ($user) {
                $q->whereHas('opds', function ($oq) use ($user) {
                    $oq->where('opds.id', $user->opd_id);
                })->orWhereHas('pemeriksaan', function ($pq) use ($user) {
                    $pq->where('opd_id', $user->opd_id);
                });
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Total nilai kerugian dari semua temuan di bawah LHP ini.
     */
    public function getTotalKerugianAttribute(): float
    {
        return (float) $this->temuans()->where('ada_unsur_kerugian', true)->sum('nilai_kerugian');
    }

    public function getFormattedTotalKerugianAttribute(): string
    {
        return 'Rp ' . number_format($this->total_kerugian, 0, ',', '.');
    }

    public function getTotalKerugianInternalAttribute(): float
    {
        return (float) $this->temuans()->where('ada_unsur_kerugian', true)->where('jenis_kerugian', 'Internal')->sum('nilai_kerugian');
    }

    public function getFormattedTotalKerugianInternalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_kerugian_internal, 0, ',', '.');
    }

    public function getTotalKerugianEksternalAttribute(): float
    {
        return (float) $this->temuans()->where('ada_unsur_kerugian', true)->where('jenis_kerugian', 'Eksternal')->sum('nilai_kerugian');
    }

    public function getFormattedTotalKerugianEksternalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_kerugian_eksternal, 0, ',', '.');
    }

    public function getKerugianInternalCountAttribute(): int
    {
        return $this->temuans()->where('ada_unsur_kerugian', true)->where('jenis_kerugian', 'Internal')->count();
    }

    public function getKerugianEksternalCountAttribute(): int
    {
        return $this->temuans()->where('ada_unsur_kerugian', true)->where('jenis_kerugian', 'Eksternal')->count();
    }

    public function getTotalKerugianBelumDikategorikanAttribute(): float
    {
        return (float) $this->temuans()->where('ada_unsur_kerugian', true)->where(function ($q) {
            $q->whereNull('jenis_kerugian')->orWhereNotIn('jenis_kerugian', ['Internal', 'Eksternal']);
        })->sum('nilai_kerugian');
    }

    public function getFormattedTotalKerugianBelumDikategorikanAttribute(): string
    {
        return 'Rp ' . number_format($this->total_kerugian_belum_dikategorikan, 0, ',', '.');
    }

    public function getKerugianBelumDikategorikanCountAttribute(): int
    {
        return $this->temuans()->where('ada_unsur_kerugian', true)->where(function ($q) {
            $q->whereNull('jenis_kerugian')->orWhereNotIn('jenis_kerugian', ['Internal', 'Eksternal']);
        })->count();
    }

    /**
     * Total setoran yang sudah diverifikasi 'SESUAI'.
     */
    public function getTotalDisetorAttribute(): float
    {
        // Hitung dari tindak lanjut langsung di LHP atau via temuan di bawah LHP
        $directSetor = (float) $this->tindakLanjuts()->where('status_verifikasi', 'SESUAI')->sum('jumlah_setor');
        $temuanSetor = (float) TindakLanjut::whereIn('temuan_id', $this->temuans()->pluck('id'))
            ->whereNull('lhp_id')
            ->where('status_verifikasi', 'SESUAI')
            ->sum('jumlah_setor');

        return $directSetor + $temuanSetor;
    }

    public function getFormattedTotalDisetorAttribute(): string
    {
        return 'Rp ' . number_format($this->total_disetor, 0, ',', '.');
    }

    /**
     * Sisa kerugian yang belum disetor.
     */
    public function getSisaKerugianAttribute(): float
    {
        return (float) max(0, $this->total_kerugian - $this->total_disetor);
    }

    public function getFormattedSisaKerugianAttribute(): string
    {
        return 'Rp ' . number_format($this->sisa_kerugian, 0, ',', '.');
    }

    /**
     * Persentase penyelesaian (0% - 100%).
     */
    public function getProgressPersenAttribute(): int
    {
        $status = $this->status_penyelesaian;

        if ($status === 'Sesuai' || $status === 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah') {
            return 100;
        }

        if ($this->total_kerugian > 0) {
            $persen = round(($this->total_disetor / $this->total_kerugian) * 100);
            return (int) min(100, max(0, $persen));
        }

        if ($status === 'Belum Sesuai') {
            return 50;
        }

        return 0;
    }

    /**
     * Status penyelesaian LHP otomatis berdasarkan riwayat tindak lanjut dan temuan.
     */
    public function getStatusPenyelesaianAttribute(): string
    {
        $temuans = $this->temuans;
        if ($temuans->isEmpty()) {
            return 'Belum Ditindaklanjuti';
        }

        // 1. Jika ada temuan yang masih Menunggu Verifikasi
        if ($temuans->contains('status_penyelesaian', 'Menunggu Verifikasi')) {
            return 'Menunggu Verifikasi';
        }

        // 2. Cek apakah seluruh temuan sudah selesai (Sesuai / Tidak Dapat Ditindaklanjuti)
        $completedCount = $temuans->filter(function ($t) {
            return in_array($t->status_penyelesaian, ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti']);
        })->count();

        if ($completedCount === $temuans->count()) {
            if ($temuans->every(fn ($t) => str_contains($t->status_penyelesaian, 'Tidak Dapat Ditindaklanjuti'))) {
                return 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah';
            }
            return 'Sesuai';
        }

        // 3. Jika ada temuan yang Belum Sesuai
        if ($temuans->contains('status_penyelesaian', 'Belum Sesuai')) {
            return 'Belum Sesuai';
        }

        // 4. Default: Belum Ditindaklanjuti
        return 'Belum Ditindaklanjuti';
    }

    /**
     * Badge CSS styling untuk status penyelesaian LHP.
     */
    public function getStatusPenyelesaianBadgeAttribute(): array
    {
        return match ($this->status_penyelesaian) {
            'Sesuai' => [
                'label' => 'Sesuai',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'dot' => 'bg-emerald-500',
            ],
            'Menunggu Verifikasi' => [
                'label' => 'Menunggu Verifikasi',
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'dot' => 'bg-blue-500',
            ],
            'Belum Sesuai' => [
                'label' => 'Belum Sesuai',
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'dot' => 'bg-amber-500',
            ],
            'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti' => [
                'label' => 'Tidak Dapat TL',
                'class' => 'bg-gray-100 text-gray-700 border-gray-300',
                'dot' => 'bg-gray-500',
            ],
            default => [
                'label' => 'Belum Ditindaklanjuti',
                'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                'dot' => 'bg-rose-500',
            ],
        };
    }
}

