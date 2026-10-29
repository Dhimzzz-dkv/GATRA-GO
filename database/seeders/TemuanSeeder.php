<?php

namespace Database\Seeders;

use App\Models\Lhp;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Database\Seeder;

class TemuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $adminBpkp = User::where('email', 'admin.bpkp@inspektorat.go.id')->first();
        $adminItjen = User::where('email', 'admin.itjen@inspektorat.go.id')->first();
        $adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();

        $pemeriksaanBpk = Pemeriksaan::where('nomor_spt', '05/SPT/BPK-GOR/02/2025')->first();
        $pemeriksaanBpkp = Pemeriksaan::where('nomor_spt', 'ST-112/PW31/3/2025')->first();
        $pemeriksaanItjen = Pemeriksaan::where('nomor_spt', '090/145/ITJEN-KEMENDAGRI/2024')->first();
        $pemeriksaanInsp = Pemeriksaan::where('nomor_spt', '700/ITPROV/042/SPT/2025')->first();

        $lhpBpk = Lhp::where('nomor_lhp', '12.A/LHP/XIX.GOR/05/2025')->first();
        $lhpInsp = Lhp::where('nomor_lhp', '700/ITPROV/042/LHP/2025')->first();

        $temuans = [
            [
                'pemeriksaan_id' => $pemeriksaanBpk?->id,
                'lhp_id' => $lhpBpk?->id,
                'sumber_pemeriksaan' => 'BPK',
                'opd_id' => $pemeriksaanBpk?->opd_id,
                'tahun' => 2025,
                'judul_temuan' => 'Kelebihan Pembayaran atas Pengadaan Alat Kesehatan Rumah Sakit Rujukan',
                'uraian_temuan' => 'Hasil uji petik pemeriksaan dokumen kontrak dan fisik pekerjaan pengadaan alat kesehatan menunjukkan terdapat kelebihan perhitungan volume pekerjaan dan selisih harga pengiriman yang tidak sesuai ketentuan.',
                'penyebab' => 'Pejabat Pembuat Komitmen (PPK) kurang cermat dalam memverifikasi bukti pertanggungjawaban penyedia jasa sebelum menyetujui SPM.',
                'akibat' => 'Mengakibatkan kelebihan pembayaran kas daerah senilai Rp 450.000.000.',
                'rekomendasi' => 'Menginstruksikan Kepala Dinas Kesehatan agar memerintahkan PPK dan rekanan penyedia menyetorkan kelebihan bayar sebesar Rp 450.000.000 ke Kas Daerah Provinsi Gorontalo.',
                'ada_unsur_administrasi' => true,
                'uraian_administrasi' => 'Berita Acara Serah Terima (BAST) belum dilengkapi laporan uji fungsi dari tim teknis.',
                'ada_unsur_kerugian' => true,
                'nilai_kerugian' => 450000000.00,
                'uraian_kerugian' => 'Kelebihan pembayaran belanja modal alat kesehatan sebesar Rp 450.000.000,00.',
                'status_pengajuan' => 'DISETUJUI',
                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                'created_by' => $adminBpk?->id,
            ],
            [
                'pemeriksaan_id' => $pemeriksaanBpkp?->id,
                'lhp_id' => null,
                'sumber_pemeriksaan' => 'BPKP',
                'opd_id' => $pemeriksaanBpkp?->opd_id,
                'tahun' => 2025,
                'judul_temuan' => 'Keterlambatan Penyelesaian Pembangunan Ruang Praktik Siswa SMK Negeri 1 Gorontalo',
                'uraian_temuan' => 'Pekerjaan fisik pembangunan gedung RPS SMK Negeri 1 Gorontalo mengalami keterlambatan selama 24 hari kalender melampaui batas akhir masa kontrak kerja.',
                'penyebab' => 'Kontraktor pelaksana mengalami kendala pasokan material baja ringan dan kekurangan tenaga kerja terampil di lapangan.',
                'akibat' => 'Gedung laboratorium terlambat dimanfaatkan untuk proses belajar mengajar dan denda keterlambatan belum dipungut.',
                'rekomendasi' => 'Kepala Dinas Pendidikan agar mengenakan sanksi denda keterlambatan sebesar 1/1000 per hari dari nilai sisa kontrak kepada kontraktor pelaksana.',
                'ada_unsur_administrasi' => true,
                'uraian_administrasi' => 'Adendum perpanjangan waktu kerja tidak didukung justifikasi teknis resmi dari konsultan pengawas.',
                'ada_unsur_kerugian' => true,
                'nilai_kerugian' => 78500000.00,
                'uraian_kerugian' => 'Potensi denda keterlambatan pekerjaan yang belum disetor ke Kas Daerah senilai Rp 78.500.000,00.',
                'status_pengajuan' => 'MENUNGGU PERSETUJUAN',
                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                'created_by' => $adminBpkp?->id,
            ],
            [
                'pemeriksaan_id' => $pemeriksaanItjen?->id,
                'lhp_id' => null,
                'sumber_pemeriksaan' => 'ITJEN',
                'opd_id' => $pemeriksaanItjen?->opd_id,
                'tahun' => 2024,
                'judul_temuan' => 'Kekurangan Kelengkapan Dokumen Pertanggungjawaban SPJ Perjalanan Dinas Luar Daerah',
                'uraian_temuan' => 'Terdapat 14 berkas SPJ perjalanan dinas luar daerah pada Bidang Bina Marga yang tidak melampirkan bukti boarding pass tiket pesawat dan laporan hasil perjalanan dinas.',
                'penyebab' => 'Pelaksana perjalanan dinas lalai mengarsipkan dokumen perjalanan fisik ke dalam sistem e-SPJ dinas.',
                'akibat' => 'Akuntabilitas penggunaan anggaran belanja perjalanan dinas tidak dapat diyakini keabsahannya.',
                'rekomendasi' => 'Kepala Dinas PUPR agar menerbitkan instruksi penertiban pelaporan SPJ dan menunda pencairan uang muka perjalanan berikutnya bagi pegawai yang belum menyelesaikan SPJ.',
                'ada_unsur_administrasi' => true,
                'uraian_administrasi' => '14 berkas SPJ tanpa boarding pass asli dan bukti konfirmasi kehadiran dari instansi tujuan.',
                'ada_unsur_kerugian' => false,
                'nilai_kerugian' => 0.00,
                'uraian_kerugian' => null,
                'status_pengajuan' => 'DISETUJUI',
                'status_penyelesaian' => 'Sesuai',
                'created_by' => $adminItjen?->id,
            ],
            [
                'pemeriksaan_id' => $pemeriksaanInsp?->id,
                'lhp_id' => $lhpInsp?->id,
                'sumber_pemeriksaan' => 'Inspektorat',
                'opd_id' => $pemeriksaanInsp?->opd_id,
                'tahun' => 2025,
                'judul_temuan' => 'Selisih Pencatatan Persediaan Obat pada Gudang Farmasi RSUD dr. Hasri Ainun Habibie',
                'uraian_temuan' => 'Stock opname fisik pada gudang farmasi menunjukkan perbedaan antara saldo buku SIMRS dan fisik stok obat generik senilai Rp 32.500.000.',
                'penyebab' => 'Sistem pencatatan keluar-masuk obat belum terintegrasi secara real-time antar apotek rawat inap dan gudang utama.',
                'akibat' => 'Laporan persediaan akhir tahun berpotensi salah saji material.',
                'rekomendasi' => 'Direktur RSUD agar melakukan rekonsiliasi bulanan dan menertibkan kartu stok fisik obat farmasi.',
                'ada_unsur_administrasi' => true,
                'uraian_administrasi' => 'Kartu stok manual belum diparaf oleh kepala instalasi farmasi.',
                'ada_unsur_kerugian' => true,
                'nilai_kerugian' => 32500000.00,
                'uraian_kerugian' => 'Selisih nilai persediaan obat kadaluarsa yang belum di-write-off Rp 32.500.000,00.',
                'status_pengajuan' => 'DRAFT',
                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                'created_by' => $adminInsp?->id,
            ],
        ];

        foreach ($temuans as $t) {
            if ($t['pemeriksaan_id'] && $t['opd_id']) {
                Temuan::updateOrCreate(
                    [
                        'judul_temuan' => $t['judul_temuan'],
                        'pemeriksaan_id' => $t['pemeriksaan_id'],
                    ],
                    $t
                );
            }
        }
    }
}
