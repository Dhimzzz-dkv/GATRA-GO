<?php

namespace Tests\Feature;

use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemuanTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminInsp;
    protected User $pemantau;
    protected User $opdDinkes;
    protected Pemeriksaan $pemeriksaanBpk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
        $this->pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        $this->pemeriksaanBpk = Pemeriksaan::where('nomor_spt', '05/SPT/BPK-GOR/02/2025')->first();
    }

    public function test_super_admin_can_view_all_findings(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('temuan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelebihan Pembayaran atas Pengadaan Alat Kesehatan');
        $response->assertSee('Selisih Pencatatan Persediaan Obat pada Gudang Farmasi');
    }

    public function test_admin_bpk_only_sees_bpk_findings(): void
    {
        $response = $this->actingAs($this->adminBpk)->get(route('temuan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelebihan Pembayaran atas Pengadaan Alat Kesehatan');
        $response->assertDontSee('Selisih Pencatatan Persediaan Obat pada Gudang Farmasi'); // Inspektorat
        $response->assertDontSee('Kekurangan Kelengkapan Dokumen Pertanggungjawaban SPJ'); // ITJEN
    }

    public function test_admin_bpk_can_create_finding_without_nomor_temuan(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('Bukti_Temuan_BPK.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $this->pemeriksaanBpk->id,
            'judul_temuan' => 'Kekurangan Volume Pekerjaan Pembangunan Puskesmas',
            'uraian_temuan' => 'Terdapat kekurangan volume terpasang pada pekerjaan struktur beton.',
            'penyebab' => 'Pengawas kurang teliti dalam opname fisik.',
            'akibat' => 'Kerugian kas daerah senilai Rp 85.000.000.',
            'rekomendasi' => 'Penyedia menyetorkan kekurangan volume sebesar Rp 85.000.000.',
            'ada_unsur_administrasi' => '1',
            'uraian_administrasi' => 'As-built drawing belum disahkan konsultan perencana.',
            'ada_unsur_kerugian' => '1',
            'nilai_kerugian' => '85000000',
            'uraian_kerugian' => 'Kekurangan volume Rp 85.000.000,00.',
            'dokumen_pendukung' => $file,
        ]);

        $temuan = Temuan::where('judul_temuan', 'Kekurangan Volume Pekerjaan Pembangunan Puskesmas')->first();
        $this->assertNotNull($temuan);
        $this->assertEquals('DISETUJUI', $temuan->status_pengajuan);
        $this->assertEquals('Belum Ditindaklanjuti', $temuan->status_penyelesaian);
        $this->assertTrue($temuan->ada_unsur_administrasi);
        $this->assertTrue($temuan->ada_unsur_kerugian);
        $this->assertEquals(85000000.00, $temuan->nilai_kerugian);
        $this->assertEquals('BPK', $temuan->sumber_pemeriksaan);
        $this->assertEquals($this->adminBpk->id, $temuan->created_by);

        Storage::disk('local')->assertExists($temuan->dokumen_pendukung);
        $response->assertRedirect(route('temuan.show', $temuan));
    }

    public function test_admin_can_create_finding_when_pemeriksaan_is_draft(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'DRAFT']);

        // Akses form pembuatan temuan diperbolehkan
        $responseUrl = $this->actingAs($this->adminBpk)->get(route('temuan.create', ['pemeriksaan_id' => $this->pemeriksaanBpk->id]));
        $responseUrl->assertStatus(200);

        // Post simpan temuan berhasil dengan status DRAFT
        $responsePost = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $this->pemeriksaanBpk->id,
            'judul_temuan' => 'Temuan on Draft Pemeriksaan',
            'uraian_temuan' => 'Uraian temuan draf',
        ]);
        $responsePost->assertRedirect();
        $this->assertDatabaseHas('temuans', [
            'judul_temuan' => 'Temuan on Draft Pemeriksaan',
            'status_pengajuan' => 'DRAFT',
        ]);
    }

    public function test_admin_cannot_create_finding_when_pemeriksaan_is_menunggu_persetujuan(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        // Data dikunci saat proses verifikasi Super Admin
        $responseUrl = $this->actingAs($this->adminBpk)->get(route('temuan.create', ['pemeriksaan_id' => $this->pemeriksaanBpk->id]));
        $responseUrl->assertStatus(403);

        $responsePost = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $this->pemeriksaanBpk->id,
            'judul_temuan' => 'Temuan on Menunggu Persetujuan',
            'uraian_temuan' => 'Uraian temuan',
        ]);
        $responsePost->assertStatus(403);
    }

    public function test_admin_can_create_finding_when_pemeriksaan_is_perlu_revisi(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'PERLU REVISI']);

        // Akses form pembuatan temuan diperbolehkan untuk merevisi
        $responseUrl = $this->actingAs($this->adminBpk)->get(route('temuan.create', ['pemeriksaan_id' => $this->pemeriksaanBpk->id]));
        $responseUrl->assertStatus(200);

        $responsePost = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $this->pemeriksaanBpk->id,
            'judul_temuan' => 'Temuan on Perlu Revisi',
            'uraian_temuan' => 'Uraian temuan revisi',
        ]);
        $responsePost->assertRedirect();
        $this->assertDatabaseHas('temuans', [
            'judul_temuan' => 'Temuan on Perlu Revisi',
            'status_pengajuan' => 'DRAFT',
        ]);
    }

    public function test_pemeriksaan_show_displays_correct_alert_based_on_status(): void
    {
        // 1. DRAFT
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'DRAFT']);
        $resDraft = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.show', $this->pemeriksaanBpk));
        $resDraft->assertStatus(200);
        $resDraft->assertSee('+ Tambah Temuan');

        // 2. MENUNGGU PERSETUJUAN
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);
        $resPending = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.show', $this->pemeriksaanBpk));
        $resPending->assertStatus(200);

        // 3. PERLU REVISI
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'PERLU REVISI']);
        $resRevisi = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.show', $this->pemeriksaanBpk));
        $resRevisi->assertStatus(200);
        $resRevisi->assertSee('+ Tambah Temuan');

        // 4. DISETUJUI
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'DISETUJUI']);
        $resApprove = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.show', $this->pemeriksaanBpk));
        $resApprove->assertStatus(200);
        $resApprove->assertSee('+ Tambah Temuan');
    }

    public function test_admin_cannot_create_finding_for_other_sources(): void
    {
        $pemeriksaanInsp = Pemeriksaan::where('nomor_spt', '700/ITPROV/042/SPT/2025')->first();
        $pemeriksaanInsp->update(['status_pengajuan' => 'DISETUJUI']);

        // Admin BPK attempts to create finding on Inspektorat examination
        $response = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $pemeriksaanInsp->id,
            'judul_temuan' => 'Illegal finding',
            'uraian_temuan' => 'Illegal description',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_edit_finding_for_other_sources(): void
    {
        $temuanInsp = Temuan::where('sumber_pemeriksaan', 'Inspektorat')->first();

        // Admin BPK attempts to update Inspektorat finding
        $response = $this->actingAs($this->adminBpk)->put(route('temuan.update', $temuanInsp), [
            'judul_temuan' => 'Updated without permission',
            'uraian_temuan' => 'Updated without permission',
        ]);

        $response->assertStatus(403);
    }

    public function test_opd_cannot_create_finding(): void
    {
        $response = $this->actingAs($this->opdDinkes)->get(route('temuan.create'));
        $response->assertStatus(403);
    }

    public function test_opd_only_sees_their_own_findings(): void
    {
        $response = $this->actingAs($this->opdDinkes)->get(route('temuan.index'));

        $response->assertStatus(200);
        $response->assertSee('Kelebihan Pembayaran atas Pengadaan Alat Kesehatan'); // Dinkes
        $response->assertDontSee('Selisih Pencatatan Persediaan Obat pada Gudang Farmasi'); // RSUD
    }

    public function test_user_can_download_finding_document(): void
    {
        Storage::fake('local');

        $temuan = Temuan::first();
        $filePath = 'temuan/test_doc.pdf';
        Storage::disk('local')->put($filePath, 'fake finding pdf content');

        $temuan->update([
            'dokumen_pendukung' => $filePath,
            'nama_dokumen_asli' => 'Bukti_Temuan.pdf',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('temuan.download-dokumen', $temuan));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_admin_can_view_edit_finding_page(): void
    {
        $temuan = Temuan::where('sumber_pemeriksaan', 'BPK')->first();

        $response = $this->actingAs($this->adminBpk)->get(route('temuan.edit', $temuan));

        $response->assertStatus(200);
        $response->assertSee('Edit Data Temuan');
        $response->assertSee($temuan->judul_temuan);
    }
}
