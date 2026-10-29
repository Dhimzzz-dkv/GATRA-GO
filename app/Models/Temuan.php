<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\EnforcesLazyLoading;

class Temuan extends Model
{
    use HasFactory, SoftDeletes, EnforcesLazyLoading;

    protected $fillable = [
        'pemeriksaan_id',
        'lhp_id',
        'sumber_pemeriksaan',
        'opd_id',
        'tahun',
        'judul_temuan',
        'uraian_temuan',
        'penyebab',
        'akibat',
        'rekomendasi',
        'ada_unsur_administrasi',
        'uraian_administrasi',
        'ada_unsur_kerugian',
        'kategori_kerugian',
        'jenis_kerugian',
        'nilai_kerugian',
        'uraian_kerugian',
        'status_pengajuan',
        'catatan_revisi',
        'status_penyelesaian',
        'alasan_khusus',
        'dokumen_pendukung_alasan',
        'dokumen_pendukung',
        'nama_dokumen_asli',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
        'is_archived',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'ada_unsur_administrasi' => 'boolean',
        'ada_unsur_kerugian' => 'boolean',
        'nilai_kerugian' => 'float',
        'approved_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    /**
     * Kegiatan Pemeriksaan induk.
     */
    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class);
    }

    /**
     * Dokumen LHP terkait.
     */
    public function lhp(): BelongsTo
    {
        return $this->belongsTo(Lhp::class);
    }

    /**
     * OPD target temuan.
     */
    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    /**
     * Pengguna yang menginput temuan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pengguna terakhir yang memperbarui temuan.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Super Admin yang menyetujui temuan.
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
     * Riwayat tindak lanjut oleh OPD.
     */
    public function tindakLanjuts(): HasMany
    {
        return $this->hasMany(TindakLanjut::class)->orderBy('tanggal_tindak_lanjut', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Daftar rekomendasi bertahap untuk temuan administrasi.
     */
    public function rekomendasis(): HasMany
    {
        return $this->hasMany(Rekomendasi::class)->orderBy('id', 'asc');
    }

    /**
     * Evaluasi ulang status penyelesaian temuan secara komprehensif berdasarkan unsur administrasi dan kerugian.
     */
    public function evaluateStatusPenyelesaian(): void
    {
        // Pengecualian: Jika temuan sudah diputuskan 'Tidak Dapat Ditindaklanjuti' secara eksplisit
        if (in_array($this->status_penyelesaian, ['Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'Tidak Dapat Ditindaklanjuti'])) {
            return;
        }

        $statusAdministrasi = null;
        if ($this->ada_unsur_administrasi && $this->rekomendasis()->exists()) {
            // Gunakan relasi yang sudah dimuat bila ada, agar tidak memicu
            // lazy loading saat preventLazyLoading aktif.
            $this->loadMissing('rekomendasis');
            $rekomendasis = $this->rekomendasis;
            if ($rekomendasis->contains('status_penyelesaian', 'Menunggu Verifikasi')) {
                $statusAdministrasi = 'Menunggu Verifikasi';
            } else {
                $completedCount = $rekomendasis->filter(function ($r) {
                    return in_array($r->status_penyelesaian, [
                        'Sesuai',
                        'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah',
                        'Tidak Dapat Ditindaklanjuti',
                    ]);
                })->count();
                if ($completedCount === $rekomendasis->count()) {
                    $statusAdministrasi = 'Sesuai';
                } elseif ($rekomendasis->every(fn ($r) => $r->status_penyelesaian === 'Belum Ditindaklanjuti')) {
                    $statusAdministrasi = 'Belum Ditindaklanjuti';
                } else {
                    $statusAdministrasi = 'Belum Sesuai';
                }
            }
        }

        $statusKerugian = null;
        if ($this->ada_unsur_kerugian && $this->nilai_kerugian > 0) {
            $hasPendingKerugian = $this->tindakLanjuts()
                ->whereNull('rekomendasi_id')
                ->where('status_verifikasi', 'MENUNGGU VERIFIKASI')
                ->exists();
                
            if ($hasPendingKerugian) {
                $statusKerugian = 'Menunggu Verifikasi';
            } else {
                $sisa = $this->sisa_kerugian;
                if ($sisa <= 0) {
                    $statusKerugian = 'Sesuai';
                } else {
                    $hasAnyTindakLanjut = $this->tindakLanjuts()->whereNull('rekomendasi_id')->exists();
                    $statusKerugian = $hasAnyTindakLanjut ? 'Belum Sesuai' : 'Belum Ditindaklanjuti';
                }
            }
        }

        $statuses = array_filter([$statusAdministrasi, $statusKerugian]);
        $newStatus = 'Belum Ditindaklanjuti';

        if (empty($statuses)) {
            $legacyTl = $this->tindakLanjuts()->whereNull('rekomendasi_id')->get();
            if ($legacyTl->count() > 0) {
                if ($legacyTl->contains('status_verifikasi', 'MENUNGGU VERIFIKASI')) {
                    $newStatus = 'Menunggu Verifikasi';
                } elseif ($legacyTl->contains('status_verifikasi', 'BELUM SESUAI')) {
                    $newStatus = 'Belum Sesuai';
                } elseif ($legacyTl->contains('status_verifikasi', 'SESUAI')) {
                    $newStatus = 'Sesuai';
                }
            }
        } else {
            if (in_array('Menunggu Verifikasi', $statuses)) {
                $newStatus = 'Menunggu Verifikasi';
            } elseif (count(array_unique($statuses)) === 1 && $statuses[array_key_first($statuses)] === 'Sesuai') {
                $newStatus = 'Sesuai';
            } elseif (count(array_unique($statuses)) === 1 && $statuses[array_key_first($statuses)] === 'Belum Ditindaklanjuti') {
                $newStatus = 'Belum Ditindaklanjuti';
            } else {
                $newStatus = 'Belum Sesuai';
            }
        }

        if ($this->status_penyelesaian !== $newStatus) {
            $this->update(['status_penyelesaian' => $newStatus]);
        }
    }

    /**
     * Sinkronisasi status penyelesaian temuan berdasarkan status seluruh rekomendasi di dalamnya.
     * Hanya berlaku jika temuan memiliki data rekomendasi bertahap.
     */
    public function syncStatusPenyelesaianFromRekomendasi(): void
    {
        $this->evaluateStatusPenyelesaian();
    }

    /**
     * Total nominal setoran yang sudah diverifikasi 'SESUAI'.
     */
    public function getTotalDisetorAttribute(): float
    {
        return (float) $this->tindakLanjuts()
            ->where('status_verifikasi', 'SESUAI')
            ->sum('jumlah_setor');
    }

    public function getFormattedTotalDisetorAttribute(): string
    {
        return 'Rp ' . number_format($this->total_disetor, 0, ',', '.');
    }

    /**
     * Sisa nilai kerugian daerah yang belum disetorkan.
     */
    public function getSisaKerugianAttribute(): float
    {
        if (! $this->ada_unsur_kerugian || $this->nilai_kerugian <= 0) {
            return 0.0;
        }

        return (float) max(0, $this->nilai_kerugian - $this->total_disetor);
    }

    public function getFormattedSisaKerugianAttribute(): string
    {
        return 'Rp ' . number_format($this->sisa_kerugian, 0, ',', '.');
    }

    /**
     * Persentase penyelesaian fisik / finansial (0% - 100%).
     */
    public function getProgressPersenAttribute(): int
    {
        if ($this->status_penyelesaian === 'Sesuai') {
            return 100;
        }

        if ($this->status_penyelesaian === 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah' || $this->status_penyelesaian === 'Tidak Dapat Ditindaklanjuti') {
            return 100;
        }

        if ($this->ada_unsur_kerugian && $this->nilai_kerugian > 0) {
            $persen = round(($this->total_disetor / $this->nilai_kerugian) * 100);
            return (int) min(100, max(0, $persen));
        }

        if ($this->status_penyelesaian === 'Belum Sesuai') {
            return 50;
        }

        return 0;
    }

    /**
     * Scope query berdasarkan hak akses pengguna.
     */
    public function scopeForUser($query, User $user)
    {
        if ($user->isSuperAdmin() || $user->isPemantau()) {
            return $query->where(function ($q) {
                $q->where('status_pengajuan', '!=', 'DRAFT')
                    ->orWhereHas('pemeriksaan', function ($pq) {
                        $pq->where('status_pengajuan', '!=', 'DRAFT');
                    });
            });
        }

        if ($user->isAdminSumber()) {
            return $query->where('sumber_pemeriksaan', $user->getSumberPemeriksaan());
        }

        if ($user->isOpd() && $user->opd_id) {
            return $query->where('status_pengajuan', 'DISETUJUI')
                ->where(function ($q) use ($user) {
                    $q->where('opd_id', $user->opd_id)
                        ->orWhereHas('pemeriksaan', function ($pq) use ($user) {
                            $pq->where('opd_id', $user->opd_id);
                        })
                        ->orWhereHas('lhp.opds', function ($loq) use ($user) {
                            $loq->where('opds.id', $user->opd_id);
                        });
                });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope temuan aktif (tidak diarsip).
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Format nilai kerugian dalam format Rupiah.
     */
    public function getFormattedNilaiKerugianAttribute(): string
    {
        if (! $this->ada_unsur_kerugian || $this->nilai_kerugian <= 0) {
            return 'Rp 0';
        }

        return 'Rp ' . number_format($this->nilai_kerugian, 0, ',', '.');
    }

    /**
     * Helper info sub kategori jenis kerugian (Internal / Eksternal) beserta penjelasannya.
     */
    public function getJenisKerugianInfoAttribute(): array
    {
        $jenis = $this->jenis_kerugian;
        if ($jenis === 'Internal') {
            return [
                'jenis' => 'Internal',
                'sub_kategori' => 'Internal',
                'label' => 'Kerugian Internal',
                'lingkup' => 'Lingkungan Kantor / OPD',
                'keterangan' => 'Kerugian yang ditemukan di dalam kantor / OPD.',
                'deskripsi' => 'Kerugian internal merupakan kerugian finansial yang bersumber atau terjadi di lingkungan internal kantor, instansi, maupun Organisasi Perangkat Daerah (OPD).',
                'badge_class' => 'bg-purple-100 text-purple-900 border-purple-300',
                'badge_header' => 'bg-purple-600 text-white',
                'tag_class' => 'bg-purple-600 text-white',
                'icon' => '🏢',
            ];
        } elseif ($jenis === 'Eksternal') {
            return [
                'jenis' => 'Eksternal',
                'sub_kategori' => 'Eksternal',
                'label' => 'Kerugian Eksternal',
                'lingkup' => 'Pihak Ketiga / Rekanan',
                'keterangan' => 'Kerugian yang ditemukan pada pihak ketiga / penyedia barang & jasa.',
                'deskripsi' => 'Kerugian eksternal merupakan kerugian finansial yang melibatkan pihak ketiga seperti rekanan, kontraktor, penyedia barang/jasa, atau pihak di luar instansi pemerintah.',
                'badge_class' => 'bg-amber-100 text-amber-900 border-amber-300',
                'badge_header' => 'bg-amber-600 text-white',
                'tag_class' => 'bg-amber-600 text-white',
                'icon' => '🤝',
            ];
        }

        return [
            'jenis' => $jenis ?? 'Kerugian Daerah',
            'sub_kategori' => $jenis ?: 'Umum',
            'label' => 'Kerugian Daerah',
            'lingkup' => 'Keuangan Daerah',
            'keterangan' => 'Temuan yang menimbulkan kewajiban pengembalian ke Kas Daerah.',
            'deskripsi' => 'Temuan yang menimbulkan kerugian finansial daerah/negara dan wajib disetorkan kembali ke Kas Daerah.',
            'badge_class' => 'bg-rose-100 text-rose-800 border-rose-200',
            'badge_header' => 'bg-rose-600 text-white',
            'tag_class' => 'bg-rose-600 text-white',
            'icon' => '💰',
        ];
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

    /**
     * Helper status penyelesaian badge class.
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
