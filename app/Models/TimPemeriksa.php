<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\EnforcesLazyLoading;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimPemeriksa extends Model
{
    use HasFactory, EnforcesLazyLoading;

    protected $fillable = [
        'pemeriksaan_id',
        'pegawai_id',
        'peran_tim',
        'keterangan',
    ];

    /**
     * Kegiatan pemeriksaan.
     */
    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class);
    }

    /**
     * Pegawai Inspektorat.
     */
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * Helper badge peran tim.
     */
    public function getPeranBadgeAttribute(): array
    {
        return match ($this->peran_tim) {
            'Penanggung Jawab' => [
                'class' => 'bg-purple-100 text-purple-800 border-purple-200',
            ],
            'Asisten Penanggung Jawab' => [
                'class' => 'bg-violet-100 text-violet-800 border-violet-200',
            ],
            'Pengendali Teknis' => [
                'class' => 'bg-blue-100 text-blue-800 border-blue-200',
            ],
            'Ketua Tim' => [
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            ],
            'Anggota Tim' => [
                'class' => 'bg-amber-100 text-amber-800 border-amber-200',
            ],
            default => [
                'class' => 'bg-gray-100 text-gray-800 border-gray-200',
            ],
        };
    }
}
