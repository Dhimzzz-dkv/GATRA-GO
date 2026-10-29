<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\EnforcesLazyLoading;

class TindakLanjut extends Model
{
    use HasFactory, SoftDeletes, EnforcesLazyLoading;

    protected $fillable = [
        'lhp_id',
        'temuan_id',
        'rekomendasi_id',
        'opd_id',
        'tanggal_tindak_lanjut',
        'uraian_tindak_lanjut',
        'jumlah_setor',
        'nomor_bukti_setor',
        'tanggal_setor',
        'dokumen_bukti',
        'nama_dokumen_asli',
        'dokumen_setor',
        'nama_dokumen_setor_asli',
        'status_verifikasi',
        'catatan_verifikasi',
        'verified_by',
        'verified_at',
        'created_by',
    ];

    protected $casts = [
        'tanggal_tindak_lanjut' => 'date',
        'tanggal_setor' => 'date',
        'jumlah_setor' => 'float',
        'verified_at' => 'datetime',
    ];

    /**
     * Dokumen LHP terkait tindak lanjut.
     *
     * withTrashed() disertakan agar tindak lanjut historis tetap dapat
     * menampilkan nomor LHP-nya meskipun LHP induknya sudah diarsipkan.
     */
    public function lhp(): BelongsTo
    {
        return $this->belongsTo(Lhp::class)->withTrashed();
    }

    /**
     * Temuan yang ditindaklanjuti.
     *
     * withTrashed() wajib: tindak lanjut yang sudah tercatat harus tetap
     * menampilkan konteks temuan aslinya walau temuan tersebut kemudian
     * dihapus/diarsipkan. Bila relasi ini mengembalikan null, seluruh kolom
     * temuan (judul, penyebab, status) hilang dari halaman monitoring.
     */
    public function temuan(): BelongsTo
    {
        return $this->belongsTo(Temuan::class)->withTrashed();
    }

    /**
     * Rekomendasi spesifik yang ditindaklanjuti (khusus temuan administrasi bertahap).
     */
    public function rekomendasi(): BelongsTo
    {
        return $this->belongsTo(Rekomendasi::class);
    }

    /**
     * Instansi OPD pelaksana tindak lanjut.
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Pengguna OPD pembuat tindak lanjut.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Verifikator Inspektorat / Super Admin.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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
            return $query->where(function ($sub) use ($user) {
                $sub->whereHas('temuan', function ($tq) use ($user) {
                    $tq->where('sumber_pemeriksaan', $user->getSumberPemeriksaan());
                })->orWhereHas('lhp.pemeriksaan', function ($lq) use ($user) {
                    $lq->where('sumber_pemeriksaan', $user->getSumberPemeriksaan());
                });
            });
        }

        if ($user->isOpd() && $user->opd_id) {
            return $query->where('opd_id', $user->opd_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Format jumlah setor Rupiah.
     */
    public function getFormattedJumlahSetorAttribute(): string
    {
        if ($this->jumlah_setor <= 0) {
            return 'Rp 0';
        }

        return 'Rp ' . number_format($this->jumlah_setor, 0, ',', '.');
    }

    /**
     * Helper status verifikasi badge.
     */
    public function getStatusVerifikasiBadgeAttribute(): array
    {
        return match ($this->status_verifikasi) {
            'MENUNGGU VERIFIKASI' => [
                'label' => 'Menunggu Verifikasi',
                'class' => 'bg-amber-100 text-amber-800 border-amber-200',
            ],
            'SESUAI' => [
                'label' => 'Diverifikasi Sesuai',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            ],
            'BELUM SESUAI' => [
                'label' => 'Belum Sesuai',
                'class' => 'bg-orange-100 text-orange-800 border-orange-200',
            ],
            'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH' => [
                'label' => 'Tidak Dapat Ditindaklanjuti',
                'class' => 'bg-gray-100 text-gray-800 border-gray-300',
            ],
            'DITOLAK' => [
                'label' => 'Ditolak',
                'class' => 'bg-rose-100 text-rose-800 border-rose-200',
            ],
            default => [
                'label' => $this->status_verifikasi,
                'class' => 'bg-gray-100 text-gray-800 border-gray-200',
            ],
        };
    }
}
