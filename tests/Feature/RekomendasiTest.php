<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Rekomendasi;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RekomendasiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminBpkp;
    protected User $pemantau;
    protected User $opdDinkes;
    protected User $opdDikbud;
    protected Opd $dinkes;
    protected Pemeriksaan $pemeriksaan;
    protected Temuan $temuanAdm;
    protected Temuan $temuanKerugian;

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
        $this->dinkes = Opd::where('kode', 'DINKES')->first();

        $this->pemeriksaan = Pemeriksaan::where('nomor_spt', '05/SPT/BPK-GOR/02/2025')->first();

        // Temuan Administrasi (Disetujui)
        $this->temuanAdm = Temuan::create([
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'lhp_id' => $this->pemeriksaan->lhps()->first()?->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Tertib Administrasi SOP Pelayanan',
            'uraian_temuan' => 'SOP belum diperbarui sesuai peraturan terbaru.',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => false,
            'nilai_kerugian' => 0,
            'rekomendasi' => 'Perbaiki SOP dan lakukan sosialisasi internal.',
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Temuan Kerugian
        $this->temuanKerugian = Temuan::create([
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'lhp_id' => $this->pemeriksaan->lhps()->first()?->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Kerugian Finansial Perjalanan Dinas',
            'uraian_temuan' => 'Kelebihan pembayaran tiket dinas.',
            'ada_unsur_administrasi' => false,
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 15000000,
            'rekomendasi' => 'Setor ke Kas Daerah.',
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);
    }

    public function test_admin_sumber_can_add_rekomendasi_to_approved_administrasi_temuan(): void
    {
        $response = $this->actingAs($this->adminBpk)->post(
            route('temuan.rekomendasi.store', $this->temuanAdm),
            [
                'uraian_rekomendasi' => '1. Kepala Dinas wajib menerbitkan SK Tim Penyusun SOP.',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('rekomendasis', [
            'temuan_id' => $this->temuanAdm->id,
            'uraian_rekomendasi' => '1. Kepala Dinas wajib menerbitkan SK Tim Penyusun SOP.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $this->assertEquals('Belum Ditindaklanjuti', $this->temuanAdm->fresh()->status_penyelesaian);
        $this->assertStringContainsString('1. Kepala Dinas wajib menerbitkan SK Tim Penyusun SOP.', $this->temuanAdm->fresh()->rekomendasi);
    }

    public function test_opd_cannot_add_rekomendasi(): void
    {
        $response = $this->actingAs($this->opdDinkes)->post(
            route('temuan.rekomendasi.store', $this->temuanAdm),
            ['uraian_rekomendasi' => 'Rekomendasi dari OPD.']
        );

        $response->assertStatus(403);
    }

    public function test_pemantau_cannot_add_rekomendasi(): void
    {
        $response = $this->actingAs($this->pemantau)->post(
            route('temuan.rekomendasi.store', $this->temuanAdm),
            ['uraian_rekomendasi' => 'Rekomendasi dari Pemantau.']
        );

        $response->assertStatus(403);
    }

    public function test_admin_sumber_from_other_source_cannot_add_rekomendasi(): void
    {
        $response = $this->actingAs($this->adminBpkp)->post(
            route('temuan.rekomendasi.store', $this->temuanAdm),
            ['uraian_rekomendasi' => 'Rekomendasi dari BPKP untuk temuan BPK.']
        );

        $response->assertStatus(403);
    }

    public function test_cannot_add_rekomendasi_to_temuan_with_unsur_kerugian(): void
    {
        $response = $this->actingAs($this->adminBpk)->post(
            route('temuan.rekomendasi.store', $this->temuanKerugian),
            ['uraian_rekomendasi' => 'Coba tambah rekomendasi ke temuan kerugian.']
        );

        $response->assertStatus(403);
        $this->assertDatabaseMissing('rekomendasis', [
            'temuan_id' => $this->temuanKerugian->id,
        ]);
    }

    public function test_cannot_add_rekomendasi_if_status_pengajuan_is_not_disetujui(): void
    {
        $this->temuanAdm->update(['status_pengajuan' => 'DRAFT']);

        $response = $this->actingAs($this->adminBpk)->post(
            route('temuan.rekomendasi.store', $this->temuanAdm),
            ['uraian_rekomendasi' => 'Rekomendasi pada temuan draft.']
        );

        $response->assertStatus(403);
    }

    public function test_opd_can_submit_tindak_lanjut_for_specific_rekomendasi(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('Bukti_SK_SOP.pdf', 300, 'application/pdf');

        $rekomendasi = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Terbitkan SK SOP.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $this->temuanAdm),
            [
                'rekomendasi_id' => $rekomendasi->id,
                'tanggal_tindak_lanjut' => '2026-08-10',
                'uraian_tindak_lanjut' => 'SK SOP nomor 123 telah diterbitkan dan ditandatangani.',
                'dokumen_bukti' => $file,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tindak_lanjuts', [
            'temuan_id' => $this->temuanAdm->id,
            'rekomendasi_id' => $rekomendasi->id,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);

        // Status rekomendasi dan temuan otomatis menjadi 'Menunggu Verifikasi'
        $this->assertEquals('Menunggu Verifikasi', $rekomendasi->fresh()->status_penyelesaian);
        $this->assertEquals('Menunggu Verifikasi', $this->temuanAdm->fresh()->status_penyelesaian);
    }

    public function test_opd_from_other_opd_cannot_submit_tindak_lanjut_for_rekomendasi(): void
    {
        $rekomendasi = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Terbitkan SK SOP.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Dikbud mencoba menindaklanjuti temuan Dinkes
        $response = $this->actingAs($this->opdDikbud)->post(
            route('temuan.tindak-lanjut.store', $this->temuanAdm),
            [
                'rekomendasi_id' => $rekomendasi->id,
                'tanggal_tindak_lanjut' => '2026-08-10',
                'uraian_tindak_lanjut' => 'Percobaan OPD lain.',
            ]
        );

        $response->assertStatus(403);
    }

    public function test_pemantau_cannot_submit_tindak_lanjut_for_rekomendasi(): void
    {
        $rekomendasi = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Terbitkan SK SOP.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->pemantau)->post(
            route('temuan.tindak-lanjut.store', $this->temuanAdm),
            [
                'rekomendasi_id' => $rekomendasi->id,
                'tanggal_tindak_lanjut' => '2026-08-10',
                'uraian_tindak_lanjut' => 'Percobaan oleh Pemantau.',
            ]
        );

        $response->assertStatus(403);
    }

    public function test_admin_sumber_from_other_source_cannot_submit_tindak_lanjut_for_rekomendasi(): void
    {
        $rekomendasi = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Terbitkan SK SOP.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Admin BPKP mencoba submit tindak lanjut temuan BPK
        $response = $this->actingAs($this->adminBpkp)->post(
            route('temuan.tindak-lanjut.store', $this->temuanAdm),
            [
                'rekomendasi_id' => $rekomendasi->id,
                'tanggal_tindak_lanjut' => '2026-08-10',
                'uraian_tindak_lanjut' => 'Percobaan oleh Admin BPKP.',
            ]
        );

        $response->assertStatus(403);
    }

    public function test_tindak_lanjut_must_use_rekomendasi_id_when_temuan_has_rekomendasi(): void
    {
        $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin rekomendasi aktif.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->opdDinkes)->from(route('temuan.show', $this->temuanAdm))->post(
            route('temuan.tindak-lanjut.store', $this->temuanAdm),
            [
                'tanggal_tindak_lanjut' => '2026-08-10',
                'uraian_tindak_lanjut' => 'Percobaan tindak lanjut umum tanpa butir.',
            ]
        );

        $response->assertRedirect(route('temuan.show', $this->temuanAdm));
        $response->assertSessionHas('error', 'Temuan ini sudah memiliki rincian Butir Rekomendasi. Silakan sampaikan tindak lanjut melalui butir rekomendasi terkait, bukan tindak lanjut umum.');
        $response->assertSessionHasErrors('rekomendasi_id');
        $this->assertDatabaseMissing('tindak_lanjuts', [
            'temuan_id' => $this->temuanAdm->id,
            'uraian_tindak_lanjut' => 'Percobaan tindak lanjut umum tanpa butir.',
        ]);
    }

    public function test_cannot_submit_tindak_lanjut_with_rekomendasi_id_belonging_to_another_temuan(): void
    {
        $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin milik temuan Adm.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $otherTemuan = Temuan::create([
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Lain',
            'uraian_temuan' => 'Uraian temuan lain.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $otherRek = $otherTemuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin milik temuan lain.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $file = UploadedFile::fake()->create('bukti_lain.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $this->temuanAdm),
            [
                'rekomendasi_id' => $otherRek->id,
                'tanggal_tindak_lanjut' => '2026-08-10',
                'uraian_tindak_lanjut' => 'Percobaan pakai rekomendasi temuan lain.',
                'dokumen_bukti' => $file,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_super_admin_verify_rekomendasi_as_belum_sesuai(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi cek belum sesuai',
            'status_penyelesaian' => 'Menunggu Verifikasi',
        ]);

        $tl = TindakLanjut::create([
            'temuan_id' => $this->temuanAdm->id,
            'rekomendasi_id' => $rek->id,
            'opd_id' => $this->dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-10',
            'uraian_tindak_lanjut' => 'TL awal.',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(
            route('temuan.tindak-lanjut.verifikasi', [$this->temuanAdm, $tl]),
            [
                'status_verifikasi' => 'BELUM SESUAI',
                'catatan_verifikasi' => 'Bukti belum lengkap.',
            ]
        );

        $response->assertRedirect();
        $this->assertEquals('Belum Sesuai', $rek->fresh()->status_penyelesaian);
        $this->assertEquals('Belum Sesuai', $this->temuanAdm->fresh()->status_penyelesaian);
    }

    public function test_super_admin_verify_one_of_three_rekomendasi_as_sesuai_keeps_temuan_belum_sesuai(): void
    {
        $rek1 = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi 1: Bentuk tim',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);
        $rek2 = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi 2: Susun draft SOP',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);
        $rek3 = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi 3: Sosialisasi SOP',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);

        $tl1 = TindakLanjut::create([
            'temuan_id' => $this->temuanAdm->id,
            'rekomendasi_id' => $rek1->id,
            'opd_id' => $this->dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-10',
            'uraian_tindak_lanjut' => 'Tim telah dibentuk.',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);
        $rek1->update(['status_penyelesaian' => 'Menunggu Verifikasi']);
        $this->temuanAdm->syncStatusPenyelesaianFromRekomendasi();
        $this->assertEquals('Menunggu Verifikasi', $this->temuanAdm->fresh()->status_penyelesaian);

        // Super Admin verifikasi Rekomendasi 1 sebagai SESUAI
        $response = $this->actingAs($this->superAdmin)->post(
            route('temuan.tindak-lanjut.verifikasi', [$this->temuanAdm, $tl1]),
            [
                'status_verifikasi' => 'SESUAI',
                'catatan_verifikasi' => 'Bukti SK tim pembentukan valid.',
            ]
        );

        $response->assertRedirect();
        $this->assertEquals('Sesuai', $rek1->fresh()->status_penyelesaian);
        $this->assertEquals('Belum Ditindaklanjuti', $rek2->fresh()->status_penyelesaian);
        $this->assertEquals('Belum Ditindaklanjuti', $rek3->fresh()->status_penyelesaian);

        // Status temuan tetap "Belum Sesuai" karena rekomendasi 2 & 3 belum selesai
        $this->assertEquals('Belum Sesuai', $this->temuanAdm->fresh()->status_penyelesaian);
    }

    public function test_when_all_rekomendasi_verified_sesuai_temuan_automatically_becomes_sesuai(): void
    {
        $rek1 = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi 1',
            'status_penyelesaian' => 'Sesuai',
        ]);
        $rek2 = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi 2',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);

        $tl2 = TindakLanjut::create([
            'temuan_id' => $this->temuanAdm->id,
            'rekomendasi_id' => $rek2->id,
            'opd_id' => $this->dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-11',
            'uraian_tindak_lanjut' => 'Tindak lanjut rekomendasi 2.',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);

        // Verifikasi rekomendasi terakhir menjadi SESUAI
        $this->actingAs($this->superAdmin)->post(
            route('temuan.tindak-lanjut.verifikasi', [$this->temuanAdm, $tl2]),
            ['status_verifikasi' => 'SESUAI']
        );

        $this->assertEquals('Sesuai', $rek1->fresh()->status_penyelesaian);
        $this->assertEquals('Sesuai', $rek2->fresh()->status_penyelesaian);
        $this->assertEquals('Sesuai', $this->temuanAdm->fresh()->status_penyelesaian);
        $this->assertDatabaseHas('temuans', [
            'id' => $this->temuanAdm->id,
            'status_penyelesaian' => 'Sesuai',
        ]);
    }

    public function test_rekomendasi_tidak_dapat_ditindaklanjuti_counted_as_completed_in_aggregation(): void
    {
        $rek1 = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi 1',
            'status_penyelesaian' => 'Sesuai',
        ]);
        $rek2 = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi 2: Regulasi telah kedaluwarsa oleh Permen baru',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);

        $tl2 = TindakLanjut::create([
            'temuan_id' => $this->temuanAdm->id,
            'rekomendasi_id' => $rek2->id,
            'opd_id' => $this->dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-12',
            'uraian_tindak_lanjut' => 'Regulasi sudah dicabut permen baru.',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);

        // Super Admin verifikasi sebagai TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH
        $this->actingAs($this->superAdmin)->post(
            route('temuan.tindak-lanjut.verifikasi', [$this->temuanAdm, $tl2]),
            [
                'status_verifikasi' => 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH',
                'alasan_khusus' => 'Permen dasar hukum telah dicabut pemerintah pusat.',
            ]
        );

        $this->assertEquals('Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', $rek2->fresh()->status_penyelesaian);
        // Karena rek 1 Sesuai dan rek 2 Tidak Dapat Ditindaklanjuti Sah -> temuan menjadi "Sesuai"
        $this->assertEquals('Sesuai', $this->temuanAdm->fresh()->status_penyelesaian);
    }

    public function test_rekomendasi_can_be_deleted_if_no_tindak_lanjut_exists(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi sementara.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->adminBpk)->delete(
            route('temuan.rekomendasi.destroy', [$this->temuanAdm, $rek])
        );

        $response->assertRedirect();
        $this->assertSoftDeleted('rekomendasis', ['id' => $rek->id]);
    }

    public function test_rekomendasi_cannot_be_deleted_if_tindak_lanjut_exists(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi dengan TL.',
            'status_penyelesaian' => 'Menunggu Verifikasi',
            'created_by' => $this->adminBpk->id,
        ]);

        TindakLanjut::create([
            'temuan_id' => $this->temuanAdm->id,
            'rekomendasi_id' => $rek->id,
            'opd_id' => $this->dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-10',
            'uraian_tindak_lanjut' => 'TL OPD.',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);

        $response = $this->actingAs($this->adminBpk)->delete(
            route('temuan.rekomendasi.destroy', [$this->temuanAdm, $rek])
        );

        $response->assertStatus(403);
        $this->assertDatabaseHas('rekomendasis', [
            'id' => $rek->id,
            'deleted_at' => null,
        ]);
    }

    public function test_opd_cannot_delete_rekomendasi(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi coba dihapus OPD.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->opdDinkes)->delete(
            route('temuan.rekomendasi.destroy', [$this->temuanAdm, $rek])
        );

        $response->assertStatus(403);
    }

    public function test_pemantau_cannot_delete_rekomendasi(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi coba dihapus Pemantau.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->pemantau)->delete(
            route('temuan.rekomendasi.destroy', [$this->temuanAdm, $rek])
        );

        $response->assertStatus(403);
    }

    public function test_admin_sumber_from_other_source_cannot_delete_rekomendasi(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi coba dihapus BPKP.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->adminBpkp)->delete(
            route('temuan.rekomendasi.destroy', [$this->temuanAdm, $rek])
        );

        $response->assertStatus(403);
    }

    public function test_cannot_delete_rekomendasi_if_status_pengajuan_not_disetujui(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Rekomendasi temuan revisi.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $this->temuanAdm->update(['status_pengajuan' => 'REVISI']);

        $response = $this->actingAs($this->adminBpk)->delete(
            route('temuan.rekomendasi.destroy', [$this->temuanAdm, $rek])
        );

        $response->assertStatus(403);
    }

    public function test_legacy_temuan_without_rekomendasi_still_works_with_direct_tindak_lanjut(): void
    {
        // Temuan administrasi tanpa rekomendasi apa pun
        $this->assertCount(0, $this->temuanAdm->rekomendasis);

        // OPD kirim TL langsung ke temuan (tanpa rekomendasi_id)
        $fLegacy = UploadedFile::fake()->create('bukti_legacy.pdf', 100, 'application/pdf');
        $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $this->temuanAdm),
            [
                'tanggal_tindak_lanjut' => '2026-08-15',
                'uraian_tindak_lanjut' => 'Tindak lanjut legacy langsung ke temuan.',
                'dokumen_bukti' => $fLegacy,
            ]
        );

        $tl = TindakLanjut::where('temuan_id', $this->temuanAdm->id)->first();
        $this->assertNotNull($tl);
        $this->assertNull($tl->rekomendasi_id);
        $this->assertEquals('Menunggu Verifikasi', $this->temuanAdm->fresh()->status_penyelesaian);

        // Super admin verifikasi SESUAI
        $this->actingAs($this->superAdmin)->post(
            route('temuan.tindak-lanjut.verifikasi', [$this->temuanAdm, $tl]),
            ['status_verifikasi' => 'SESUAI']
        );

        // Temuan langsung berubah menjadi Sesuai (tidak ada regresi alur legacy)
        $this->assertEquals('Sesuai', $this->temuanAdm->fresh()->status_penyelesaian);
    }

    public function test_submit_tindak_lanjut_without_rekomendasi_id_to_temuan_kerugian_succeeds(): void
    {
        $this->assertCount(0, $this->temuanKerugian->rekomendasis);

        $fKerugian = UploadedFile::fake()->create('bukti_kerugian.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $this->temuanKerugian),
            [
                'tanggal_tindak_lanjut' => '2026-08-16',
                'uraian_tindak_lanjut' => 'Penyetoran kerugian dinas ke Kas Daerah.',
                'jumlah_setor' => '5.000.000',
                'dokumen_bukti' => $fKerugian,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tindak_lanjuts', [
            'temuan_id' => $this->temuanKerugian->id,
            'rekomendasi_id' => null,
            'uraian_tindak_lanjut' => 'Penyetoran kerugian dinas ke Kas Daerah.',
            'jumlah_setor' => 5000000.00,
        ]);
    }

    public function test_admin_creates_temuan_with_multiple_rekomendasi_items_creates_records_and_numbered_text(): void
    {
        $response = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'judul_temuan' => 'Temuan Multi Rekomendasi Bertahap Otomatis',
            'uraian_temuan' => 'Uraian temuan tertib administrasi multi rekomendasi.',
            'ada_unsur_administrasi' => '1',
            'uraian_administrasi' => 'Kelengkapan dokumen SPJ belum lengkap.',
            'rekomendasi_items' => [
                'Kepala Dinas menerbitkan SOP baru.',
                'Melakukan sosialisasi dan bimbingan teknis.',
                'Menyampaikan laporan kepatuhan ke Inspektorat.',
            ],
        ]);

        $response->assertRedirect();
        $temuan = Temuan::where('judul_temuan', 'Temuan Multi Rekomendasi Bertahap Otomatis')->first();
        $this->assertNotNull($temuan);

        // Rekomendasi bertahap otomatis terbuat 3 record
        $temuan->loadMissing('rekomendasis');
        $this->assertCount(3, $temuan->rekomendasis);
        $this->assertDatabaseHas('rekomendasis', [
            'temuan_id' => $temuan->id,
            'uraian_rekomendasi' => 'Kepala Dinas menerbitkan SOP baru.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);
        $this->assertDatabaseHas('rekomendasis', [
            'temuan_id' => $temuan->id,
            'uraian_rekomendasi' => 'Melakukan sosialisasi dan bimbingan teknis.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);
        $this->assertDatabaseHas('rekomendasis', [
            'temuan_id' => $temuan->id,
            'uraian_rekomendasi' => 'Menyampaikan laporan kepatuhan ke Inspektorat.',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Kolom legacy 'rekomendasi' terisi gabungan bernomor
        $this->assertStringContainsString("1. Kepala Dinas menerbitkan SOP baru.", $temuan->rekomendasi);
        $this->assertStringContainsString("2. Melakukan sosialisasi dan bimbingan teknis.", $temuan->rekomendasi);
        $this->assertStringContainsString("3. Menyampaikan laporan kepatuhan ke Inspektorat.", $temuan->rekomendasi);

        // Halaman detail menampilkan rekomendasi bertahap
        $responseShow = $this->actingAs($this->adminBpk)->get(route('temuan.show', $temuan));
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Daftar Rekomendasi Bertahap');
        $responseShow->assertSee('Kepala Dinas menerbitkan SOP baru.');
        $responseShow->assertSee('Melakukan sosialisasi dan bimbingan teknis.');
        $responseShow->assertSee('+ Tambah Rekomendasi');
    }

    public function test_admin_updates_temuan_modifies_and_deletes_untouched_rekomendasi(): void
    {
        $temuan = Temuan::create([
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Edit Rekomendasi',
            'uraian_temuan' => 'Uraian temuan.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $rekA = $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin A Awal',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $rekB = $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin B Akan Dihapus',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Update form: ubah Rek A, hapus Rek B (tidak dikirim), tambah Rek C baru
        $response = $this->actingAs($this->adminBpk)->put(route('temuan.update', $temuan), [
            'judul_temuan' => 'Temuan Edit Rekomendasi (Updated)',
            'uraian_temuan' => 'Uraian temuan.',
            'ada_unsur_administrasi' => '1',
            'rekomendasi_ids' => [$rekA->id, ''],
            'rekomendasi_items' => ['Poin A Dirubah', 'Poin C Baru'],
        ]);

        $response->assertRedirect();

        // Rek A berubah uraiannya
        $this->assertEquals('Poin A Dirubah', $rekA->fresh()->uraian_rekomendasi);

        // Rek B terhapus (soft delete)
        $this->assertSoftDeleted('rekomendasis', ['id' => $rekB->id]);

        // Rek C terbuat
        $this->assertDatabaseHas('rekomendasis', [
            'temuan_id' => $temuan->id,
            'uraian_rekomendasi' => 'Poin C Baru',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);

        // Kolom legacy rekomendasi tersinkronisasi bernomor
        $this->assertEquals("1. Poin A Dirubah\n2. Poin C Baru", $temuan->fresh()->rekomendasi);
    }

    public function test_admin_cannot_delete_locked_rekomendasi_during_temuan_update(): void
    {
        $temuan = Temuan::create([
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Terkunci',
            'uraian_temuan' => 'Uraian temuan.',
            'ada_unsur_administrasi' => true,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $rek1 = $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin 1 Telah Berjalan',
            'status_penyelesaian' => 'Menunggu Verifikasi',
            'created_by' => $this->adminBpk->id,
        ]);

        TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'rekomendasi_id' => $rek1->id,
            'opd_id' => $this->dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-10',
            'uraian_tindak_lanjut' => 'TL OPD untuk poin 1.',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);

        $rek2 = $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin 2 Belum Disentuh',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Coba kirim update tanpa menyertakan Rek 1 (hanya mengirim Rek 2)
        $this->actingAs($this->adminBpk)->put(route('temuan.update', $temuan), [
            'judul_temuan' => 'Temuan Terkunci',
            'uraian_temuan' => 'Uraian temuan.',
            'ada_unsur_administrasi' => '1',
            'rekomendasi_ids' => [$rek2->id],
            'rekomendasi_items' => ['Poin 2 Diperbarui'],
        ]);

        // Rek 1 TIDAK boleh terhapus karena sudah memiliki tindak lanjut
        $this->assertDatabaseHas('rekomendasis', [
            'id' => $rek1->id,
            'deleted_at' => null,
        ]);
        $this->assertEquals('Poin 2 Diperbarui', $rek2->fresh()->uraian_rekomendasi);
    }

    public function test_validation_requires_at_least_one_rekomendasi_for_administrasi(): void
    {
        $response = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'judul_temuan' => 'Temuan Tanpa Rekomendasi',
            'uraian_temuan' => 'Uraian temuan.',
            'ada_unsur_administrasi' => '1',
            'rekomendasi_items' => ['', '   '],
            'rekomendasi' => '',
        ]);

        $response->assertSessionHasErrors('rekomendasi_items');
    }

    public function test_show_page_displays_empty_state_with_add_button_when_temuan_adm_has_no_rekomendasi(): void
    {
        $response = $this->actingAs($this->adminBpk)->get(route('temuan.show', $this->temuanAdm));

        $response->assertOk();
        $response->assertSee('Daftar Rekomendasi Bertahap');
        $response->assertSee('Belum Ada Rekomendasi Bertahap');
        $response->assertSee('+ Tambah Butir Rekomendasi Pertama');
        $response->assertSee('Riwayat Tindak Lanjut & Bukti Penyetoran', false);
        $response->assertSee('+ Input Tindak Lanjut');
    }

    public function test_show_page_hides_general_input_when_temuan_has_rekomendasi(): void
    {
        $rek = $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin satu aktif',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->adminBpk)->get(route('temuan.show', $this->temuanAdm));

        $response->assertOk();
        $response->assertSee('Daftar Rekomendasi Bertahap');
        $response->assertSee('Poin satu aktif');
        $response->assertSee('+ Tindak Lanjut Butir Ini');
        $response->assertDontSee('+ Input Tindak Lanjut');
        // Because no legacy TL without rekomendasi_id exists, general section is hidden
        $response->assertDontSee('Riwayat Tindak Lanjut & Bukti Penyetoran', false);
        $response->assertDontSee('Riwayat Tindak Lanjut Sebelumnya (Arsip)');
    }

    public function test_show_page_displays_legacy_archive_when_rekomendasi_and_legacy_tl_exist(): void
    {
        // Legacy TL without rekomendasi_id
        TindakLanjut::create([
            'temuan_id' => $this->temuanAdm->id,
            'rekomendasi_id' => null,
            'opd_id' => $this->dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-01',
            'uraian_tindak_lanjut' => 'TL lama sebelum butir dibuat.',
            'status_verifikasi' => 'SESUAI',
            'created_by' => $this->opdDinkes->id,
        ]);

        $this->temuanAdm->rekomendasis()->create([
            'uraian_rekomendasi' => 'Poin baru bertahap',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->adminBpk)->get(route('temuan.show', $this->temuanAdm));

        $response->assertOk();
        $response->assertSee('Daftar Rekomendasi Bertahap');
        $response->assertSee('Poin baru bertahap');
        $response->assertSee('Riwayat Tindak Lanjut Sebelumnya (Arsip)');
        $response->assertSee('TL lama sebelum butir dibuat.');
        $response->assertDontSee('+ Input Tindak Lanjut');

        // Pastikan record tindak lanjut lama tetap ada di database (data tidak hilang)
        $this->assertDatabaseHas('tindak_lanjuts', [
            'temuan_id' => $this->temuanAdm->id,
            'uraian_tindak_lanjut' => 'TL lama sebelum butir dibuat.',
            'rekomendasi_id' => null,
            'status_verifikasi' => 'SESUAI',
        ]);
    }
}
