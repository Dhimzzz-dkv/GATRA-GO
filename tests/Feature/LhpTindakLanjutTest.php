<?php

namespace Tests\Feature;

use App\Models\Lhp;
use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Role;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LhpTindakLanjutTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminBpkp;
    protected User $adminItjen;
    protected User $adminInspektorat;
    protected User $pemantau;
    protected User $opdUser;
    protected Opd $opd;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $roleSuper = Role::firstOrCreate(['name' => 'super_admin'], ['display_name' => 'Super Admin']);
        $roleBpk = Role::firstOrCreate(['name' => 'admin_bpk'], ['display_name' => 'Admin BPK', 'sumber_pemeriksaan' => 'BPK']);
        $roleBpkp = Role::firstOrCreate(['name' => 'admin_bpkp'], ['display_name' => 'Admin BPKP', 'sumber_pemeriksaan' => 'BPKP']);
        $roleItjen = Role::firstOrCreate(['name' => 'admin_itjen'], ['display_name' => 'Admin ITJEN', 'sumber_pemeriksaan' => 'ITJEN']);
        $roleInsp = Role::firstOrCreate(['name' => 'admin_inspektorat'], ['display_name' => 'Admin Inspektorat', 'sumber_pemeriksaan' => 'Inspektorat']);
        $rolePemantau = Role::firstOrCreate(['name' => 'pemantau'], ['display_name' => 'Pemantau']);
        $roleOpd = Role::firstOrCreate(['name' => 'opd'], ['display_name' => 'OPD']);

        $this->opd = Opd::create(['kode' => 'DISDIK', 'nama' => 'Dinas Pendidikan', 'singkatan' => 'Disdik', 'is_active' => true]);

        $this->superAdmin = User::factory()->create(['role_id' => $roleSuper->id]);
        $this->adminBpk = User::factory()->create(['role_id' => $roleBpk->id]);
        $this->adminBpkp = User::factory()->create(['role_id' => $roleBpkp->id]);
        $this->adminItjen = User::factory()->create(['role_id' => $roleItjen->id]);
        $this->adminInspektorat = User::factory()->create(['role_id' => $roleInsp->id]);
        $this->pemantau = User::factory()->create(['role_id' => $rolePemantau->id]);
        $this->opdUser = User::factory()->create(['role_id' => $roleOpd->id, 'opd_id' => $this->opd->id]);
    }

    private function createApprovedPemeriksaan(string $sumber): Pemeriksaan
    {
        return Pemeriksaan::create([
            'sumber_pemeriksaan' => $sumber,
            'opd_id' => $this->opd->id,
            'nomor_spt' => 'SPT/' . $sumber . '/2026/001',
            'tanggal_spt' => now(),
            'tahun' => 2026,
            'status_pengajuan' => 'DISETUJUI',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    private function createLhp(Pemeriksaan $pemeriksaan): Lhp
    {
        // Catatan: tabel `lhps` TIDAK memiliki kolom `status_pengajuan`.
        // Status pengajuan hanya ada pada `pemeriksaans` dan `temuans`.
        // Sebelumnya baris ini menulis `status_pengajuan` dan nilainya
        // terbuang diam-diam; kini ditangkap oleh preventSilentlyDiscardingAttributes.
        return Lhp::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'nomor_lhp' => 'LHP/' . $pemeriksaan->sumber_pemeriksaan . '/001',
            'tanggal_lhp' => now(),
            'judul_lhp' => 'LHP Pemeriksaan ' . $pemeriksaan->sumber_pemeriksaan,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    private function createTemuan(Pemeriksaan $pemeriksaan, Lhp $lhp, float $nilaiKerugian = 10000000, bool $isAdm = false): Temuan
    {
        return Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'lhp_id' => $lhp->id,
            'sumber_pemeriksaan' => $pemeriksaan->sumber_pemeriksaan,
            'opd_id' => $this->opd->id,
            'tahun' => 2026,
            'judul_temuan' => $isAdm ? 'Temuan Kelengkapan Dokumen SOP' : 'Kelebihan Pembayaran Honorarium',
            'uraian_temuan' => $isAdm ? 'SOP belum ditandatangani' : 'Terdapat pembayaran ganda',
            'rekomendasi' => $isAdm ? 'Melengkapi SOP' : 'Menyetorkan kelebihan ke Kas Daerah',
            'ada_unsur_administrasi' => $isAdm,
            'ada_unsur_kerugian' => $nilaiKerugian > 0 && !$isAdm,
            'nilai_kerugian' => $isAdm ? 0 : $nilaiKerugian,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_admin_bpk_can_input_tindak_lanjut_for_bpk_temuan(): void
    {
        $pem = $this->createApprovedPemeriksaan('BPK');
        $lhp = $this->createLhp($pem);
        $temuan = $this->createTemuan($pem, $lhp, 10000000);

        // Submit follow-up 1 for Temuan
        $file = UploadedFile::fake()->create('bukti_tl_1.pdf', 500, 'application/pdf');
        $postRes = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-28',
            'uraian_tindak_lanjut' => 'Telah dilakukan penyetoran tahap 1',
            'jumlah_setor' => '3.000.000',
            'nomor_bukti_setor' => 'STS-001',
            'tanggal_setor' => '2026-08-28',
            'dokumen_bukti' => $file,
        ]);

        $postRes->assertRedirect();
        $this->assertDatabaseHas('tindak_lanjuts', [
            'temuan_id' => $temuan->id,
            'lhp_id' => $lhp->id,
            'jumlah_setor' => 3000000,
            'nomor_bukti_setor' => 'STS-001',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        $this->assertEquals('Menunggu Verifikasi', $temuan->fresh()->status_penyelesaian);
    }

    public function test_admin_bpk_cannot_create_tindak_lanjut_for_bpkp_temuan(): void
    {
        $pemBpkp = $this->createApprovedPemeriksaan('BPKP');
        $lhpBpkp = $this->createLhp($pemBpkp);
        $temuanBpkp = $this->createTemuan($pemBpkp, $lhpBpkp, 5000000);

        $response = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuanBpkp), [
            'tanggal_tindak_lanjut' => '2026-08-28',
            'uraian_tindak_lanjut' => 'Bypass attempt',
        ]);

        $response->assertForbidden();
    }

    public function test_opd_can_create_tindak_lanjut_and_pemantau_is_forbidden(): void
    {
        $pem = $this->createApprovedPemeriksaan('BPK');
        $lhp = $this->createLhp($pem);
        $temuan = $this->createTemuan($pem, $lhp);

        $responsePemantau = $this->actingAs($this->pemantau)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-28',
            'uraian_tindak_lanjut' => 'Pemantau trying to create TL',
        ]);
        $responsePemantau->assertForbidden();

        $fileOpd = UploadedFile::fake()->create('bukti_opd.pdf', 100, 'application/pdf');
        $responseOpd = $this->actingAs($this->opdUser)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-28',
            'uraian_tindak_lanjut' => 'OPD tindak lanjut resmi',
            'dokumen_bukti' => $fileOpd,
        ]);
        $responseOpd->assertRedirect();
        $this->assertEquals('Menunggu Verifikasi', $temuan->fresh()->status_penyelesaian);

        // Another OPD
        $otherOpd = Opd::create(['kode' => 'DISNAKER', 'nama' => 'Dinas Tenaga Kerja', 'is_active' => true]);
        $otherOpdUser = User::factory()->create(['role_id' => $this->opdUser->role_id, 'opd_id' => $otherOpd->id]);
        $responseOther = $this->actingAs($otherOpdUser)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-28',
            'uraian_tindak_lanjut' => 'Other OPD trying to create TL',
        ]);
        $responseOther->assertForbidden();
    }

    public function test_temuan_supports_multiple_sequential_follow_ups_and_staged_payments(): void
    {
        $pem = $this->createApprovedPemeriksaan('BPK');
        $lhp = $this->createLhp($pem);
        $temuan = $this->createTemuan($pem, $lhp, 10000000);

        // Tindak Lanjut 1: 3.000.000
        $f1 = UploadedFile::fake()->create('sts_01.pdf', 100, 'application/pdf');
        $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-01-10',
            'uraian_tindak_lanjut' => 'Penyetoran tahap 1',
            'jumlah_setor' => '3.000.000',
            'nomor_bukti_setor' => 'STS-01',
            'dokumen_bukti' => $f1,
        ]);
        $tl1 = TindakLanjut::where('nomor_bukti_setor', 'STS-01')->first();

        // Super Admin verifies TL 1 as BELUM SESUAI (partial payment)
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl1]), [
            'status_verifikasi' => 'BELUM SESUAI',
            'catatan_verifikasi' => 'Pembayaran baru tahap 1 (Rp 3.000.000), sisa Rp 7.000.000',
        ]);

        $this->assertEquals('Belum Sesuai', $temuan->fresh()->status_penyelesaian);

        // Tindak Lanjut 2: 4.000.000
        $f2 = UploadedFile::fake()->create('sts_02.pdf', 100, 'application/pdf');
        $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-02-15',
            'uraian_tindak_lanjut' => 'Penyetoran tahap 2',
            'jumlah_setor' => '4.000.000',
            'nomor_bukti_setor' => 'STS-02',
            'dokumen_bukti' => $f2,
        ]);
        $tl2 = TindakLanjut::where('nomor_bukti_setor', 'STS-02')->first();

        // Tindak Lanjut 3: 3.000.000
        $f3 = UploadedFile::fake()->create('sts_03.pdf', 100, 'application/pdf');
        $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-03-20',
            'uraian_tindak_lanjut' => 'Penyetoran tahap 3 (Pelunasan)',
            'jumlah_setor' => '3.000.000',
            'nomor_bukti_setor' => 'STS-03',
            'dokumen_bukti' => $f3,
        ]);
        $tl3 = TindakLanjut::where('nomor_bukti_setor', 'STS-03')->first();

        // Super Admin verifies all as SESUAI
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl1]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl2]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl3]), [
            'status_verifikasi' => 'SESUAI',
        ]);

        $freshTemuan = $temuan->fresh();
        $freshTemuan->loadMissing('tindakLanjuts');
        $this->assertEquals(3, $freshTemuan->tindakLanjuts->count());
        $this->assertEquals(10000000, $freshTemuan->total_disetor);
        $this->assertEquals(0, $freshTemuan->sisa_kerugian);
        $this->assertEquals(100, $freshTemuan->progress_persen);
        $this->assertEquals('Sesuai', $freshTemuan->status_penyelesaian);
    }

    public function test_super_admin_verification_tidak_dapat_ditindaklanjuti_requires_reason(): void
    {
        $pem = $this->createApprovedPemeriksaan('BPK');
        $lhp = $this->createLhp($pem);
        $temuan = $this->createTemuan($pem, $lhp, 5000000);

        $tl = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $lhp->id,
            'opd_id' => $this->opd->id,
            'tanggal_tindak_lanjut' => now(),
            'uraian_tindak_lanjut' => 'Entitas sudah dibubarkan',
            'created_by' => $this->adminBpk->id,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        // Attempt without reason should fail validation
        $failRes = $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl]), [
            'status_verifikasi' => 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH',
        ]);
        $failRes->assertSessionHasErrors('alasan_khusus');

        // With reason should succeed
        $successRes = $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl]), [
            'status_verifikasi' => 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH',
            'alasan_khusus' => 'Pihak ketiga telah dinyatakan pailit berdasarkan Putusan Pengadilan Niaga No. 12/Pdt.Sus-Pailit/2025.',
        ]);
        $successRes->assertRedirect();

        $this->assertEquals('Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', $temuan->fresh()->status_penyelesaian);
    }

    public function test_sensitive_files_require_authorization(): void
    {
        $pem = $this->createApprovedPemeriksaan('BPK');
        $lhp = $this->createLhp($pem);
        $temuan = $this->createTemuan($pem, $lhp);

        $path = Storage::disk('local')->put('tindak_lanjut', UploadedFile::fake()->create('bukti_setor_rahasia.pdf', 100, 'application/pdf'));

        $tl = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $lhp->id,
            'opd_id' => $this->opd->id,
            'tanggal_tindak_lanjut' => now(),
            'uraian_tindak_lanjut' => 'Bukti setor sensitif',
            'dokumen_bukti' => $path,
            'nama_dokumen_asli' => 'bukti_setor_rahasia.pdf',
            'created_by' => $this->adminBpk->id,
            'status_verifikasi' => 'SESUAI',
        ]);

        // Admin BPKP cannot download BPK sensitive document
        $unauthRes = $this->actingAs($this->adminBpkp)->get(route('temuan.tindak-lanjut.download', [$temuan, $tl]));
        $unauthRes->assertForbidden();

        // Admin BPK can download
        $authRes = $this->actingAs($this->adminBpk)->get(route('temuan.tindak-lanjut.download', [$temuan, $tl]));
        $authRes->assertOk();
    }
}
