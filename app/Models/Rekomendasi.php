<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\EnforcesLazyLoading;

class Rekomendasi extends Model
{
    use HasFactory, SoftDeletes, EnforcesLazyLoading;

    protected $fillable = [
        'temuan_id',
        'uraian_rekomendasi',
        'status_penyelesaian',
        'alasan_khusus',
        'dokumen_pendukung_alasan',
        'created_by',
    ];

    /**
     * Temuan induk yang memiliki rekomendasi ini.
     */
    public function temuan(): BelongsTo
    {
        return $this->belongsTo(Temuan::class);
    }

    /**
     * Riwayat tindak lanjut terhadap rekomendasi ini.
     */
    public function tindakLanjuts(): HasMany
    {
        return $this->hasMany(TindakLanjut::class)->orderBy('tanggal_tindak_lanjut', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Pengguna yang membuat rekomendasi ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Helper status penyelesaian badge class (konsisten dengan Temuan).
     */
    public function getStatusPenyelesaianBadgeAttribute(): array
    {
        return match ($this->status_penyelesaian) {
            'Belum Ditindaklanjuti' => [
                'label' => 'Belum Ditindaklanjuti',
                'class' => 'bg-rose-100 text-rose-800 border-rose-200',
                'dot' => 'bg-rose-500',
            ],
            'Menunggu Verifikasi' => [
                'label' => 'Menunggu Verifikasi',
                'class' => 'bg-blue-100 text-blue-800 border-blue-200',
                'dot' => 'bg-blue-500',
            ],
            'Belum Sesuai' => [
                'label' => 'Belum Sesuai',
                'class' => 'bg-amber-100 text-amber-800 border-amber-200',
                'dot' => 'bg-amber-500',
            ],
            'Sesuai' => [
                'label' => 'Sesuai',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'dot' => 'bg-emerald-500',
            ],
            'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti' => [
                'label' => 'Tidak Dapat Ditindaklanjuti',
                'class' => 'bg-gray-100 text-gray-800 border-gray-300',
                'dot' => 'bg-gray-500',
            ],
            default => [
                'label' => $this->status_penyelesaian ?? 'Belum Ditindaklanjuti',
                'class' => 'bg-gray-100 text-gray-700 border-gray-200',
                'dot' => 'bg-gray-500',
            ],
        };
    }
}
