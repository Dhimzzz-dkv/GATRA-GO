<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\User;
use Illuminate\Database\Seeder;

class PemeriksaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $adminBpkp = User::where('email', 'admin.bpkp@inspektorat.go.id')->first();
        $adminItjen = User::where('email', 'admin.itjen@inspektorat.go.id')->first();
        $adminInspektorat = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();

        $dinkes = Opd::where('kode', 'DINKES')->first();
        $dikbud = Opd::where('kode', 'DIKBUD')->first();
        $pupr = Opd::where('kode', 'PUPR-PKP')->first();
        $rsud = Opd::where('kode', 'RSUD-HAH')->first();

        $pemeriksaans = [
            [
                'sumber_pemeriksaan' => 'BPK',
                'opd_id' => $dinkes?->id,
                'tahun' => 2025,
                'judul' => 'Pemeriksaan Kepatuhan atas Pengelolaan Obat dan Pelayanan Kesehatan TA 2025',
                'nomor_spt' => '05/SPT/BPK-GOR/02/2025',
                'tanggal_spt' => '2025-02-10',
                'tanggal_mulai' => '2025-02-15',
                'tanggal_selesai' => '2025-03-15',
                'status_pengajuan' => 'DISETUJUI',
                'created_by' => $adminBpk?->id,
            ],
            [
                'sumber_pemeriksaan' => 'BPKP',
                'opd_id' => $dikbud?->id,
                'tahun' => 2025,
                'judul' => 'Audit Kinerja atas Pengelolaan Dana Bantuan Operasional Sekolah (BOS) TA 2025',
                'nomor_spt' => 'ST-112/PW31/3/2025',
                'tanggal_spt' => '2025-03-01',
                'tanggal_mulai' => '2025-03-05',
                'tanggal_selesai' => '2025-03-25',
                'status_pengajuan' => 'DRAFT',
                'created_by' => $adminBpkp?->id,
            ],
            [
                'sumber_pemeriksaan' => 'ITJEN',
                'opd_id' => $pupr?->id,
                'tahun' => 2024,
                'judul' => 'Pengawasan Penyelenggaraan Urusan Pemerintahan Daerah Bidang Pekerjaan Umum TA 2024',
                'nomor_spt' => '090/145/ITJEN-KEMENDAGRI/2024',
                'tanggal_spt' => '2024-06-12',
                'tanggal_mulai' => '2024-06-15',
                'tanggal_selesai' => '2024-06-30',
                'status_pengajuan' => 'DISETUJUI',
                'created_by' => $adminItjen?->id,
            ],
            [
                'sumber_pemeriksaan' => 'Inspektorat',
                'opd_id' => $rsud?->id,
                'tahun' => 2025,
                'judul' => 'Audit Operasional Pengelolaan Pengadaan Barang/Jasa dan BLUD RSUD dr. Hasri Ainun Habibie',
                'nomor_spt' => '700/ITPROV/042/SPT/2025',
                'tanggal_spt' => '2025-04-02',
                'tanggal_mulai' => '2025-04-05',
                'tanggal_selesai' => '2025-04-20',
                'status_pengajuan' => 'DRAFT',
                'created_by' => $adminInspektorat?->id,
            ],
        ];

        foreach ($pemeriksaans as $data) {
            if ($data['opd_id']) {
                Pemeriksaan::updateOrCreate(
                    ['nomor_spt' => $data['nomor_spt']],
                    $data
                );
            }
        }
    }
}
