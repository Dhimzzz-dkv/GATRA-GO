<?php

namespace Database\Seeders;

use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Database\Seeder;

class TindakLanjutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
        $superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();

        $temuanBpk = Temuan::where('sumber_pemeriksaan', 'BPK')->first();
        $temuanInsp = Temuan::where('sumber_pemeriksaan', 'Inspektorat')->first();

        if ($temuanBpk && $opdDinkes) {
            // Tindak Lanjut 1 (Verified Sesuai: Rp 200.000.000)
            TindakLanjut::updateOrCreate(
                ['nomor_bukti_setor' => 'STS-2025/06/001'],
                [
                    'temuan_id' => $temuanBpk->id,
                    'opd_id' => $temuanBpk->opd_id,
                    'tanggal_tindak_lanjut' => '2025-06-15',
                    'uraian_tindak_lanjut' => 'Penyetoran tahap pertama atas kelebihan bayar pengadaan alat kesehatan melalui Bank SulutGo ke Rekening Kas Umum Daerah.',
                    'jumlah_setor' => 200000000.00,
                    'status_verifikasi' => 'SESUAI',
                    'catatan_verifikasi' => 'Bukti STS dan rekening koran Kasda telah dicocokkan dan valid.',
                    'verified_by' => $superAdmin?->id,
                    'verified_at' => '2025-06-18 10:30:00',
                    'created_by' => $opdDinkes->id,
                ]
            );

            // Tindak Lanjut 2 (Menunggu Verifikasi: Rp 250.000.000)
            TindakLanjut::updateOrCreate(
                ['nomor_bukti_setor' => 'STS-2025/07/045'],
                [
                    'temuan_id' => $temuanBpk->id,
                    'opd_id' => $temuanBpk->opd_id,
                    'tanggal_tindak_lanjut' => '2025-07-20',
                    'uraian_tindak_lanjut' => 'Penyetoran pelunasan sisa kelebihan pembayaran pengadaan alkes sebesar Rp 250.000.000 lunas 100%.',
                    'jumlah_setor' => 250000000.00,
                    'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                    'created_by' => $opdDinkes->id,
                ]
            );

            $temuanBpk->update(['status_penyelesaian' => 'Belum Sesuai']);
        }

        if ($temuanInsp) {
            // Tindak Lanjut Inspektorat (Lunas & Sesuai)
            TindakLanjut::updateOrCreate(
                ['nomor_bukti_setor' => 'STS-BLUD/2025/089'],
                [
                    'temuan_id' => $temuanInsp->id,
                    'opd_id' => $temuanInsp->opd_id,
                    'tanggal_tindak_lanjut' => '2025-06-05',
                    'uraian_tindak_lanjut' => 'Penyetoran selisih persediaan dan penerbitan SOP rekonsiliasi berkala stok obat rawat inap.',
                    'jumlah_setor' => 32500000.00,
                    'status_verifikasi' => 'SESUAI',
                    'catatan_verifikasi' => 'SOP telah disahkan Direktur RSUD dan dana selisih telah disetorkan.',
                    'verified_by' => $adminInsp?->id,
                    'verified_at' => '2025-06-10 14:00:00',
                    'created_by' => $opdDinkes?->id,
                ]
            );

            $temuanInsp->update([
                'status_pengajuan' => 'DISETUJUI',
                'status_penyelesaian' => 'Sesuai',
            ]);
        }
    }
}
