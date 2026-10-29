<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Temuan;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $pemantau;
    protected User $opdDinkes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
    }

    public function test_super_admin_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('audit-log.index'));

        $response->assertStatus(200);
        $response->assertSee('Audit Trail &amp; Log Aktivitas Sistem', false);
        $response->assertSee('TEMUAN');
    }

    public function test_pemantau_can_view_audit_logs(): void
    {
        $response = $this->actingAs($this->pemantau)->get(route('audit-log.index'));

        $response->assertStatus(200);
    }

    public function test_opd_cannot_view_audit_logs(): void
    {
        $response = $this->actingAs($this->opdDinkes)->get(route('audit-log.index'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_view_trash_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('audit-log.trash'));

        $response->assertStatus(200);
        $response->assertSee('Arsip &amp; Pemulihan Data', false);
    }

    public function test_super_admin_can_view_archived_pemeriksaan_detail(): void
    {
        $pemeriksaan = \App\Models\Pemeriksaan::first();
        $pemeriksaan->delete();

        $response = $this->actingAs($this->superAdmin)->get(route('audit-log.show-pemeriksaan', $pemeriksaan->id));

        $response->assertStatus(200);
        $response->assertSee($pemeriksaan->nomor_spt);
        $response->assertSee('Status: Dalam Arsip Sampah');
    }

    public function test_super_admin_can_restore_soft_deleted_pemeriksaan(): void
    {
        $pemeriksaan = \App\Models\Pemeriksaan::first();
        $id = $pemeriksaan->id;
        $pemeriksaan->delete();

        $this->assertSoftDeleted('pemeriksaans', ['id' => $id]);

        $response = $this->actingAs($this->superAdmin)->post(route('audit-log.restore', ['type' => 'pemeriksaan', 'id' => $id]));

        $response->assertRedirect(route('audit-log.trash'));
        $this->assertNotSoftDeleted('pemeriksaans', ['id' => $id]);
    }

    public function test_super_admin_can_force_delete_soft_deleted_pemeriksaan(): void
    {
        $pemeriksaan = \App\Models\Pemeriksaan::first();
        $id = $pemeriksaan->id;
        $pemeriksaan->delete();

        $response = $this->actingAs($this->superAdmin)->delete(route('audit-log.force-delete', ['type' => 'pemeriksaan', 'id' => $id]));

        $response->assertRedirect(route('audit-log.trash'));
        $this->assertDatabaseMissing('pemeriksaans', ['id' => $id]);
    }

    public function test_audit_logger_records_event_accurately(): void
    {
        $log = AuditLogger::log('CREATE', 'TEST_MODULE', 'Testing custom event logging.', null, null, $this->superAdmin);

        $this->assertNotNull($log);
        $this->assertEquals('CREATE', $log->action);
        $this->assertEquals('TEST_MODULE', $log->module);
        $this->assertEquals($this->superAdmin->id, $log->user_id);
        $this->assertDatabaseHas('audit_logs', ['description' => 'Testing custom event logging.']);
    }
}
