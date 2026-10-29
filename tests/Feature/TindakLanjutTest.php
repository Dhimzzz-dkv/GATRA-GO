<?php

namespace Tests\Feature;

use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TindakLanjutTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $pemantau;
    protected User $opdDinkes;
    protected User $opdDikbud;
    protected Temuan $temuanBpk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
        $this->opdDikbud = User::where('email', 'opd.dikbud@gorontaloprov.go.id')->first();

        $this->temuanBpk = Temuan::where('sumber_pemeriksaan', 'BPK')->first();
    }

    public function test_admin_sumber_can_submit_follow_up_with_payment_and_document(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('Bukti_STS_Dinkes.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $this->temuanBpk), [
            'tanggal_tindak_lanjut' => '2025-08-01',
            'uraian_tindak_lanjut' => 'Penyetoran tahap 3 ke kas daerah sebesar Rp 50.000.000.',
            'jumlah_setor' => '50.000.000',
            'nomor_bukti_setor' => 'STS-2025/08/TEST',
            'dokumen_bukti' => $file,
        ]);

        $response->assertRedirect();
        $tl = TindakLanjut::where('nomor_bukti_setor', 'STS-2025/08/TEST')->first();
        $this->assertNotNull($tl);
        $this->assertEquals('MENUNGGU VERIFIKASI', $tl->status_verifikasi);
        $this->assertEquals(50000000.00, $tl->jumlah_setor);
        $this->assertEquals($this->adminBpk->id, $tl->created_by);
        $this->assertEquals('Menunggu Verifikasi', $this->temuanBpk->fresh()->status_penyelesaian);
        Storage::disk('local')->assertExists($tl->dokumen_bukti);
    }

    public function test_opd_can_submit_follow_up_for_own_opd_finding(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('Bukti_Setor_Dinkes.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->opdDinkes)->post(route('temuan.tindak-lanjut.store', $this->temuanBpk), [
            'tanggal_tindak_lanjut' => '2025-08-01',
            'uraian_tindak_lanjut' => 'Realisasi tindak lanjut penyetoran kas daerah oleh Dinkes.',
            'jumlah_setor' => '25.000.000',
            'nomor_bukti_setor' => 'STS-DINKES-001',
            'dokumen_bukti' => $file,
        ]);

        $response->assertRedirect();
        $tl = TindakLanjut::where('nomor_bukti_setor', 'STS-DINKES-001')->first();
        $this->assertNotNull($tl);
        $this->assertEquals('MENUNGGU VERIFIKASI', $tl->status_verifikasi);
        $this->assertEquals(25000000.00, $tl->jumlah_setor);
        $this->assertEquals($this->opdDinkes->id, $tl->created_by);
        $this->assertEquals($this->opdDinkes->opd_id, $tl->opd_id);
        $this->assertEquals('Menunggu Verifikasi', $this->temuanBpk->fresh()->status_penyelesaian);
        Storage::disk('local')->assertExists($tl->dokumen_bukti);
    }

    public function test_opd_cannot_submit_follow_up_for_other_opd_finding(): void
    {
        // opdDikbud attempting to submit for temuanBpk (which belongs to Dinkes)
        $response = $this->actingAs($this->opdDikbud)->post(route('temuan.tindak-lanjut.store', $this->temuanBpk), [
            'tanggal_tindak_lanjut' => '2025-08-01',
            'uraian_tindak_lanjut' => 'Illegal attempt across OPD',
        ]);
        $response->assertStatus(403);
    }

    public function test_pemantau_cannot_submit_follow_up(): void
    {
        $resPemantau = $this->actingAs($this->pemantau)->post(route('temuan.tindak-lanjut.store', $this->temuanBpk), [
            'tanggal_tindak_lanjut' => '2025-08-01',
            'uraian_tindak_lanjut' => 'Illegal attempt by Pemantau',
        ]);
        $resPemantau->assertStatus(403);
    }

    public function test_opd_can_delete_own_pending_follow_up(): void
    {
        $tl = TindakLanjut::create([
            'temuan_id' => $this->temuanBpk->id,
            'lhp_id' => $this->temuanBpk->lhp_id,
            'opd_id' => $this->opdDinkes->opd_id,
            'tanggal_tindak_lanjut' => '2025-08-02',
            'uraian_tindak_lanjut' => 'Draft TL Dinkes',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->opdDinkes->id,
        ]);

        // Other OPD cannot delete
        $otherRes = $this->actingAs($this->opdDikbud)->delete(route('temuan.tindak-lanjut.destroy', [$this->temuanBpk, $tl]));
        $otherRes->assertStatus(403);

        // Creator OPD can delete
        $res = $this->actingAs($this->opdDinkes)->delete(route('temuan.tindak-lanjut.destroy', [$this->temuanBpk, $tl]));
        $res->assertRedirect();
        $this->assertSoftDeleted('tindak_lanjuts', ['id' => $tl->id]);
    }

    public function test_admin_sumber_cannot_submit_follow_up_for_other_sources(): void
    {
        $adminBpkp = User::where('email', 'admin.bpkp@inspektorat.go.id')->first();
        $response = $this->actingAs($adminBpkp)->post(route('temuan.tindak-lanjut.store', $this->temuanBpk), [
            'tanggal_tindak_lanjut' => '2025-08-01',
            'uraian_tindak_lanjut' => 'Illegal attempt across source',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_verify_follow_up_as_sesuai(): void
    {
        $tl = TindakLanjut::where('nomor_bukti_setor', 'STS-2025/07/045')->first();

        $response = $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$this->temuanBpk, $tl]), [
            'status_verifikasi' => 'SESUAI',
            'catatan_verifikasi' => 'Setoran pelunasan telah diverifikasi sesuai.',
        ]);

        $response->assertRedirect();
        $freshTl = $tl->fresh();
        $this->assertEquals('SESUAI', $freshTl->status_verifikasi);
        $this->assertEquals($this->superAdmin->id, $freshTl->verified_by);
        $this->assertEquals('Sesuai', $this->temuanBpk->fresh()->status_penyelesaian);
    }

    public function test_super_admin_can_verify_follow_up_as_belum_sesuai_with_note(): void
    {
        $tl = TindakLanjut::where('nomor_bukti_setor', 'STS-2025/07/045')->first();

        $response = $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$this->temuanBpk, $tl]), [
            'status_verifikasi' => 'BELUM SESUAI',
            'catatan_verifikasi' => 'Bukti setor belum tertera validasi bank penerima.',
        ]);

        $response->assertRedirect();
        $freshTl = $tl->fresh();
        $this->assertEquals('BELUM SESUAI', $freshTl->status_verifikasi);
        $this->assertEquals('Bukti setor belum tertera validasi bank penerima.', $freshTl->catatan_verifikasi);
        $this->assertEquals('Belum Sesuai', $this->temuanBpk->fresh()->status_penyelesaian);
    }

    public function test_super_admin_can_verify_as_special_unresolvable_status_with_legal_reason(): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create('Telaahan_Hukum_Inkracht.pdf', 500, 'application/pdf');
        $tl = TindakLanjut::where('nomor_bukti_setor', 'STS-2025/07/045')->first();

        $response = $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$this->temuanBpk, $tl]), [
            'status_verifikasi' => 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH',
            'alasan_khusus' => 'Penyedia pihak ketiga telah dinyatakan pailit berdasarkan Putusan Pengadilan Niaga inkracht.',
            'dokumen_pendukung_alasan' => $file,
        ]);

        $response->assertRedirect();
        $fresh = $this->temuanBpk->fresh();
        $this->assertEquals('Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', $fresh->status_penyelesaian);
        $this->assertStringContainsString('pailit', $fresh->alasan_khusus);
        Storage::disk('local')->assertExists($fresh->dokumen_pendukung_alasan);
    }

    public function test_non_super_admin_cannot_verify_follow_up(): void
    {
        $tl = TindakLanjut::where('nomor_bukti_setor', 'STS-2025/07/045')->first();

        $response1 = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.verifikasi', [$this->temuanBpk, $tl]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $response1->assertStatus(403);

        $response2 = $this->actingAs($this->opdDinkes)->post(route('temuan.tindak-lanjut.verifikasi', [$this->temuanBpk, $tl]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $response2->assertStatus(403);

        $response3 = $this->actingAs($this->pemantau)->post(route('temuan.tindak-lanjut.verifikasi', [$this->temuanBpk, $tl]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $response3->assertStatus(403);
    }

    public function test_manual_status_penyelesaian_route_is_disabled(): void
    {
        $response = $this->actingAs($this->superAdmin)->post('/temuan/' . $this->temuanBpk->id . '/status-penyelesaian', [
            'status_penyelesaian' => 'Sesuai',
        ]);

        $response->assertStatus(404);
    }

    public function test_financial_calculations_are_accurate(): void
    {
        // temuanBpk has total loss: Rp 450.000.000
        // Seeded verified TL: Rp 200.000.000
        $freshTemuan = $this->temuanBpk->fresh();
        $this->assertEquals(200000000.00, $freshTemuan->total_disetor);
        $this->assertEquals(250000000.00, $freshTemuan->sisa_kerugian);
        $this->assertEquals('Rp 200.000.000', $freshTemuan->formatted_total_disetor);
        $this->assertEquals('Rp 250.000.000', $freshTemuan->formatted_sisa_kerugian);
    }

    public function test_user_can_download_follow_up_document(): void
    {
        Storage::fake('local');

        $tl = TindakLanjut::first();
        $filePath = 'tindak_lanjut/test_sts.pdf';
        Storage::disk('local')->put($filePath, 'fake sts content');

        $tl->update([
            'dokumen_bukti' => $filePath,
            'nama_dokumen_asli' => 'STS_Sample.pdf',
        ]);

        $response = $this->actingAs($this->opdDinkes)->get(route('temuan.tindak-lanjut.download', [$tl->temuan_id, $tl]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_partial_payment_keeps_temuan_belum_sesuai_until_full(): void
    {
        $dinkes = \App\Models\Opd::where('kode', 'DINKES')->first();
        $temuanKerugian = Temuan::create([
            'pemeriksaan_id' => $this->temuanBpk->pemeriksaan_id,
            'lhp_id' => $this->temuanBpk->lhp_id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Kerugian Daerah Uji Coba',
            'uraian_temuan' => 'Kerugian perjalanan dinas',
            'ada_unsur_administrasi' => false,
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 10000000,
            'rekomendasi' => 'Setor ke kasda',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);

        // 1. Submit partial payment Rp 4.000.000
        $tl1 = TindakLanjut::create([
            'temuan_id' => $temuanKerugian->id,
            'lhp_id' => $temuanKerugian->lhp_id,
            'opd_id' => $dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-01',
            'uraian_tindak_lanjut' => 'Setoran tahap 1 Rp 4 juta',
            'jumlah_setor' => 4000000,
            'nomor_bukti_setor' => 'STS-001',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        // Super Admin verifies TL #1 as SESUAI
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuanKerugian, $tl1]), [
            'status_verifikasi' => 'SESUAI',
        ]);

        // Temuan still Belum Sesuai because Rp 4jt < Rp 10jt
        $this->assertEquals('Belum Sesuai', $temuanKerugian->fresh()->status_penyelesaian);
        $this->assertEquals(4000000, $temuanKerugian->fresh()->total_disetor);
        $this->assertEquals(6000000, $temuanKerugian->fresh()->sisa_kerugian);
        $this->assertEquals(40, $temuanKerugian->fresh()->progress_persen);

        // 2. Submit remaining payment Rp 6.000.000
        $tl2 = TindakLanjut::create([
            'temuan_id' => $temuanKerugian->id,
            'lhp_id' => $temuanKerugian->lhp_id,
            'opd_id' => $dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-05',
            'uraian_tindak_lanjut' => 'Setoran pelunasan Rp 6 juta',
            'jumlah_setor' => 6000000,
            'nomor_bukti_setor' => 'STS-002',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        // Super Admin verifies TL #2 as SESUAI
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuanKerugian, $tl2]), [
            'status_verifikasi' => 'SESUAI',
        ]);

        // Temuan is now Sesuai (fully paid)
        $this->assertEquals('Sesuai', $temuanKerugian->fresh()->status_penyelesaian);
        $this->assertEquals(10000000, $temuanKerugian->fresh()->total_disetor);
        $this->assertEquals(0, $temuanKerugian->fresh()->sisa_kerugian);
        $this->assertEquals(100, $temuanKerugian->fresh()->progress_persen);
    }

    public function test_administrasi_follow_up_becomes_sesuai_on_verification(): void
    {
        $dinkes = \App\Models\Opd::where('kode', 'DINKES')->first();
        $temuanAdm = Temuan::create([
            'pemeriksaan_id' => $this->temuanBpk->pemeriksaan_id,
            'lhp_id' => $this->temuanBpk->lhp_id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Administrasi SOP',
            'uraian_temuan' => 'SOP belum ditandatangani',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => false,
            'nilai_kerugian' => 0,
            'rekomendasi' => 'Terbitkan SOP definitif',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);

        $tl = TindakLanjut::create([
            'temuan_id' => $temuanAdm->id,
            'lhp_id' => $temuanAdm->lhp_id,
            'opd_id' => $dinkes->id,
            'tanggal_tindak_lanjut' => '2026-08-01',
            'uraian_tindak_lanjut' => 'SOP telah ditandatangani Kepala Dinas',
            'jumlah_setor' => 0,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuanAdm, $tl]), [
            'status_verifikasi' => 'SESUAI',
        ]);

        $this->assertEquals('Sesuai', $temuanAdm->fresh()->status_penyelesaian);
    }

    public function test_submitting_tindak_lanjut_requires_dokumen_bukti_for_both_administrasi_and_kerugian(): void
    {
        Storage::fake('local');

        // 1. Temuan Kerugian: Submit TANPA dokumen_bukti -> Harus gagal validasi
        $resKerugianWithoutFile = $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $this->temuanBpk),
            [
                'tanggal_tindak_lanjut' => '2026-08-01',
                'uraian_tindak_lanjut' => 'Penyetoran tanpa melampirkan berkas bukti.',
                'jumlah_setor' => '10.000.000',
            ]
        );
        $resKerugianWithoutFile->assertSessionHasErrors('dokumen_bukti');

        // 2. Temuan Kerugian: Submit DENGAN dokumen_bukti -> Berhasil
        $fileKerugian = UploadedFile::fake()->create('bukti_setor.pdf', 200, 'application/pdf');
        $resKerugianWithFile = $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $this->temuanBpk),
            [
                'tanggal_tindak_lanjut' => '2026-08-01',
                'uraian_tindak_lanjut' => 'Penyetoran dengan melampirkan berkas bukti lengkap.',
                'jumlah_setor' => '10.000.000',
                'dokumen_bukti' => $fileKerugian,
            ]
        );
        $resKerugianWithFile->assertRedirect();
        $this->assertDatabaseHas('tindak_lanjuts', [
            'temuan_id' => $this->temuanBpk->id,
            'uraian_tindak_lanjut' => 'Penyetoran dengan melampirkan berkas bukti lengkap.',
        ]);

        // 3. Temuan Administrasi: Submit TANPA dokumen_bukti -> Harus gagal validasi
        $temuanAdm = Temuan::create([
            'pemeriksaan_id' => $this->temuanBpk->pemeriksaan_id,
            'lhp_id' => $this->temuanBpk->lhp_id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->opdDinkes->opd_id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Bukti Administrasi',
            'uraian_temuan' => 'Uraian administrasi.',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => false,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
        ]);

        $resAdmWithoutFile = $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $temuanAdm),
            [
                'tanggal_tindak_lanjut' => '2026-08-01',
                'uraian_tindak_lanjut' => 'Tindak lanjut administrasi tanpa bukti berkas.',
            ]
        );
        $resAdmWithoutFile->assertSessionHasErrors('dokumen_bukti');

        // 4. Temuan Administrasi: Submit DENGAN dokumen_bukti -> Berhasil
        $fileAdm = UploadedFile::fake()->create('surat_tindak_lanjut.pdf', 150, 'application/pdf');
        $resAdmWithFile = $this->actingAs($this->opdDinkes)->post(
            route('temuan.tindak-lanjut.store', $temuanAdm),
            [
                'tanggal_tindak_lanjut' => '2026-08-01',
                'uraian_tindak_lanjut' => 'Tindak lanjut administrasi dengan berkas bukti resmi.',
                'dokumen_bukti' => $fileAdm,
            ]
        );
        $resAdmWithFile->assertRedirect();
        $this->assertDatabaseHas('tindak_lanjuts', [
            'temuan_id' => $temuanAdm->id,
            'uraian_tindak_lanjut' => 'Tindak lanjut administrasi dengan berkas bukti resmi.',
        ]);
    }
}
