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

class TindakLanjutWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminBpkp;
    protected User $adminItjen;
    protected User $adminInspektorat;
    protected User $pemantau;
    protected User $opdUser;
    protected Opd $opdDinkes;
    protected Opd $opdDikbud;

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

        $this->opdDinkes = Opd::create(['kode' => 'DINKES', 'nama' => 'Dinas Kesehatan', 'singkatan' => 'Dinkes', 'is_active' => true]);
        $this->opdDikbud = Opd::create(['kode' => 'DIKBUD', 'nama' => 'Dinas Pendidikan dan Kebudayaan', 'singkatan' => 'Dikbud', 'is_active' => true]);

        $this->superAdmin = User::factory()->create(['role_id' => $roleSuper->id, 'name' => 'Super Admin']);
        $this->adminBpk = User::factory()->create(['role_id' => $roleBpk->id, 'name' => 'Admin BPK']);
        $this->adminBpkp = User::factory()->create(['role_id' => $roleBpkp->id, 'name' => 'Admin BPKP']);
        $this->adminItjen = User::factory()->create(['role_id' => $roleItjen->id, 'name' => 'Admin ITJEN']);
        $this->adminInspektorat = User::factory()->create(['role_id' => $roleInsp->id, 'name' => 'Admin Inspektorat']);
        $this->pemantau = User::factory()->create(['role_id' => $rolePemantau->id, 'name' => 'Pemantau']);
        $this->opdUser = User::factory()->create(['role_id' => $roleOpd->id, 'opd_id' => $this->opdDinkes->id, 'name' => 'Petugas Dinkes']);
    }

    private function createApprovedData(string $sumber, bool $isKerugian = true, float $nilaiKerugian = 50000000): array
    {
        $pem = Pemeriksaan::create([
            'sumber_pemeriksaan' => $sumber,
            'opd_id' => $this->opdDinkes->id,
            'nomor_spt' => 'SPT/' . $sumber . '/2026/001',
            'tanggal_spt' => now(),
            'tahun' => 2026,
            'status_pengajuan' => 'DISETUJUI',
            'created_by' => $this->superAdmin->id,
        ]);

        $lhp = Lhp::create([
            'pemeriksaan_id' => $pem->id,
            'nomor_lhp' => 'LHP/' . $sumber . '/2026/001',
            'tanggal_lhp' => now(),
            'judul_lhp' => 'LHP ' . $sumber . ' 2026',
            'created_by' => $this->superAdmin->id,
        ]);

        $temuan = Temuan::create([
            'pemeriksaan_id' => $pem->id,
            'lhp_id' => $lhp->id,
            'sumber_pemeriksaan' => $sumber,
                'opd_id' => $this->opdDinkes->id,
                'tahun' => 2026,
                'judul_temuan' => $isKerugian ? 'Temuan Kelebihan Bayar Pengadaan Alkes' : 'Temuan Administrasi SOP Pengadaan',
            'uraian_temuan' => $isKerugian ? 'Ditemukan selisih harga' : 'SOP belum ditandatangani',
            'rekomendasi' => $isKerugian ? 'Menyetorkan ke kas daerah' : 'Menerbitkan SOP baru',
            'ada_unsur_administrasi' => !$isKerugian,
            'ada_unsur_kerugian' => $isKerugian,
            'nilai_kerugian' => $isKerugian ? $nilaiKerugian : 0,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->superAdmin->id,
        ]);

        return [$pem, $lhp, $temuan];
    }

    /** 1. Admin Sumber can submit TL directly bound to Temuan */
    public function test_admin_sumber_can_submit_tindak_lanjut_bound_to_temuan(): void
    {
        [$pem, $lhp, $temuan] = $this->createApprovedData('BPK', true, 20000000);

        $file = UploadedFile::fake()->create('sts_bpk.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-20',
            'uraian_tindak_lanjut' => 'Penyetoran tahap 1 sebesar Rp 10.000.000',
            'jumlah_setor' => '10.000.000',
            'nomor_bukti_setor' => 'STS/2026/08/01',
            'tanggal_setor' => '2026-08-20',
            'dokumen_bukti' => $file,
        ]);

        $response->assertRedirect();
        $tl = TindakLanjut::where('nomor_bukti_setor', 'STS/2026/08/01')->first();
        $this->assertNotNull($tl);
        $this->assertEquals($temuan->id, $tl->temuan_id);
        $this->assertEquals($lhp->id, $tl->lhp_id);
        $this->assertEquals($this->opdDinkes->id, $tl->opd_id);
        $this->assertEquals('MENUNGGU VERIFIKASI', $tl->status_verifikasi);
        $this->assertEquals(10000000, $tl->jumlah_setor);
        $this->assertEquals('Menunggu Verifikasi', $temuan->fresh()->status_penyelesaian);
    }

    /** 2. Unsur Administrasi handles zero payment correctly */
    public function test_administrasi_finding_stores_tl_with_zero_payment(): void
    {
        [$pem, $lhp, $temuan] = $this->createApprovedData('BPKP', false, 0);

        $file = UploadedFile::fake()->create('sop_baru.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->adminBpkp)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-22',
            'uraian_tindak_lanjut' => 'SOP Baru telah diterbitkan dan ditandatangani',
            'dokumen_bukti' => $file,
        ]);

        $response->assertRedirect();
        $tl = $temuan->tindakLanjuts()->first();
        $this->assertNotNull($tl);
        $this->assertEquals(0, $tl->jumlah_setor);
        $this->assertEquals('MENUNGGU VERIFIKASI', $tl->status_verifikasi);
        $this->assertEquals('Menunggu Verifikasi', $temuan->fresh()->status_penyelesaian);
    }

    /** 3. OPD can submit TL for own findings, but forbidden for other OPDs, and Pemantau is strictly forbidden */
    public function test_opd_can_submit_tindak_lanjut_and_pemantau_is_forbidden(): void
    {
        [$pem, $lhp, $temuan] = $this->createApprovedData('BPK', true, 10000000);

        $fOpd = UploadedFile::fake()->create('sts_opd.pdf', 200, 'application/pdf');
        $resOpd = $this->actingAs($this->opdUser)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-20',
            'uraian_tindak_lanjut' => 'OPD Dinkes menyampaikan bukti TL resmi',
            'jumlah_setor' => '10.000.000',
            'nomor_bukti_setor' => 'STS-DINKES-WF-01',
            'dokumen_bukti' => $fOpd,
        ]);
        $resOpd->assertRedirect();
        $this->assertEquals('Menunggu Verifikasi', $temuan->fresh()->status_penyelesaian);

        // Other OPD attempting to submit for Dinkes finding
        $otherOpdUser = User::factory()->create(['role_id' => $this->opdUser->role_id, 'opd_id' => $this->opdDikbud->id]);
        $resOtherOpd = $this->actingAs($otherOpdUser)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-20',
            'uraian_tindak_lanjut' => 'Other OPD attempting to submit TL',
        ]);
        $resOtherOpd->assertForbidden();

        // Pemantau attempting to submit
        $resPemantau = $this->actingAs($this->pemantau)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-20',
            'uraian_tindak_lanjut' => 'Pemantau attempting to submit TL',
        ]);
        $resPemantau->assertForbidden();
    }

    /** 4. Cross-source attempt is strictly forbidden */
    public function test_admin_cannot_submit_tl_for_different_source(): void
    {
        [$pem, $lhp, $temuanBpk] = $this->createApprovedData('BPK', true, 10000000);

        // Admin BPKP attempts to submit for BPK finding
        $response = $this->actingAs($this->adminBpkp)->post(route('temuan.tindak-lanjut.store', $temuanBpk), [
            'tanggal_tindak_lanjut' => '2026-08-20',
            'uraian_tindak_lanjut' => 'Cross source bypass attempt',
        ]);
        $response->assertForbidden();
    }

    /** 5. Super Admin verification with partial vs full payment */
    public function test_super_admin_verification_lifecycle(): void
    {
        [$pem, $lhp, $temuan] = $this->createApprovedData('ITJEN', true, 30000000);

        // Submit TL 1: 10jt
        $fItjen1 = UploadedFile::fake()->create('sts_itjen_1.pdf', 200, 'application/pdf');
        $this->actingAs($this->adminItjen)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-01',
            'uraian_tindak_lanjut' => 'Cicilan 1: Rp 10.000.000',
            'jumlah_setor' => '10.000.000',
            'nomor_bukti_setor' => 'STS-ITJEN-01',
            'dokumen_bukti' => $fItjen1,
        ]);
        $tl1 = TindakLanjut::where('nomor_bukti_setor', 'STS-ITJEN-01')->first();

        // Super Admin verifies TL 1 as SESUAI
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl1]), [
            'status_verifikasi' => 'SESUAI',
            'catatan_verifikasi' => 'Setoran 10jt valid.',
        ]);

        // Temuan status remains Belum Sesuai because 10jt < 30jt
        $this->assertEquals('Belum Sesuai', $temuan->fresh()->status_penyelesaian);
        $this->assertEquals(10000000, $temuan->fresh()->total_disetor);
        $this->assertEquals(20000000, $temuan->fresh()->sisa_kerugian);

        // Submit TL 2: 20jt (Pelunasan)
        $fItjen2 = UploadedFile::fake()->create('sts_itjen_2.pdf', 200, 'application/pdf');
        $this->actingAs($this->adminItjen)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'tanggal_tindak_lanjut' => '2026-08-15',
            'uraian_tindak_lanjut' => 'Pelunasan sisa Rp 20.000.000',
            'jumlah_setor' => '20.000.000',
            'nomor_bukti_setor' => 'STS-ITJEN-02',
            'dokumen_bukti' => $fItjen2,
        ]);
        $tl2 = TindakLanjut::where('nomor_bukti_setor', 'STS-ITJEN-02')->first();

        // Super Admin verifies TL 2 as SESUAI
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl2]), [
            'status_verifikasi' => 'SESUAI',
            'catatan_verifikasi' => 'Pelunasan lengkap.',
        ]);

        // Temuan is now Sesuai (100%)
        $this->assertEquals('Sesuai', $temuan->fresh()->status_penyelesaian);
        $this->assertEquals(30000000, $temuan->fresh()->total_disetor);
        $this->assertEquals(0, $temuan->fresh()->sisa_kerugian);
        $this->assertEquals(100, $temuan->fresh()->progress_persen);
    }

    /** 6. Super Admin verification for Tidak Dapat Ditindaklanjuti requires mandatory reason */
    public function test_super_admin_verification_tidak_dapat_ditindaklanjuti_validation(): void
    {
        [$pem, $lhp, $temuan] = $this->createApprovedData('Inspektorat', false, 0);

        $tl = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $lhp->id,
            'opd_id' => $this->opdDinkes->id,
            'tanggal_tindak_lanjut' => now(),
            'uraian_tindak_lanjut' => 'Kondisi force majeure',
            'created_by' => $this->adminInspektorat->id,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        // Validation error if alasan_khusus is blank
        $fail = $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl]), [
            'status_verifikasi' => 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH',
            'alasan_khusus' => '',
        ]);
        $fail->assertSessionHasErrors('alasan_khusus');

        // Success if reason provided
        $file = UploadedFile::fake()->create('surat_keputusan.pdf', 100, 'application/pdf');
        $success = $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl]), [
            'status_verifikasi' => 'TIDAK DAPAT DITINDAKLANJUTI DENGAN ALASAN YANG SAH',
            'alasan_khusus' => 'Berdasarkan SK Penghapusan Piutang Daerah No. 123/2026 yang sah.',
            'dokumen_pendukung_alasan' => $file,
        ]);
        $success->assertRedirect();

        $fresh = $temuan->fresh();
        $this->assertEquals('Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah', $fresh->status_penyelesaian);
        $this->assertStringContainsString('123/2026', $fresh->alasan_khusus);
        Storage::disk('local')->assertExists($fresh->dokumen_pendukung_alasan);
    }

    /** 7. Non-super admin cannot perform verification */
    public function test_non_super_admin_cannot_verify(): void
    {
        [$pem, $lhp, $temuan] = $this->createApprovedData('BPK', true, 10000000);

        $tl = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $lhp->id,
            'opd_id' => $this->opdDinkes->id,
            'tanggal_tindak_lanjut' => now(),
            'uraian_tindak_lanjut' => 'Setoran 10jt',
            'jumlah_setor' => 10000000,
            'created_by' => $this->adminBpk->id,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        $resAdminBpk = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $resAdminBpk->assertForbidden();

        $resOpd = $this->actingAs($this->opdUser)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $resOpd->assertForbidden();

        $resPemantau = $this->actingAs($this->pemantau)->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tl]), [
            'status_verifikasi' => 'SESUAI',
        ]);
        $resPemantau->assertForbidden();
    }

    /** 8. Monitoring index page filters correctly */
    public function test_monitoring_index_filtering(): void
    {
        [$pem, $lhp, $temuan] = $this->createApprovedData('BPK', true, 10000000);

        TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $lhp->id,
            'opd_id' => $this->opdDinkes->id,
            'tanggal_tindak_lanjut' => now(),
            'uraian_tindak_lanjut' => 'Tindak lanjut pengujian filter',
            'created_by' => $this->adminBpk->id,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('tindak-lanjut.index', [
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'sumber' => 'BPK',
        ]));

        $response->assertOk();
        $response->assertSee('Tindak lanjut pengujian filter');
    }

    /** 9. Temuan created under approved SPT/LHP is directly active for Tindak Lanjut without separate pengajuan */
    public function test_temuan_under_approved_spt_lhp_is_ready_for_tindak_lanjut_without_separate_pengajuan(): void
    {
        $pem = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->opdDinkes->id,
            'nomor_spt' => 'SPT/BPK/TEST/001',
            'tanggal_spt' => now(),
            'tahun' => 2026,
            'status_pengajuan' => 'DISETUJUI',
            'created_by' => $this->superAdmin->id,
        ]);

        $lhp = Lhp::create([
            'pemeriksaan_id' => $pem->id,
            'nomor_lhp' => 'LHP/BPK/TEST/001',
            'tanggal_lhp' => now(),
            'judul_lhp' => 'LHP BPK Test',
            'created_by' => $this->superAdmin->id,
        ]);

        // 1. Admin BPK creates Temuan under this approved Pemeriksaan
        $createRes = $this->actingAs($this->adminBpk)->post(route('temuan.store'), [
            'pemeriksaan_id' => $pem->id,
            'lhp_id' => $lhp->id,
            'judul_temuan' => 'Temuan Langsung Aktif',
            'uraian_temuan' => 'Uraian temuan langsung aktif',
            'rekomendasi' => 'Rekomendasi tindak lanjut',
            'ada_unsur_administrasi' => '1',
            'ada_unsur_kerugian' => '0',
        ]);
        $createRes->assertRedirect();

        $newTemuan = Temuan::where('judul_temuan', 'Temuan Langsung Aktif')->first();
        $this->assertNotNull($newTemuan);
        // Temuan is directly DISETUJUI, no separate pengajuan required!
        $this->assertEquals('DISETUJUI', $newTemuan->status_pengajuan);

        // 2. Admin BPK immediately inputs Tindak Lanjut for this Temuan (via butir rekomendasi)
        $rek = $newTemuan->rekomendasis()->first();
        $fBpk = UploadedFile::fake()->create('bukti_langsung.pdf', 100, 'application/pdf');
        $tlRes = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $newTemuan), [
            'rekomendasi_id' => $rek?->id,
            'tanggal_tindak_lanjut' => '2026-08-29',
            'uraian_tindak_lanjut' => 'Tindak lanjut langsung berhasil diinput',
            'dokumen_bukti' => $fBpk,
        ]);
        $tlRes->assertRedirect();

        $this->assertDatabaseHas('tindak_lanjuts', [
            'temuan_id' => $newTemuan->id,
            'uraian_tindak_lanjut' => 'Tindak lanjut langsung berhasil diinput',
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
        ]);
    }

    /** 10. Validasi sinkronisasi status temuan gabungan (Administrasi + Kerugian) */
    public function test_temuan_gabungan_status_sinkronisasi(): void
    {
        $pem = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->opdDinkes->id,
            'nomor_spt' => 'SPT/BPK/TEST/GABUNGAN',
            'tanggal_spt' => now(),
            'tahun' => 2026,
            'status_pengajuan' => 'DISETUJUI',
            'created_by' => $this->superAdmin->id,
        ]);

        $lhp = Lhp::create([
            'pemeriksaan_id' => $pem->id,
            'nomor_lhp' => 'LHP/BPK/TEST/GABUNGAN',
            'tanggal_lhp' => now(),
            'judul_lhp' => 'LHP Gabungan',
            'created_by' => $this->superAdmin->id,
        ]);

        $temuan = Temuan::create([
            'pemeriksaan_id' => $pem->id,
            'lhp_id' => $lhp->id,
            'sumber_pemeriksaan' => 'BPK',
                'opd_id' => $this->opdDinkes->id,
                'tahun' => 2026,
                'judul_temuan' => 'Temuan Gabungan Administrasi dan Kerugian',
            'uraian_temuan' => 'Ditemukan selisih harga dan SOP belum dibuat',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 50000000,
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->superAdmin->id,
        ]);

        // Temuan must be created through controller to auto-generate rekomendasi or we just create manually
        $rekomendasiAdm = $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Membuat SOP pengadaan yang baru',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $rekomendasiKerugian = $temuan->rekomendasis()->create([
            'uraian_rekomendasi' => 'Menyetorkan kelebihan bayar sebesar Rp 50.000.000 ke Kas Daerah',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // 1. Submit TL for Administrasi
        $fAdm = UploadedFile::fake()->create('sop_baru.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'rekomendasi_id' => $rekomendasiAdm->id,
            'tanggal_tindak_lanjut' => '2026-08-20',
            'uraian_tindak_lanjut' => 'SOP baru telah dibuat',
            'dokumen_bukti' => $fAdm,
        ]);
        $response->assertRedirect();

        $tlAdm = TindakLanjut::where('rekomendasi_id', $rekomendasiAdm->id)->first();
        $this->assertNotNull($tlAdm, 'TindakLanjut administrasi harus berhasil dibuat');
        
        // Verifikasi TL Administrasi sebagai SESUAI
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', ['temuan' => $temuan->id, 'tindakLanjut' => $tlAdm->id]), [
            'status_verifikasi' => 'SESUAI',
        ]);

        // Status keseluruhan masih Belum Sesuai karena kerugian (50jt) belum dibayar
        $this->assertEquals('Sesuai', $rekomendasiAdm->fresh()->status_penyelesaian);
        $this->assertEquals('Belum Sesuai', $temuan->fresh()->status_penyelesaian);

        // 2. Submit TL for Kerugian (Lunas 50jt)
        $fKerugian = UploadedFile::fake()->create('sts_lunas.pdf', 100, 'application/pdf');
        $response2 = $this->actingAs($this->adminBpk)->post(route('temuan.tindak-lanjut.store', $temuan), [
            'rekomendasi_id' => $rekomendasiKerugian->id,
            'tanggal_tindak_lanjut' => '2026-08-21',
            'uraian_tindak_lanjut' => 'Penyetoran lunas kerugian 50 juta',
            'jumlah_setor' => '50.000.000',
            'nomor_bukti_setor' => 'STS-LUNAS-01',
            'tanggal_setor' => '2026-08-21',
            'dokumen_bukti' => $fKerugian,
        ]);
        $response2->assertRedirect();

        $tlKerugian = TindakLanjut::where('nomor_bukti_setor', 'STS-LUNAS-01')->first();
        $this->assertNotNull($tlKerugian, 'TindakLanjut kerugian harus berhasil dibuat');
        
        // Verifikasi TL Kerugian sebagai SESUAI
        $this->actingAs($this->superAdmin)->post(route('temuan.tindak-lanjut.verifikasi', ['temuan' => $temuan->id, 'tindakLanjut' => $tlKerugian->id]), [
            'status_verifikasi' => 'SESUAI',
        ]);

        // Status keseluruhan sekarang menjadi SESUAI
        $this->assertEquals('Sesuai', $temuan->fresh()->status_penyelesaian);
        $this->assertEquals(50000000, $temuan->fresh()->total_disetor);
        $this->assertEquals(0, $temuan->fresh()->sisa_kerugian);
        $this->assertEquals(100, $temuan->fresh()->progress_persen);
    }
}
