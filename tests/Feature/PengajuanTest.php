<?php

namespace Tests\Feature;

use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminInsp;
    protected User $opdUser;
    protected Pemeriksaan $pemeriksaanBpk;
    protected Temuan $temuanBpk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
        $this->opdUser = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        $this->pemeriksaanBpk = Pemeriksaan::where('nomor_spt', '05/SPT/BPK-GOR/02/2025')->first();
        $this->temuanBpk = Temuan::where('sumber_pemeriksaan', 'BPK')->first();
    }

    public function test_admin_sumber_can_submit_inspection(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'DRAFT']);

        $response = $this->actingAs($this->adminBpk)->post(route('pemeriksaan.ajukan', $this->pemeriksaanBpk));

        $response->assertRedirect();
        $this->assertEquals('MENUNGGU PERSETUJUAN', $this->pemeriksaanBpk->fresh()->status_pengajuan);
    }

    public function test_admin_sumber_can_cancel_inspection_submission(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $response = $this->actingAs($this->adminBpk)->post(route('pemeriksaan.batal-ajukan', $this->pemeriksaanBpk));

        $response->assertRedirect();
        $this->assertEquals('DRAFT', $this->pemeriksaanBpk->fresh()->status_pengajuan);
    }

    public function test_super_admin_can_approve_inspection(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $response = $this->actingAs($this->superAdmin)->post(route('pengajuan.pemeriksaan.approve', $this->pemeriksaanBpk));

        $response->assertRedirect();
        $fresh = $this->pemeriksaanBpk->fresh();
        $this->assertEquals('DISETUJUI', $fresh->status_pengajuan);
        $this->assertEquals($this->superAdmin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_super_admin_can_request_revision_with_notes(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $response = $this->actingAs($this->superAdmin)->post(route('pengajuan.pemeriksaan.revise', $this->pemeriksaanBpk), [
            'catatan_revisi' => 'Mohon lampirkan dokumen SPT yang sudah ditandatangani basah.',
        ]);

        $response->assertRedirect();
        $fresh = $this->pemeriksaanBpk->fresh();
        $this->assertEquals('PERLU REVISI', $fresh->status_pengajuan);
        $this->assertEquals('Mohon lampirkan dokumen SPT yang sudah ditandatangani basah.', $fresh->catatan_persetujuan);
    }

    public function test_opd_cannot_approve_or_reject_submissions(): void
    {
        $this->pemeriksaanBpk->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $response = $this->actingAs($this->opdUser)->post(route('pengajuan.pemeriksaan.approve', $this->pemeriksaanBpk));
        $response->assertStatus(403);

        $responseRevise = $this->actingAs($this->opdUser)->post(route('pengajuan.pemeriksaan.revise', $this->pemeriksaanBpk), [
            'catatan_revisi' => 'Illegal attempt',
        ]);
        $responseRevise->assertStatus(403);
    }
}
