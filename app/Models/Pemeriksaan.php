<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Concerns\EnforcesLazyLoading;

class Pemeriksaan extends Model
{
    use HasFactory, SoftDeletes, EnforcesLazyLoading;

    public const JENIS_PENGAJUAN_SPT_DAN_LHP = 'SPT_DAN_LHP';

    public const JENIS_PENGAJUAN_LHP_ONLY = 'LHP_ONLY';

    public $lhp_count_for_message;

    protected $fillable = [
        'sumber_pemeriksaan',
        'jenis_pengajuan',
        'opd_id',
        'tahun',
        'judul',
        'nomor_spt',
        'nama_tim',
        'tanggal_spt',
        'dokumen_spt',
        'nama_dokumen_spt_asli',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_pengajuan',
        'catatan_persetujuan',
        'created_by',
        'approved_by',
        'approved_at',
        'is_archived',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'tanggal_spt' => 'date',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'approved_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    /**
     * OPD yang diperiksa.
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Pengguna yang menginput data pemeriksaan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Super Admin yang menyetujui / mereview.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias reviewer untuk approver.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Anggota tim pemeriksa yang ditugaskan.
     */
    public function timPemeriksas(): HasMany
    {
        return $this->hasMany(TimPemeriksa::class)->orderByRaw("
            CASE peran_tim
                WHEN 'Penanggung Jawab' THEN 1
                WHEN 'Asisten Penanggung Jawab' THEN 2
                WHEN 'Pengendali Teknis' THEN 3
                WHEN 'Ketua Tim' THEN 4
                WHEN 'Anggota Tim' THEN 5
                ELSE 6
            END
        ");
    }

    /**
     * Daftar Pegawai yang bertugas dalam tim pemeriksaan.
     */
    public function pegawais(): BelongsToMany
    {
        return $this->belongsToMany(Pegawai::class, 'tim_pemeriksas')
            ->withPivot('peran_tim', 'keterangan')
            ->withTimestamps();
    }

    /**
     * Dokumen Laporan Hasil Pemeriksaan (LHP).
     */
    public function lhps(): HasMany
    {
        return $this->hasMany(Lhp::class)->orderBy('tanggal_lhp', 'desc');
    }

    /**
     * Daftar temuan dari kegiatan pemeriksaan ini.
     */
    public function temuans(): HasMany
    {
        return $this->hasMany(Temuan::class)->orderBy('created_at', 'asc');
    }

    /**
     * Scope filter berdasarkan kewenangan pengguna.
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->isSuperAdmin() || $user->isPemantau()) {
            return $query->where('status_pengajuan', '!=', 'DRAFT');
        }

        if ($user->isAdminSumber()) {
            return $query->where('sumber_pemeriksaan', $user->getSumberPemeriksaan());
        }

        if ($user->isOpd() && $user->opd_id) {
            return $query->where(function ($q) use ($user) {
                $q->where('opd_id', $user->opd_id)
                    ->orWhereHas('lhps.opds', function ($oq) use ($user) {
                        $oq->where('opds.id', $user->opd_id);
                    });
            });
        }

        return $query->whereRaw('1 = 0'); // Deny if role undefined
    }

    /**
     * Scope aktif (tidak diarsip).
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Cek apakah SEMUA temuan pada pemeriksaan ini sudah selesai ditindaklanjuti.
     * Kondisi selesai: status_penyelesaian = 'Sesuai' atau mengandung 'Tidak Dapat Ditindaklanjuti'.
     */
    public function isFullyResolved(): bool
    {
        $temuans = $this->temuans()->withoutGlobalScopes()->where('is_archived', false)->get();

        // Jika tidak ada temuan, dianggap belum ada yang perlu diselesaikan
        if ($temuans->isEmpty()) {
            return false;
        }

        $resolvedStatuses = ['Sesuai', 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti'];

        return $temuans->every(fn ($t) => in_array($t->status_penyelesaian, $resolvedStatuses));
    }

    /**
     * Helper status pengajuan badge class.
     */
    public function getStatusPengajuanBadgeAttribute(): array
    {
        return match ($this->status_pengajuan) {
            'DRAFT' => [
                'label' => 'Draft',
                'class' => 'bg-gray-100 text-gray-700 border-gray-200',
            ],
            'DIAJUKAN', 'MENUNGGU PERSETUJUAN' => [
                'label' => 'Menunggu Persetujuan',
                'class' => 'bg-amber-100 text-amber-800 border-amber-200',
            ],
            'DISETUJUI' => [
                'label' => 'Disetujui',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            ],
            'PERLU REVISI', 'DITOLAK' => [
                'label' => 'Perlu Revisi',
                'class' => 'bg-orange-100 text-orange-800 border-orange-200',
            ],
            default => [
                'label' => $this->status_pengajuan,
                'class' => 'bg-gray-100 text-gray-700 border-gray-200',
            ],
        };
    }

    public function getIsLhpOnlySubmissionAttribute(): bool
    {
        return $this->jenis_pengajuan === self::JENIS_PENGAJUAN_LHP_ONLY;
    }

    /**
     * Apakah pemeriksaan ini memiliki temuan kerugian daerah.
     */
    public function getHasKerugianAttribute(): bool
    {
        return $this->temuans()->where('ada_unsur_kerugian', true)->exists();
    }

    /**
     * Total nilai kerugian daerah dari seluruh temuan.
     */
    public function getTotalKerugianAttribute(): float
    {
        return (float) $this->temuans()->where('ada_unsur_kerugian', true)->sum('nilai_kerugian');
    }

    public function getFormattedTotalKerugianAttribute(): string
    {
        return 'Rp ' . number_format($this->total_kerugian, 0, ',', '.');
    }

    /**
     * Total kerugian sub kategori Internal (di lingkungan kantor/OPD).
     */
    public function getTotalKerugianInternalAttribute(): float
    {
        return (float) $this->temuans()->where('ada_unsur_kerugian', true)->where('jenis_kerugian', 'Internal')->sum('nilai_kerugian');
    }

    public function getFormattedTotalKerugianInternalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_kerugian_internal, 0, ',', '.');
    }

    /**
     * Total kerugian sub kategori Eksternal (pada pihak ketiga/rekanan).
     */
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
}
