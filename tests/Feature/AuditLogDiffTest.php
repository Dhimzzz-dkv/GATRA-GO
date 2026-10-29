<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Role;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogDiffTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $pemeriksaan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $superAdminRole = Role::where('name', 'super_admin')->firstOrFail();
        $opd = Opd::firstOrFail();

        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        $this->pemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $opd->id,
            'tahun' => date('Y'),
            'nomor_spt' => 'SPT/TEST/001',
            'judul' => 'Pemeriksaan Test',
            'status_pengajuan' => 'DISETUJUI',
            'created_by' => $this->superAdmin->id,
        ]);
        $this->opd = $opd;
    }

    public function test_temuan_update_generates_diff_in_audit_log()
    {
        // 1. Create Temuan (kerugian murni, tanpa administrasi)
        $temuan = Temuan::create([
            'pemeriksaan_id' => $this->pemeriksaan->id,
            'opd_id' => $this->opd->id,
            'judul_temuan' => 'Temuan Awal',
            'uraian_temuan' => 'Uraian Awal',
            'nilai_kerugian' => 1000000,
            'ada_unsur_kerugian' => true,
            'ada_unsur_administrasi' => false,
            'kategori_kerugian' => 'Kerugian Daerah',
            'jenis_kerugian' => 'Internal',
            'status_pengajuan' => 'DISETUJUI',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => date('Y'),
            'created_by' => $this->superAdmin->id,
        ]);

        // 2. Perform Update via Route (update kerugian saja)
        $response = $this->actingAs($this->superAdmin)->put(route('temuan.update', $temuan), [
            'judul_temuan' => 'Temuan Diperbarui',
            'uraian_temuan' => 'Uraian Diperbarui',
            'nilai_kerugian' => '500.000', // controller parses this
            'ada_unsur_kerugian' => '1',
            'kategori_kerugian' => 'Kerugian Daerah',
            'jenis_kerugian' => 'Internal',
        ]);
        $response->assertRedirect();

        // 3. Assert Audit Log was created
        $log = AuditLog::where('module', 'TEMUAN')
            ->where('action', 'UPDATE')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($log, 'Audit log update temuan harus tercatat.');
        
        // 4. Assert Diff contains before and after values
        $this->assertIsArray($log->old_values, 'old_values harus berupa array/JSON.');
        $this->assertIsArray($log->new_values, 'new_values harus berupa array/JSON.');

        $this->assertEquals('Temuan Awal', $log->old_values['judul_temuan']);
        $this->assertEquals('Temuan Diperbarui', $log->new_values['judul_temuan']);
        
        $this->assertEquals(1000000, $log->old_values['nilai_kerugian']);
        $this->assertEquals(500000, $log->new_values['nilai_kerugian']);
    }

    public function test_pemeriksaan_submission_generates_diff_in_audit_log()
    {
        $draft = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPKP',
            'opd_id' => $this->opd->id,
            'status_pengajuan' => 'DRAFT',
            'nomor_spt' => 'SPT/DRAFT/001',
            'tahun' => date('Y'),
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('pemeriksaan.ajukan', $draft));
        $response->assertRedirect();

        $log = AuditLog::where('module', 'PEMERIKSAAN')
            ->where('action', 'SUBMIT')
            ->orderBy('id', 'desc')
            ->first();

        $this->assertNotNull($log, 'Audit log SUBMIT pemeriksaan harus tercatat.');
        $this->assertIsArray($log->old_values, 'old_values harus berupa array.');
        $this->assertIsArray($log->new_values, 'new_values harus berupa array.');
        $this->assertArrayHasKey('status_pengajuan', $log->old_values);
        $this->assertArrayHasKey('status_pengajuan', $log->new_values);
        $this->assertEquals('DRAFT', $log->old_values['status_pengajuan']);
        $this->assertEquals('MENUNGGU PERSETUJUAN', $log->new_values['status_pengajuan']);
    }

    public function test_audit_log_ui_accessible_and_renders_without_error()
    {
        // Generate a sample log with diff
        $draft = clone $this->pemeriksaan;
        $draft->status_pengajuan = 'DRAFT';
        $draft->save();

        \App\Services\AuditLogger::logModelChange(
            'UPDATE',
            $draft,
            'TEST',
            'Testing Diff',
            ['status_pengajuan' => 'DISETUJUI'] // old
        );

        $response = $this->actingAs($this->superAdmin)->get(route('audit-log.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Rincian Perubahan');
        $response->assertSee('Nilai Sebelum');
        $response->assertSee('Nilai Sesudah');
    }
}
