<?php

namespace Database\Seeders;

use App\Models\Lhp;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\TimPemeriksa;
use App\Models\User;
use Illuminate\Database\Seeder;

class TimAndLhpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminInspektorat = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
        $adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();

        $pemeriksaanBpk = Pemeriksaan::where('nomor_spt', '05/SPT/BPK-GOR/02/2025')->first();
        $pemeriksaanInsp = Pemeriksaan::where('nomor_spt', '700/ITPROV/042/SPT/2025')->first();

        $inspektur = Pegawai::where('nip', '19690510 199503 1 002')->first();
        $ahmad = Pegawai::where('nip', '19750814 200003 1 005')->first();
        $rahmat = Pegawai::where('nip', '19860925 201101 1 007')->first();
        $nurul = Pegawai::where('nip', '19890412 201402 2 006')->first();

        // Seed Tim Pemeriksa for Inspektorat
        if ($pemeriksaanInsp) {
            $timInsp = [
                ['pegawai_id' => $inspektur?->id, 'peran_tim' => 'Penanggung Jawab', 'keterangan' => 'Inspektur Daerah'],
                ['pegawai_id' => $ahmad?->id, 'peran_tim' => 'Pengendali Teknis', 'keterangan' => 'Irban Wilayah I'],
                ['pegawai_id' => $rahmat?->id, 'peran_tim' => 'Ketua Tim', 'keterangan' => 'Auditor Ahli Muda'],
                ['pegawai_id' => $nurul?->id, 'peran_tim' => 'Anggota Tim', 'keterangan' => 'Auditor Pertama'],
            ];

            foreach ($timInsp as $member) {
                if ($member['pegawai_id']) {
                    TimPemeriksa::updateOrCreate(
                        ['pemeriksaan_id' => $pemeriksaanInsp->id, 'pegawai_id' => $member['pegawai_id']],
                        $member
                    );
                }
            }

            // Seed LHP for Inspektorat
            Lhp::updateOrCreate(
                ['nomor_lhp' => '700/ITPROV/042/LHP/2025'],
                [
                    'pemeriksaan_id' => $pemeriksaanInsp->id,
                    'tanggal_lhp' => '2025-05-10',
                    'judul_lhp' => 'Laporan Hasil Pemeriksaan Operasional dan BLUD RSUD dr. Hasri Ainun Habibie TA 2025',
                    'ringkasan' => 'Ditemukan ketidaksesuaian administrasi pencatatan persediaan obat dan keterlambatan penyetoran sisa kas BLUD.',
                    'created_by' => $adminInspektorat?->id,
                ]
            );
        }

        // Seed LHP for BPK
        if ($pemeriksaanBpk) {
            Lhp::updateOrCreate(
                ['nomor_lhp' => '12.A/LHP/XIX.GOR/05/2025'],
                [
                    'pemeriksaan_id' => $pemeriksaanBpk->id,
                    'tanggal_lhp' => '2025-05-20',
                    'judul_lhp' => 'LHP BPK atas Kepatuhan Pengelolaan Keuangan Dinas Kesehatan TA 2025',
                    'ringkasan' => 'Terdapat kelebihan pembayaran pada belanja modal pengadaan alat kesehatan dan denda keterlambatan yang belum dipungut.',
                    'created_by' => $adminBpk?->id,
                ]
            );
        }
    }
}
