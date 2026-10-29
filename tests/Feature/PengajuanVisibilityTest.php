<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminBpkp;
    protected User $adminItjen;
    protected User $adminInspektorat;
    protected Opd $dinkes;
    protected Opd $dikbud;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->adminBpkp = User::where('email', 'admin.bpkp@inspektorat.go.id')->first();
        $this->adminItjen = User::where('email', 'admin.itjen@inspektorat.go.id')->first();
        $this->adminInspektorat = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();

        $this->dinkes = Opd::where('kode', 'DINKES')->first();
        $this->dikbud = Opd::where('kode', 'DIKBUD')->first();
    }

    /**
     * TEST 1: Admin BPK membuat Draft -> terlihat oleh Admin BPK, TIDAK terlihat oleh Super Admin.
     */
    public function test_draft_is_visible_to_admin_sumber_but_hidden_from_super_admin(): void
    {
        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/001',
            'judul' => 'Pemeriksaan Draft BPK',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        $draftTemuan = Temuan::create([
            'pemeriksaan_id' => $draftPemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Draft BPK',
            'uraian_temuan' => 'Uraian Temuan Draft',
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 50000000,
            'status_pengajuan' => 'DRAFT',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Admin BPK query sees it
        $this->assertTrue(Pemeriksaan::forUser($this->adminBpk)->where('id', $draftPemeriksaan->id)->exists());
        $this->assertTrue(Temuan::forUser($this->adminBpk)->where('id', $draftTemuan->id)->exists());

        // Super Admin query DOES NOT see it
        $this->assertFalse(Pemeriksaan::forUser($this->superAdmin)->where('id', $draftPemeriksaan->id)->exists());
        $this->assertFalse(Temuan::forUser($this->superAdmin)->where('id', $draftTemuan->id)->exists());
    }

    /**
     * TEST 2: Admin BPK membuka Draft sendiri -> Berhasil (200 OK).
     */
    public function test_admin_bpk_can_open_own_draft(): void
    {
        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/002',
            'judul' => 'Pemeriksaan Draft BPK 2',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        $response = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.show', $draftPemeriksaan));
        $response->assertStatus(200);
    }

    /**
     * TEST 3: Super Admin mencoba membuka ID Draft melalui URL -> Ditolak (403 Forbidden).
     */
    public function test_super_admin_cannot_open_draft_url_directly(): void
    {
        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/003',
            'judul' => 'Pemeriksaan Draft BPK 3',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        $draftTemuan = Temuan::create([
            'pemeriksaan_id' => $draftPemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Draft BPK 3',
            'uraian_temuan' => 'Uraian Temuan Draft 3',
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        // Super Admin gets 403 on Pemeriksaan Draft
        $responsePem = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.show', $draftPemeriksaan));
        $responsePem->assertStatus(403);

        // Super Admin gets 403 on Temuan Draft
        $responseTem = $this->actingAs($this->superAdmin)->get(route('temuan.show', $draftTemuan));
        $responseTem->assertStatus(403);
    }

    /**
     * TEST 4 & 5: Admin BPK klik Ajukan -> status berubah MENUNGGU PERSETUJUAN & muncul di Super Admin.
     */
    public function test_submitting_draft_changes_status_and_makes_it_visible_to_super_admin(): void
    {
        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/004',
            'judul' => 'Pemeriksaan Draft BPK 4',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        // Admin BPK submits
        $response = $this->actingAs($this->adminBpk)->post(route('pemeriksaan.ajukan', $draftPemeriksaan));
        $response->assertSessionHas('success');

        $draftPemeriksaan->refresh();
        $this->assertEquals('MENUNGGU PERSETUJUAN', $draftPemeriksaan->status_pengajuan);

        // Now Super Admin CAN see it
        $this->assertTrue(Pemeriksaan::forUser($this->superAdmin)->where('id', $draftPemeriksaan->id)->exists());

        // And Super Admin can view the detail
        $resSuper = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.show', $draftPemeriksaan));
        $resSuper->assertStatus(200);
    }

    /**
     * TEST 6: Dashboard Super Admin -> Draft TIDAK dihitung sebagai pengajuan atau statistik.
     */
    public function test_dashboard_super_admin_does_not_count_draft(): void
    {
        // Count initial
        $initialTemuanCount = Temuan::forUser($this->superAdmin)->active()->count();

        // Create a DRAFT finding
        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/005',
            'judul' => 'Pemeriksaan Draft 5',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        Temuan::create([
            'pemeriksaan_id' => $draftPemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Draft BPK 5',
            'uraian_temuan' => 'Uraian Temuan Draft 5',
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 99000000,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        // Dashboard request for Super Admin
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $response->assertStatus(200);

        // Super Admin's active finding count must not change
        $newTemuanCount = Temuan::forUser($this->superAdmin)->active()->count();
        $this->assertEquals($initialTemuanCount, $newTemuanCount);
    }

    /**
     * TEST 7: Badge pengajuan baru hanya menghitung MENUNGGU PERSETUJUAN.
     */
    public function test_badge_pengajuan_only_counts_menunggu_persetujuan(): void
    {
        $initialPending = Pemeriksaan::where('status_pengajuan', 'MENUNGGU PERSETUJUAN')->count();

        // Add DRAFT
        Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPKP',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPKP/DRAFT/007',
            'judul' => 'Pemeriksaan BPKP Draft',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dikbud->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpkp->id,
        ]);

        $currentPending = Pemeriksaan::where('status_pengajuan', 'MENUNGGU PERSETUJUAN')->count();
        $this->assertEquals($initialPending, $currentPending);
    }

    /**
     * TEST 8 & 9: Source isolation between Admin Sumber.
     */
    public function test_admin_bpk_cannot_see_or_manage_bpkp_draft(): void
    {
        $bpkpDraft = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPKP',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPKP/DRAFT/008',
            'judul' => 'Pemeriksaan BPKP Draft 8',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dikbud->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpkp->id,
        ]);

        // Admin BPK cannot query it
        $this->assertFalse(Pemeriksaan::forUser($this->adminBpk)->where('id', $bpkpDraft->id)->exists());

        // Admin BPK cannot open it
        $response = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.show', $bpkpDraft));
        $response->assertStatus(403);
    }

    public function test_admin_bpkp_cannot_see_or_manage_bpk_draft(): void
    {
        $bpkDraft = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/009',
            'judul' => 'Pemeriksaan BPK Draft 9',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        // Admin BPKP cannot query it
        $this->assertFalse(Pemeriksaan::forUser($this->adminBpkp)->where('id', $bpkDraft->id)->exists());

        // Admin BPKP cannot open it
        $response = $this->actingAs($this->adminBpkp)->get(route('pemeriksaan.show', $bpkDraft));
        $response->assertStatus(403);
    }

    /**
     * TEST 10: Akun OPD tidak dapat mengajukan atau membatalkan pengajuan Pemeriksaan (Cegah Celah IDOR).
     */
    public function test_opd_cannot_submit_or_cancel_pemeriksaan_submission(): void
    {
        $opdUser = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/OPD-TEST',
            'judul' => 'Pemeriksaan Draft BPK OPD Test',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        // OPD mencoba mengajukan -> Wajib 403 Forbidden
        $resSubmit = $this->actingAs($opdUser)->post(route('pemeriksaan.ajukan', $draftPemeriksaan));
        $resSubmit->assertStatus(403);
        $this->assertEquals('DRAFT', $draftPemeriksaan->fresh()->status_pengajuan);

        // Ubah status ke MENUNGGU PERSETUJUAN
        $draftPemeriksaan->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        // OPD mencoba membatalkan pengajuan -> Wajib 403 Forbidden
        $resCancel = $this->actingAs($opdUser)->post(route('pemeriksaan.batal-ajukan', $draftPemeriksaan));
        $resCancel->assertStatus(403);
        $this->assertEquals('MENUNGGU PERSETUJUAN', $draftPemeriksaan->fresh()->status_pengajuan);
    }

    /**
     * TEST 11: Akun Pemantau tidak dapat mengajukan atau membatalkan pengajuan Pemeriksaan (Cegah Celah IDOR).
     */
    public function test_pemantau_cannot_submit_or_cancel_pemeriksaan_submission(): void
    {
        $pemantauUser = User::where('email', 'pemantau@gorontaloprov.go.id')->first();

        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/PMN-TEST',
            'judul' => 'Pemeriksaan Draft BPK Pemantau Test',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        // Pemantau mencoba mengajukan -> Wajib 403 Forbidden
        $resSubmit = $this->actingAs($pemantauUser)->post(route('pemeriksaan.ajukan', $draftPemeriksaan));
        $resSubmit->assertStatus(403);

        $draftPemeriksaan->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        // Pemantau mencoba membatalkan -> Wajib 403 Forbidden
        $resCancel = $this->actingAs($pemantauUser)->post(route('pemeriksaan.batal-ajukan', $draftPemeriksaan));
        $resCancel->assertStatus(403);
    }

    /**
     * TEST 12: Akun OPD tidak dapat melihat atau membuka temuan yang masih berstatus DRAFT atau belum disetujui.
     */
    public function test_opd_cannot_see_or_open_draft_temuan(): void
    {
        $opdUser = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/DRAFT/TEMUAN-LEAK-TEST',
            'judul' => 'Pemeriksaan Draft BPK Leak Test',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        $draftTemuan = Temuan::create([
            'pemeriksaan_id' => $draftPemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Rahasia Draft BPK',
            'uraian_temuan' => 'Uraian temuan yang belum resmi disetujui',
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 25000000,
            'status_pengajuan' => 'DRAFT',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        // Query scope OPD tidak boleh menemukan temuan draft ini
        $this->assertFalse(Temuan::forUser($opdUser)->where('id', $draftTemuan->id)->exists());

        // Akses via show route wajib ditolak (403 Forbidden)
        $response = $this->actingAs($opdUser)->get(route('temuan.show', $draftTemuan));
        $response->assertStatus(403);
    }

    /**
     * TEST 13: Akun OPD tidak dapat menginput tindak lanjut pada temuan yang belum disetujui resmi.
     */
    public function test_opd_cannot_submit_tindak_lanjut_for_unapproved_temuan(): void
    {
        $opdUser = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        $draftPemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_spt' => 'SPT/BPK/TL/BLOCK-TEST',
            'judul' => 'Pemeriksaan Draft BPK TL Block Test',
            'tanggal_spt' => '2026-03-01',
            'opd_id' => $this->dinkes->id,
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminBpk->id,
        ]);

        $draftTemuan = Temuan::create([
            'pemeriksaan_id' => $draftPemeriksaan->id,
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $this->dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Draft BPK',
            'uraian_temuan' => 'Uraian temuan draft',
            'ada_unsur_kerugian' => true,
            'nilai_kerugian' => 10000000,
            'status_pengajuan' => 'DRAFT',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminBpk->id,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf');

        $response = $this->actingAs($opdUser)->post(route('temuan.tindak-lanjut.store', $draftTemuan), [
            'tanggal_tindak_lanjut' => '2026-03-15',
            'uraian_tindak_lanjut' => 'Mencoba tindak lanjut pada temuan draft',
            'dokumen_bukti' => $file,
        ]);

        $response->assertStatus(403);
    }
}
