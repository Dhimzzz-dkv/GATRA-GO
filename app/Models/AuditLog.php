<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\EnforcesLazyLoading;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory, EnforcesLazyLoading;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'role_name',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * User who executed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper for badge action colors.
     *
     * Setiap jenis aksi yang ditulis aplikasi harus punya label Bahasa Indonesia.
     * Aksi yang tidak terdaftar akan tampil sebagai kode mentah (mis. FORCE_DELETE),
     * sehingga membingungkan pembaca audit trail. Test
     * AuditTrailLabelTest::test_every_recorded_action_has_a_label menjaga hal ini.
     */
    public function getActionBadgeAttribute(): array
    {
        return match ($this->action) {
            'LOGIN' => ['label' => 'Login', 'class' => 'bg-blue-100 text-blue-800 border-blue-200'],
            'LOGOUT' => ['label' => 'Logout', 'class' => 'bg-gray-100 text-gray-800 border-gray-200'],
            'LOGIN_FAILED' => ['label' => 'Login Gagal', 'class' => 'bg-rose-100 text-rose-800 border-rose-200'],
            'LOGIN_LOCKED' => ['label' => 'Akun Terkunci', 'class' => 'bg-red-100 text-red-900 border-red-300'],
            'CREATE' => ['label' => 'Tambah Data', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200'],
            'UPDATE' => ['label' => 'Ubah Data', 'class' => 'bg-amber-100 text-amber-800 border-amber-200'],
            'DELETE' => ['label' => 'Hapus', 'class' => 'bg-rose-100 text-rose-800 border-rose-200'],
            'RESTORE' => ['label' => 'Pulihkan Data', 'class' => 'bg-teal-100 text-teal-800 border-teal-200'],
            'FORCE_DELETE' => ['label' => 'Hapus Permanen', 'class' => 'bg-red-100 text-red-900 border-red-300'],
            'SUBMIT' => ['label' => 'Pengajuan', 'class' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
            'CANCEL_SUBMIT' => ['label' => 'Batal Pengajuan', 'class' => 'bg-slate-100 text-slate-800 border-slate-200'],
            'APPROVE' => ['label' => 'Disetujui', 'class' => 'bg-green-100 text-green-800 border-green-200'],
            'REVISE' => ['label' => 'Minta Revisi', 'class' => 'bg-orange-100 text-orange-800 border-orange-200'],
            'REJECT' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-800 border-red-200'],
            'VERIFY' => ['label' => 'Verifikasi TL', 'class' => 'bg-purple-100 text-purple-800 border-purple-200'],
            'DOWNLOAD' => ['label' => 'Unduh Berkas', 'class' => 'bg-sky-100 text-sky-800 border-sky-200'],
            default => ['label' => $this->action, 'class' => 'bg-gray-100 text-gray-800 border-gray-200'],
        };
    }

    /**
     * Label Bahasa Indonesia untuk sebuah kode aksi, tanpa perlu instance model.
     * Dipakai oleh dropdown filter agar tidak menampilkan kode mentah seperti
     * FORCE_DELETE atau LOGIN_LOCKED kepada pengguna.
     */
    public static function actionLabel(string $action): string
    {
        return (new self(['action' => $action]))->action_badge['label'];
    }

    /**
     * Label Bahasa Indonesia untuk sebuah kode modul.
     * Modul ditampilkan di badge (huruf kecil) dan dropdown filter, sehingga
     * kode seperti TINDAK_LANJUT tidak boleh bocor ke antarmuka.
     */
    public static function moduleLabel(?string $module): string
    {
        if ($module === null || $module === '') {
            return '-';
        }

        return match (strtoupper($module)) {
            'AUTH' => 'Autentikasi',
            'LHP' => 'LHP',
            'PEMERIKSAAN' => 'Pemeriksaan',
            'SPT' => 'SPT',
            'TEMUAN' => 'Temuan',
            'TINDAK_LANJUT' => 'Tindak Lanjut',
            'OPD' => 'Master OPD',
            'PEGAWAI' => 'Master Pegawai',
            'USER' => 'Manajemen Pengguna',
            default => ucwords(strtolower(str_replace('_', ' ', $module))),
        };
    }

    /**
     * Daftar seluruh jenis aksi yang ditulis ke audit trail oleh aplikasi.
     * Dipakai sebagai acuan pengujian kelengkapan label.
     *
     * @return array<int, string>
     */
    public static function knownActions(): array
    {
        return [
            'LOGIN',
            'LOGOUT',
            'LOGIN_FAILED',
            'LOGIN_LOCKED',
            'CREATE',
            'UPDATE',
            'DELETE',
            'RESTORE',
            'FORCE_DELETE',
            'SUBMIT',
            'CANCEL_SUBMIT',
            'APPROVE',
            'REVISE',
            'REJECT',
            'VERIFY',
            'DOWNLOAD',
        ];
    }

    /**
     * Map database column name to human-readable Indonesian label.
     */
    public static function attributeLabel(string $key): string
    {
        $labels = [
            'judul_temuan' => 'Judul Temuan',
            'uraian_temuan' => 'Uraian Temuan',
            'rekomendasi' => 'Rekomendasi',
            'nilai_kerugian' => 'Nilai Kerugian Daerah (Rp)',
            'total_disetor' => 'Total Disetor (Rp)',
            'sisa_kerugian' => 'Sisa Kerugian (Rp)',
            'ada_unsur_kerugian' => 'Ada Unsur Kerugian',
            'status_penyelesaian' => 'Status Penyelesaian',
            'status_pengajuan' => 'Status Pengajuan',
            'status_verifikasi' => 'Status Verifikasi',
            'catatan_verifikasi' => 'Catatan Verifikasi',
            'catatan_revisi' => 'Catatan Revisi',
            'catatan_persetujuan' => 'Catatan Persetujuan',
            'alasan_khusus' => 'Alasan Khusus',
            'nomor_spt' => 'Nomor SPT',
            'nama_tim' => 'Nama Tim',
            'tanggal_spt' => 'Tanggal SPT',
            'tanggal_mulai' => 'Tanggal Mulai',
            'tanggal_selesai' => 'Tanggal Selesai',
            'nomor_lhp' => 'Nomor LHP',
            'tanggal_lhp' => 'Tanggal LHP',
            'judul_lhp' => 'Judul LHP',
            'jumlah_setor' => 'Jumlah Setor (Rp)',
            'nomor_bukti_setor' => 'Nomor Bukti Setor',
            'tanggal_setor' => 'Tanggal Setor',
            'uraian_tindak_lanjut' => 'Uraian Tindak Lanjut',
            'opd_id' => 'ID OPD',
            'sumber_pemeriksaan' => 'Sumber Pemeriksaan',
            'tahun' => 'Tahun Anggaran',
        ];

        return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
    }

    /**
     * Format attribute value for clean presentation.
     */
    public static function formatAttributeValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '(Kosong)';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (in_array($key, ['nilai_kerugian', 'total_disetor', 'sisa_kerugian', 'jumlah_setor']) && is_numeric($value)) {
            return 'Rp ' . number_format((float) $value, 0, ',', '.');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
