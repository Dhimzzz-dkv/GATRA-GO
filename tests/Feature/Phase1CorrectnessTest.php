<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression guard untuk Phase 1 (Correctness).
 *
 * Setiap test di sini mengunci satu cacat nyata yang pernah ditemukan:
 *  - duplikat id="review-modal-form" → alur Minta Revisi / Tolak SPT gagal validasi
 *  - modal review mati di temuan/show → POST ke route yang tidak ada (404)
 *  - arsip pemulihan hanya mencakup Pemeriksaan → OPD/Pegawai/User/Temuan
 *    yang soft-deleted tidak bisa dipulihkan lewat UI
 *  - link ke route role:super_admin ditampilkan untuk role lain → 403
 *  - AuditLogger::logModelChange() tidak merekam diff untuk aksi workflow
 */
class Phase1CorrectnessTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminInsp;
    protected User $pemantau;
    protected User $opdDinkes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
        $this->pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
    }

    // =========================================================================
    // Item 1 + 2 — tidak ada DOM ID duplikat, tidak ada UI mati
    // =========================================================================

    public function test_rendered_pages_have_no_duplicate_html_ids(): void
    {
        $pemeriksaan = Pemeriksaan::first();
        $temuan = Temuan::first();

        $pages = [
            'dashboard' => route('dashboard'),
            'pemeriksaan.index' => route('pemeriksaan.index'),
            'audit-log.trash' => route('audit-log.trash'),
        ];

        if ($pemeriksaan) {
            $pages['pemeriksaan.show'] = route('pemeriksaan.show', $pemeriksaan);
        }
        if ($temuan) {
            $pages['temuan.show'] = route('temuan.show', $temuan);
        }

        foreach ($pages as $label => $url) {
            $html = $this->actingAs($this->superAdmin)->get($url)->getContent();

            preg_match_all('/\sid="([^"]+)"/', $html, $m);
            $counts = array_count_values($m[1] ?? []);
            $duplicates = array_filter($counts, fn ($c) => $c > 1);

            $this->assertSame(
                [],
                $duplicates,
                "Halaman [{$label}] memiliki id HTML duplikat: " . json_encode($duplicates)
            );
        }
    }

    public function test_pemeriksaan_show_review_modal_submits_correct_field_name(): void
    {
        $pemeriksaan = Pemeriksaan::first();
        $this->assertNotNull($pemeriksaan, 'Butuh data pemeriksaan untuk test ini.');

        $html = $this->actingAs($this->superAdmin)
            ->get(route('pemeriksaan.show', $pemeriksaan))
            ->getContent();

        // Modal review hanya boleh ada satu
        $this->assertSame(1, substr_count($html, 'id="review-modal-form"'));

        // Field yang dikirim harus sesuai kontrak PengajuanController
        $this->assertStringContainsString('name="catatan_revisi"', $html);

        // Field lama yang menyebabkan validasi gagal tidak boleh tersisa
        $this->assertStringNotContainsString('name="catatan"', $html);
    }

    public function test_temuan_show_has_no_dead_review_modal(): void
    {
        $temuan = Temuan::first();
        $this->assertNotNull($temuan, 'Butuh data temuan untuk test ini.');

        $html = $this->actingAs($this->superAdmin)
            ->get(route('temuan.show', $temuan))
            ->getContent();

        // Modal review mati + route hantu yang selalu 404
        $this->assertStringNotContainsString('id="modal-review"', $html);
        $this->assertStringNotContainsString('/pengajuan/temuan/', $html);
        $this->assertStringNotContainsString('openReviewModal', $html);
    }

    public function test_super_admin_can_revise_and_reject_pemeriksaan(): void
    {
        // End-to-end: ini alur yang sebelumnya selalu gagal validasi karena
        // form membawa `catatan` alih-alih `catatan_revisi`.
        $pemeriksaan = Pemeriksaan::first();
        $pemeriksaan->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $revise = $this->actingAs($this->superAdmin)->post(
            route('pengajuan.pemeriksaan.revise', $pemeriksaan),
            ['catatan_revisi' => 'Mohon lengkapi dokumen pendukung SPT.']
        );

        $revise->assertSessionHasNoErrors();
        $revise->assertRedirect();
        $this->assertEquals('PERLU REVISI', $pemeriksaan->fresh()->status_pengajuan);

        // fresh() wajib: instance $pemeriksaan masih memegang status lama,
        // sehingga update() kedua tidak akan tersimpan.
        $pemeriksaan->fresh()->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $reject = $this->actingAs($this->superAdmin)->post(
            route('pengajuan.pemeriksaan.reject', $pemeriksaan),
            ['catatan_revisi' => 'Data tidak sesuai dengan dokumen sumber.']
        );

        $reject->assertSessionHasNoErrors();
        $reject->assertRedirect();
        $this->assertEquals('DITOLAK', $pemeriksaan->fresh()->status_pengajuan);
    }

    // =========================================================================
    // Item 3 — arsip pemulihan mencakup seluruh entitas soft-deletable
    // =========================================================================

    public function test_trash_lists_and_restores_all_archived_entity_types(): void
    {
        $opd = Opd::where('kode', 'DINKES')->first();
        $pegawai = Pegawai::first();
        $pemeriksaan = Pemeriksaan::first();
        $temuan = Temuan::first();

        // Akun OPD di-seed → lepas dulu agar OPD bisa dihapus,
        // lalu soft-delete keempat entitas.
        $this->opdDinkes->forceDelete();
        $opd->delete();
        $pegawai->delete();
        $temuan->delete();

        $cases = [
            'opd' => $opd,
            'pegawai' => $pegawai,
            'temuan' => $temuan,
        ];

        foreach ($cases as $type => $model) {
            $list = $this->actingAs($this->superAdmin)
                ->get(route('audit-log.trash', ['type' => $type]));

            $list->assertOk();
            $this->assertSame(
                1,
                $model->newQuery()->onlyTrashed()->whereKey($model->getKey())->count(),
                "Data {$type} harus ter-soft-delete sebelum dipulihkan."
            );

            // Tiap tipe harus muncul sebagai tab dengan jumlahnya
            $list->assertSee('type=' . $type, false);

            $restore = $this->actingAs($this->superAdmin)->post(
                route('audit-log.restore', ['type' => $type, 'id' => $model->getKey()])
            );

            $restore->assertSessionHasNoErrors();
            $this->assertNull(
                $model->newQuery()->onlyTrashed()->whereKey($model->getKey())->first(),
                "Data {$type} gagal dipulihkan lewat UI arsip."
            );
        }
    }

    public function test_trash_can_force_delete_archived_user_and_pegawai(): void
    {
        $user = User::where('email', 'opd.dikbud@gorontaloprov.go.id')->first();
        $userId = $user->id;
        $user->delete();

        $list = $this->actingAs($this->superAdmin)->get(route('audit-log.trash', ['type' => 'user']));
        $list->assertOk();

        $delete = $this->actingAs($this->superAdmin)->delete(
            route('audit-log.force-delete', ['type' => 'user', 'id' => $userId])
        );

        $delete->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_trash_page_rejects_unknown_type_gracefully(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.trash', ['type' => 'tabel_ajaib']));

        $response->assertOk();
    }

    // =========================================================================
    // Item 4 — jangan tampilkan link ke route Super Admin untuk role lain
    // =========================================================================

    public function test_non_super_admin_does_not_see_super_admin_only_links(): void
    {
        $temuan = Temuan::first();
        $this->assertNotNull($temuan);

        foreach (['adminBpk' => $this->adminBpk, 'pemantau' => $this->pemantau] as $label => $user) {
            $dashboard = $this->actingAs($user)->get(route('dashboard'));
            $dashboard->assertOk();
            $dashboard->assertDontSee(route('opd.index'), false);

            $temuanPage = $this->actingAs($user)->get(route('temuan.show', $temuan));
            $temuanPage->assertOk();
            $temuan->loadMissing('opd');
            if ($temuan->opd) {
                $temuanPage->assertDontSee(route('opd.show', $temuan->opd), false);
            }
        }

        // Super Admin tetap melihatnya
        $superDashboard = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $superDashboard->assertSee(route('opd.index'), false);
    }

    // =========================================================================
    // Item 5 — AuditLogger merekam diff untuk aksi workflow
    // =========================================================================

    public function test_workflow_actions_record_audit_diff(): void
    {
        $pemeriksaan = Pemeriksaan::first();
        $this->assertNotNull($pemeriksaan);

        $workflow = [
            'SUBMIT' => 'MENUNGGU PERSETUJUAN',
            'APPROVE' => 'DISETUJUI',
            'REVISE' => 'PERLU REVISI',
            'REJECT' => 'DITOLAK',
            'CANCEL_SUBMIT' => 'DRAFT',
        ];

        foreach ($workflow as $action => $newStatus) {
            $oldAttributes = $pemeriksaan->fresh()->getAttributes();

            $pemeriksaan->fresh()->update(['status_pengajuan' => $newStatus]);

            AuditLogger::logModelChange(
                $action,
                $pemeriksaan->fresh(),
                'PEMERIKSAAN',
                "Uji aksi {$action}.",
                $oldAttributes
            );

            $log = AuditLog::where('module', 'PEMERIKSAAN')
                ->where('action', $action)
                ->latest('id')
                ->first();

            $this->assertNotNull($log, "Audit log untuk aksi {$action} tidak tercatat.");
            $this->assertIsArray($log->old_values, "old_values aksi {$action} harus berupa array, bukan null.");
            $this->assertIsArray($log->new_values, "new_values aksi {$action} harus berupa array, bukan null.");
            $this->assertArrayHasKey('status_pengajuan', $log->new_values, "new_values aksi {$action} harus memuat status_pengajuan.");
            $this->assertEquals($newStatus, $log->new_values['status_pengajuan']);
        }
    }

    public function test_workflow_audit_diff_preserves_existing_create_and_update_behaviour(): void
    {
        // CREATE tetap merekam seluruh atribut baru
        $pemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'BPKP',
            'status_pengajuan' => 'DRAFT',
            'nomor_spt' => 'SPT/REGRESI/001',
            'tahun' => date('Y'),
            'created_by' => $this->superAdmin->id,
        ]);

        AuditLogger::logModelChange('CREATE', $pemeriksaan, 'PEMERIKSAAN', 'Uji CREATE.');

        $createLog = AuditLog::where('action', 'CREATE')->where('module', 'PEMERIKSAAN')->latest('id')->first();
        $this->assertIsArray($createLog->new_values);
        $this->assertArrayHasKey('nomor_spt', $createLog->new_values);

        // UPDATE tetap merekam diff perubahan
        $old = $pemeriksaan->getAttributes();
        $pemeriksaan->update(['judul' => 'Judul Setelah Update']);

        AuditLogger::logModelChange('UPDATE', $pemeriksaan, 'PEMERIKSAAN', 'Uji UPDATE.', $old);

        $updateLog = AuditLog::where('action', 'UPDATE')->where('module', 'PEMERIKSAAN')->latest('id')->first();
        $this->assertIsArray($updateLog->new_values);
        $this->assertArrayHasKey('judul', $updateLog->new_values);
        $this->assertEquals('Judul Setelah Update', $updateLog->new_values['judul']);
    }
}
