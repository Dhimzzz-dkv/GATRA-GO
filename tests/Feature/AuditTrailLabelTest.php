<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard keterbacaan Audit Trail.
 *
 * Audit trail adalah fitur kepatuhan. Bila jenis aksi tampil sebagai kode mentah
 * (mis. FORCE_DELETE, LOGIN_FAILED), pembaca tidak dapat menilai apa yang terjadi —
 * justru pada dua peristiwa keamanan yang paling perlu terlihat.
 */
class AuditTrailLabelTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
    }

    public function test_every_recorded_action_has_an_indonesian_label(): void
    {
        foreach (AuditLog::knownActions() as $action) {
            $log = new AuditLog(['action' => $action]);
            $badge = $log->action_badge;

            $this->assertNotSame(
                $action,
                $badge['label'],
                "Aksi {$action} masih tampil sebagai kode mentah, bukan label Bahasa Indonesia."
            );

            $this->assertMatchesRegularExpression(
                '/^[A-Za-z ]+$/',
                $badge['label'],
                "Label untuk aksi {$action} mengandung karakter tak terduga."
            );

            $this->assertStringNotContainsString('_', $badge['label'], "Label {$action} tidak boleh memuat garis bawah.");
            $this->assertNotEmpty($badge['class']);
        }
    }

    public function test_security_events_have_visually_distinct_labels(): void
    {
        // Dua peristiwa keamanan yang paling penting harus terbaca jelas
        $failedLogin = (new AuditLog(['action' => 'LOGIN_FAILED']))->action_badge;
        $locked = (new AuditLog(['action' => 'LOGIN_LOCKED']))->action_badge;
        $forceDelete = (new AuditLog(['action' => 'FORCE_DELETE']))->action_badge;
        $normalLogin = (new AuditLog(['action' => 'LOGIN']))->action_badge;

        $this->assertSame('Login Gagal', $failedLogin['label']);
        $this->assertSame('Akun Terkunci', $locked['label']);
        $this->assertSame('Hapus Permanen', $forceDelete['label']);
        $this->assertSame('Login', $normalLogin['label']);

        // Login gagal harus berbeda warna dari login sukses
        $this->assertNotSame($normalLogin['class'], $failedLogin['class']);
    }

    public function test_workflow_actions_have_labels(): void
    {
        $expected = [
            'SUBMIT' => 'Pengajuan',
            'CANCEL_SUBMIT' => 'Batal Pengajuan',
            'APPROVE' => 'Disetujui',
            'REVISE' => 'Minta Revisi',
            'REJECT' => 'Ditolak',
        ];

        foreach ($expected as $action => $label) {
            $this->assertSame(
                $label,
                (new AuditLog(['action' => $action]))->action_badge['label'],
                "Label untuk aksi workflow {$action} tidak sesuai."
            );
        }
    }

    public function test_unknown_action_falls_back_to_raw_code(): void
    {
        // Fallback harus tetap ada agar aksi baru tidak membuat halaman error
        $badge = (new AuditLog(['action' => 'AKSI_BARU_BELUM_TERDAFTAR']))->action_badge;

        $this->assertSame('AKSI_BARU_BELUM_TERDAFTAR', $badge['label']);
        $this->assertStringContainsString('bg-gray-100', $badge['class']);
    }

    public function test_audit_log_page_renders_labels_not_raw_codes(): void
    {
        AuditLogger::log('LOGIN_FAILED', 'AUTH', 'Percobaan login gagal untuk pengujian.');
        AuditLogger::log('LOGIN_LOCKED', 'AUTH', 'Akun terkunci untuk pengujian.');
        AuditLogger::log('FORCE_DELETE', 'PEMERIKSAAN', 'Penghapusan permanen untuk pengujian.');

        $html = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.index'))
            ->getContent();

        $checks = [
            'label LOGIN_FAILED' => str_contains($html, 'Login Gagal'),
            'label LOGIN_LOCKED' => str_contains($html, 'Akun Terkunci'),
            'label FORCE_DELETE' => str_contains($html, 'Hapus Permanen'),
            'tidak ada >LOGIN_FAILED<' => ! str_contains($html, '>LOGIN_FAILED<'),
            'tidak ada >LOGIN_LOCKED<' => ! str_contains($html, '>LOGIN_LOCKED<'),
            'tidak ada >FORCE_DELETE<' => ! str_contains($html, '>FORCE_DELETE<'),
        ];

        foreach ($checks as $name => $passed) {
            $this->assertTrue($passed, "Gagal: {$name}");
        }
    }

    public function test_module_codes_have_readable_labels(): void
    {
        $expected = [
            'AUTH' => 'Autentikasi',
            'PEMERIKSAAN' => 'Pemeriksaan',
            'TINDAK_LANJUT' => 'Tindak Lanjut',
            'TEMUAN' => 'Temuan',
            'LHP' => 'LHP',
            'SPT' => 'SPT',
        ];

        foreach ($expected as $code => $label) {
            $this->assertSame($label, AuditLog::moduleLabel($code), "Label modul {$code} tidak sesuai.");
        }

        $this->assertSame('-', AuditLog::moduleLabel(null));

        // Modul tak terdaftar tetap terbaca, bukan snake_case mentah
        $this->assertSame('Modul Baru', AuditLog::moduleLabel('MODUL_BARU'));
        $this->assertStringNotContainsString('_', AuditLog::moduleLabel('MODUL_BARU'));
    }

    public function test_filter_dropdowns_do_not_expose_raw_codes(): void
    {
        AuditLogger::log('LOGIN_LOCKED', 'TINDAK_LANJUT', 'Uji kode mentah pada filter.');

        $html = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.index'))
            ->getContent();

        // Kode mentah tidak boleh muncul sebagai teks opsi/teks badge
        $this->assertStringNotContainsString('>LOGIN_LOCKED<', $html);
        $this->assertStringNotContainsString('>TINDAK_LANJUT<', $html);
        $this->assertStringNotContainsString('>PEMERIKSAAN<', $html);

        // Label yang terbaca harus ada
        $this->assertStringContainsString('Akun Terkunci', $html);
        $this->assertStringContainsString('Tindak Lanjut', $html);
    }

    public function test_attribute_labels_are_human_readable(): void
    {
        // Kolom bertingkat tanpa label harus tetap terbaca, bukan snake_case mentah
        $this->assertSame('Status Pengajuan', AuditLog::attributeLabel('status_pengajuan'));
        $this->assertSame('Nilai Kerugian Daerah (Rp)', AuditLog::attributeLabel('nilai_kerugian'));

        // Kolom yang tidak terdaftar diubah menjadi Title Case
        $this->assertSame('Kolom Baru', AuditLog::attributeLabel('kolom_baru'));
        $this->assertStringNotContainsString('_', AuditLog::attributeLabel('kolom_baru'));
    }
}
