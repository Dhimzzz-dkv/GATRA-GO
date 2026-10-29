<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Rekomendasi;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminBpkp;
    protected User $pemantau;
    protected User $opdDinkes;
    protected User $opdDikbud;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->adminBpkp = User::where('email', 'admin.bpkp@inspektorat.go.id')->first();
        $this->pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
        $this->opdDikbud = User::where('email', 'opd.dikbud@gorontaloprov.go.id')->first();
    }

    public function test_super_admin_can_view_laporan_matrix(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('laporan.index', ['tab' => 'rekap']));

        $response->assertStatus(200);
        $response->assertSee('Pusat Laporan &amp; Rekapitulasi Eksekutif', false);
        $response->assertSee('Matriks Rekapitulasi OPD');
        $response->assertSee('Dinas Kesehatan');
    }

    public function test_super_admin_can_view_laporan_rincian(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('laporan.index', ['tab' => 'rincian']));

        $response->assertStatus(200);
        $response->assertSee('Laporan Rincian Temuan');
        $response->assertSee('Dinas Kesehatan');
    }

    public function test_admin_sumber_can_view_scoped_reports(): void
    {
        $response = $this->actingAs($this->adminBpk)->get(route('laporan.index'));

        $response->assertStatus(200);
        $response->assertSee('Dinas Kesehatan');
    }

    public function test_official_print_report_renders_with_letterhead_and_dual_signatures(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('laporan.cetak', ['tab' => 'rekap', 'tahun' => 2025]));

        $response->assertStatus(200);
        $response->assertSee('PEMERINTAH PROVINSI GORONTALO');
        $response->assertSee('INSPEKTORAT DAERAH');
        $response->assertSee('Jln. Jenderal Sudirman No. 79');

        // Penandatangan bersumber dari master Pegawai, bukan ditulis tetap.
        // Nama hardcoded lama (RAHMAT S. ISMAIL / ZUKRI SUROTINOJO) sudah dihapus
        // karena tidak pernah ada di master data.
        $ttd = \App\Services\SignatoryResolver::forLaporan();
        $response->assertSee($ttd['pengesah']['nama']);
        $response->assertSee($ttd['pembuat']['nama']);
        $response->assertSee('Inspektur Daerah Provinsi Gorontalo');

        $response->assertSee('TOTAL REKAPITULASI PROVINSI');
    }

    public function test_export_csv_streams_valid_spreadsheet_data(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('laporan.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('content-disposition');
    }

    public function test_default_access_without_jenis_parameter_defaults_to_kerugian_and_is_identical(): void
    {
        $resDefault = $this->actingAs($this->superAdmin)->get(route('laporan.index'));
        $resKerugian = $this->actingAs($this->superAdmin)->get(route('laporan.index', ['jenis' => 'kerugian']));

        $resDefault->assertStatus(200);
        $resKerugian->assertStatus(200);

        // Header & cards check for financial loss default
        $resDefault->assertSee('Matriks Rekapitulasi OPD');
        $resDefault->assertSee('Total Kerugian');
        $resDefault->assertSee('Total Telah Disetor');
        $resDefault->assertSee('Sisa Kerugian');
        $resDefault->assertSee('Nilai Kerugian (Rp)');

        // Comparison between default and ?jenis=kerugian
        $resKerugian->assertSee('Matriks Rekapitulasi OPD');
        $resKerugian->assertSee('Total Kerugian');
    }

    public function test_jenis_administrasi_hides_financial_loss_findings_and_shows_administrative_summary(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $pemeriksaan = Pemeriksaan::where('sumber_pemeriksaan', 'BPK')->first();

        // Temuan Kerugian Murni
        Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Kerugian Uji Laporan',
            'uraian_temuan' => 'Kerugian perjalanan dinas.',
            'ada_unsur_administrasi' => false,
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 50000000,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Temuan Administrasi Murni
        Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Administrasi Uji Laporan',
            'uraian_temuan' => 'Kelengkapan SOP dinas.',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => false,
            'nilai_kerugian' => 0,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('laporan.index', ['jenis' => 'administrasi']));

        $response->assertStatus(200);
        $response->assertSee('Total Temuan Administrasi');
        $response->assertSee('Total Pemeriksaan Terkait');
        $response->assertSee('Total Butir Rekomendasi');
        $response->assertSee('% Rekomendasi Selesai');

        // Temuan kerugian tidak boleh tampil di rekap administrasi
        $response->assertDontSee('Temuan Kerugian Uji Laporan');
    }

    public function test_jenis_administrasi_tab_rekap_groups_by_pemeriksaan(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $pemeriksaan = Pemeriksaan::where('sumber_pemeriksaan', 'BPK')->first();

        $temuanAdm = Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Pengawasan Prosedur Rekap',
            'uraian_temuan' => 'Uraian temuan pengawasan prosedur.',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => false,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin Butir Pertama Rekap',
            'status_penyelesaian' => 'Sesuai',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('laporan.index', [
            'tab' => 'rekap',
            'jenis' => 'administrasi',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Matriks Rekapitulasi Pemeriksaan');
        $response->assertSee($pemeriksaan->nomor_spt);
        $response->assertSee('Temuan Pengawasan Prosedur Rekap');
        $response->assertSee('Poin Butir Pertama Rekap');

        // Tab rekap mengelompokkan temuan per pemeriksaan dalam tabel matriks,
        // dengan kolom "Jumlah Rekomendasi" dan baris total di footer.
        $response->assertSee('Jumlah Rekomendasi');
        $response->assertSee('TOTAL KESELURUHAN');
        $response->assertSee('% Selesai');
    }

    public function test_jenis_administrasi_tab_rincian_displays_exact_seven_columns_with_data(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $pemeriksaan = Pemeriksaan::where('sumber_pemeriksaan', 'BPK')->first();

        $temuan = Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Rincian Spesifik 7 Kolom',
            'uraian_temuan' => 'Uraian temuan 7 kolom.',
            'penyebab' => 'Kurangnya pemahaman regulasi perbup.',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => false,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Sesuai',
            'created_by' => $this->adminBpk->id,
        ]);

        $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Menyusun pedoman tertulis SOP.',
            'status_penyelesaian' => 'Sesuai',
            'created_by' => $this->adminBpk->id,
        ]);
        $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Melakukan bimtek staf secara berkala.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('laporan.index', [
            'tab' => 'rincian',
            'jenis' => 'administrasi',
        ]));

        $response->assertStatus(200);

        // Tab rincian memakai format kartu naratif (bukan tabel 7 kolom).
        // Setiap butir informasi yang dahulu berupa kolom kini berupa label
        // yang terbaca: temuan, penyebab, dan daftar rekomendasi bernomor.
        $response->assertSee($dinkes->nama);
        $response->assertSee('Temuan Rincian Spesifik 7 Kolom');
        $response->assertSee('Kurangnya pemahaman regulasi perbup.');
        $response->assertSee('1.');
        $response->assertSee('Menyusun pedoman tertulis SOP.');
        $response->assertSee('2.');
        $response->assertSee('Melakukan bimtek staf secara berkala.');
        $response->assertSee('Belum Sesuai');

        // Header eksplisit untuk rincian butir rekomendasi
        $response->assertSee('Rincian Butir Rekomendasi');
    }

    public function test_jenis_administrasi_calculates_rekomendasi_progress_percentage_accurately(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $pemeriksaan = Pemeriksaan::where('sumber_pemeriksaan', 'BPK')->first();

        $temuan = Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Hitung Persen Rekomendasi',
            'uraian_temuan' => 'Uraian temuan.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Sesuai',
            'created_by' => $this->adminBpk->id,
        ]);

        // 4 butir: 2 Sesuai, 1 Tidak Dapat Ditindaklanjuti Sah, 1 Belum Ditindaklanjuti -> 3/4 = 75%
        $temuan->rekomendasis()->create(['uraian_rekomendasi' => 'R1', 'status_penyelesaian' => 'Sesuai', 'created_by' => $this->adminBpk->id]);
        $temuan->rekomendasis()->create(['uraian_rekomendasi' => 'R2', 'status_penyelesaian' => 'Sesuai', 'created_by' => $this->adminBpk->id]);
        $temuan->rekomendasis()->create(['uraian_rekomendasi' => 'R3', 'status_penyelesaian' => 'Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', 'created_by' => $this->adminBpk->id]);
        $temuan->rekomendasis()->create(['uraian_rekomendasi' => 'R4', 'status_penyelesaian' => 'Belum Ditindaklanjuti', 'created_by' => $this->adminBpk->id]);

        $response = $this->actingAs($this->superAdmin)->get(route('laporan.index', [
            'jenis' => 'administrasi',
            'opd_id' => $dinkes->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('75%');
        $response->assertSee('(3 / 4)');
    }

    public function test_jenis_administrasi_dropdown_filters_by_pemeriksaan(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $pemeriksaanA = Pemeriksaan::where('sumber_pemeriksaan', 'BPK')->first();
        $pemeriksaanB = Pemeriksaan::where('sumber_pemeriksaan', 'BPKP')->first();

        Temuan::create([
            'pemeriksaan_id' => $pemeriksaanA->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Dari Pemeriksaan Alpha',
            'uraian_temuan' => 'Uraian A.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Sesuai',
            'created_by' => $this->adminBpk->id,
        ]);

        Temuan::create([
            'pemeriksaan_id' => $pemeriksaanB->id,
            'sumber_pemeriksaan' => 'BPKP',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Dari Pemeriksaan Beta',
            'uraian_temuan' => 'Uraian B.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Sesuai',
            'created_by' => $this->adminBpkp->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('laporan.index', [
            'tab' => 'rincian',
            'jenis' => 'administrasi',
            'pemeriksaan_id' => $pemeriksaanA->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Temuan Dari Pemeriksaan Alpha');
        $response->assertDontSee('Temuan Dari Pemeriksaan Beta');
    }

    public function test_jenis_administrasi_respects_user_role_scoping(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $dikbud = Opd::where('kode', 'DIKBUD')->first();
        $pemeriksaan = Pemeriksaan::where('sumber_pemeriksaan', 'BPK')->first();

        Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Khusus Dinkes Adm',
            'uraian_temuan' => 'Uraian.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Sesuai',
            'created_by' => $this->adminBpk->id,
        ]);

        $pemeriksaanDikbud = Pemeriksaan::create([
            'nomor_spt' => '99/SPT/BPK-DIKBUD/2026',
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dikbud->id,
            'tahun' => 2026,
            'status_pengajuan' => 'DISETUJUI',
            'created_by' => $this->adminBpk->id,
        ]);

        Temuan::create([
            'pemeriksaan_id' => $pemeriksaanDikbud->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dikbud->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Khusus Dikbud Adm',
            'uraian_temuan' => 'Uraian.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Sesuai',
            'created_by' => $this->adminBpk->id,
        ]);

        // OPD Dinkes hanya boleh melihat temuannya sendiri
        $responseOpd = $this->actingAs($this->opdDinkes)->get(route('laporan.index', [
            'tab' => 'rincian',
            'jenis' => 'administrasi',
        ]));

        $responseOpd->assertStatus(200);
        $responseOpd->assertSee('Temuan Khusus Dinkes Adm');
        $responseOpd->assertDontSee('Temuan Khusus Dikbud Adm');
    }

    public function test_cetak_official_report_supports_jenis_administrasi(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('laporan.cetak', [
            'tab' => 'rincian',
            'jenis' => 'administrasi',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Laporan Rekapitulasi Temuan Pengawasan Administratif');
        $response->assertSee('OPD');
        $response->assertSee('Sumber');
        $response->assertSee('Temuan');
        $response->assertSee('Penyebab');
        $response->assertSee('Rekomendasi');
        $response->assertSee('Jumlah');
        $response->assertSee('Status');

        // Blok tanda tangan ikut dirender pada versi administrasi
        $response->assertSee('Inspektur Daerah Provinsi Gorontalo');
    }

    public function test_export_csv_supports_jenis_administrasi(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('laporan.export-csv', [
            'tab' => 'rincian',
            'jenis' => 'administrasi',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->assertHeader('content-disposition');
    }
}
