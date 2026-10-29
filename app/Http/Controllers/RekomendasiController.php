<?php

namespace App\Http\Controllers;

use App\Models\Rekomendasi;
use App\Models\Temuan;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RekomendasiController extends Controller
{
    /**
     * Store a newly created gradual recommendation for an administrative finding.
     */
    public function store(Request $request, Temuan $temuan)
    {
        $user = $request->user();

        // 1. Khusus Temuan Administrasi
        if ($temuan->ada_unsur_kerugian) {
            abort(403, 'Akses ditolak. Fitur rekomendasi bertahap hanya tersedia untuk temuan administrasi.');
        }

        // 2. Hanya bisa ditambahkan kalau status_pengajuan DISETUJUI
        if ($temuan->status_pengajuan !== 'DISETUJUI') {
            abort(403, 'Akses ditolak. Rekomendasi hanya dapat ditambahkan pada temuan yang telah disetujui.');
        }

        // 3. Otorisasi Peran: Pemantau & OPD ditolak (hanya Admin yang boleh mengelola butir rekomendasi)
        if ($user->isPemantau()) {
            abort(403, 'Akses ditolak. Peran Pemantau hanya memiliki hak akses untuk memantau data.');
        }

        if ($user->isOpd()) {
            abort(403, 'Akses ditolak. Pengguna OPD tidak diizinkan menambahkan butir rekomendasi.');
        }

        if ($user->isAdminSumber() && $temuan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak. Anda hanya dapat mengelola rekomendasi untuk temuan sumber ' . $user->getSumberPemeriksaan());
        }

        $validated = $request->validate([
            'uraian_rekomendasi' => ['required', 'string'],
        ]);

        // Transaksi: membuat butir rekomendasi juga menyinkronkan status
        // penyelesaian temuan dan teks ringkasan bernomor. Ketiganya harus
        // konsisten — bila teks bernomor gagal diperbarui sementara record
        // rekomendasi sudah ada, ringkasan temuan tidak lagi mencerminkan
        // butir rekomendasi yang sebenarnya.
        $rekomendasi = DB::transaction(function () use ($temuan, $validated, $user) {
            $rekomendasi = $temuan->rekomendasis()->create([
                'uraian_rekomendasi' => $validated['uraian_rekomendasi'],
                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                'created_by' => $user->id,
            ]);

            $temuan->syncStatusPenyelesaianFromRekomendasi();

            // Sinkronkan teks ringkasan bernomor pada kolom 'rekomendasi' temuan
            $allReks = $temuan->rekomendasis()->orderBy('id')->pluck('uraian_rekomendasi')->toArray();
            $numbered = [];
            foreach ($allReks as $i => $u) {
                $trimmed = trim($u);
                $numbered[] = preg_match('/^\d+[\.\)]\s*/', $trimmed) ? $trimmed : (($i + 1) . '. ' . $trimmed);
            }
            $temuan->update(['rekomendasi' => implode("\n", $numbered)]);

            return $rekomendasi;
        }, 3);

        AuditLogger::log(
            'CREATE',
            'REKOMENDASI',
            "Menambahkan butir rekomendasi (ID: {$rekomendasi->id}) pada Temuan (ID: {$temuan->id} - {$temuan->judul_temuan})."
        );

        return back()->with('success', 'Butir rekomendasi berhasil ditambahkan.');
    }

    /**
     * Remove the specified recommendation.
     */
    public function destroy(Request $request, Temuan $temuan, Rekomendasi $rekomendasi)
    {
        if ($rekomendasi->temuan_id !== $temuan->id) {
            abort(404, 'Rekomendasi tidak ditemukan pada temuan ini.');
        }

        $user = $request->user();

        // 1. Khusus Temuan Administrasi
        if ($temuan->ada_unsur_kerugian) {
            abort(403, 'Akses ditolak. Fitur rekomendasi bertahap hanya tersedia untuk temuan administrasi.');
        }

        // 2. Hanya bisa dikelola kalau status_pengajuan DISETUJUI
        if ($temuan->status_pengajuan !== 'DISETUJUI') {
            abort(403, 'Akses ditolak. Rekomendasi hanya dapat dikelola pada temuan yang telah disetujui.');
        }

        // 3. Otorisasi Peran: Pemantau & OPD ditolak
        if ($user->isPemantau()) {
            abort(403, 'Akses ditolak. Peran Pemantau hanya memiliki hak akses untuk memantau data.');
        }

        if ($user->isOpd()) {
            abort(403, 'Akses ditolak. Pengguna OPD tidak diizinkan menghapus butir rekomendasi.');
        }

        if ($user->isAdminSumber() && $temuan->sumber_pemeriksaan !== $user->getSumberPemeriksaan()) {
            abort(403, 'Akses ditolak. Anda hanya dapat mengelola rekomendasi untuk temuan sumber ' . $user->getSumberPemeriksaan());
        }

        // 4. HANYA boleh jika $rekomendasi->tindakLanjuts()->doesntExist() (belum pernah disentuh tindak lanjut sama sekali)
        if (! $rekomendasi->tindakLanjuts()->doesntExist()) {
            abort(403, 'Akses ditolak. Rekomendasi yang sudah memiliki riwayat tindak lanjut tidak dapat dihapus.');
        }

        $rekomendasiId = $rekomendasi->id;

        // Transaksi: menghapus butir rekomendasi, menyinkronkan status temuan,
        // dan memperbarui teks ringkasan bernomor harus terjadi bersama.
        DB::transaction(function () use ($rekomendasi, $temuan) {
            $rekomendasi->delete();

            $temuan->syncStatusPenyelesaianFromRekomendasi();

            // Sinkronkan teks ringkasan bernomor pada kolom 'rekomendasi' temuan
            $allReks = $temuan->rekomendasis()->orderBy('id')->pluck('uraian_rekomendasi')->toArray();
            $numbered = [];
            foreach ($allReks as $i => $u) {
                $trimmed = trim($u);
                $numbered[] = preg_match('/^\d+[\.\)]\s*/', $trimmed) ? $trimmed : (($i + 1) . '. ' . $trimmed);
            }
            $temuan->update(['rekomendasi' => implode("\n", $numbered)]);
        }, 3);

        AuditLogger::log(
            'DELETE',
            'REKOMENDASI',
            "Menghapus butir rekomendasi (ID: {$rekomendasiId}) dari Temuan (ID: {$temuan->id} - {$temuan->judul_temuan})."
        );

        return back()->with('success', 'Butir rekomendasi berhasil dihapus.');
    }
}
