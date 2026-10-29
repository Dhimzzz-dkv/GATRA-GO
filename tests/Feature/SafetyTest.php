<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard keamanan operasional (Phase 3).
 *
 * Mengunci tiga perbaikan:
 *  - aksi toggle status pada master data meminta konfirmasi
 *  - antrean persetujuan tersedia sebagai halaman tersendiri + badge sidebar
 *  - daftar temuan menyediakan tombol Buat & Edit
 */
class SafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $opdDinkes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
    }

    // =========================================================================
    // Konfirmasi aksi toggle status
    // =========================================================================

    public function test_toggle_status_buttons_require_confirmation(): void
    {
        $pages = [
            'user.index' => route('user.index'),
            'opd.index' => route('opd.index'),
            'pegawai.index' => route('pegawai.index'),
        ];

        foreach ($pages as $label => $url) {
            $html = $this->actingAs($this->superAdmin)->get($url)->getContent();

            // Ambil seluruh blok <form>
            preg_match_all('/<form[^>]*>.*?<\/form>/s', $html, $forms);

            $toggleForms = array_values(array_filter(
                $forms[0] ?? [],
                fn ($form) => str_contains($form, 'toggle-status')
            ));

            $this->assertNotEmpty(
                $toggleForms,
                "Halaman [{$label}] harus memiliki form toggle-status."
            );

            foreach ($toggleForms as $form) {
                $this->assertStringContainsString(
                    'confirm(',
                    $form,
                    "Form toggle-status pada [{$label}] harus meminta konfirmasi sebelum mengubah status."
                );
            }
        }
    }

    public function test_toggle_status_confirmation_mentions_the_affected_entity(): void
    {
        $html = $this->actingAs($this->superAdmin)->get(route('user.index'))->getContent();

        // Pesan konfirmasi harus menyebut nama pengguna, bukan sekadar "yakin?"
        $user = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->assertStringContainsString('Nonaktifkan akun', $html);
        $this->assertStringContainsString($user->name, $html);
    }

    // =========================================================================
    // Antrean persetujuan
    // =========================================================================

    public function test_approval_queue_page_is_accessible_for_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('pengajuan.index'));

        $response->assertOk();
        $response->assertViewIs('pengajuan.index');
        $response->assertSee('Menunggu Persetujuan');
        $response->assertSee('Perlu Revisi');
    }

    public function test_approval_queue_is_a_dedicated_page_not_a_redirect(): void
    {
        // Sebelumnya route ini hanya closure yang me-redirect ke daftar pemeriksaan,
        // sehingga antrean persetujuan tidak pernah punya halaman sendiri.
        $response = $this->actingAs($this->superAdmin)->get(route('pengajuan.index'));

        $response->assertOk();
        $response->assertViewIs('pengajuan.index');
        // Halaman sendiri memiliki judul khas, bukan daftar pemeriksaan
        $response->assertSee('Antrean Pengajuan');
        $response->assertSee('Antrean Pengajuan &amp; Persetujuan', false);

        // Tidak boleh ada redirect
        $this->assertNotSame(302, $response->getStatusCode());
    }

    public function test_approval_queue_lists_pending_submissions_with_actions(): void
    {
        $pemeriksaan = Pemeriksaan::first();
        $pemeriksaan->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $response = $this->actingAs($this->superAdmin)->get(route('pengajuan.index'));

        $response->assertOk();
        $response->assertSee($pemeriksaan->nomor_spt);

        // Tiga aksi keputusan harus tersedia
        $response->assertSee('Setujui');
        $response->assertSee('Minta Revisi');
        $response->assertSee('Tolak');

        // Route aksi harus benar-benar ada
        $response->assertSee(route('pengajuan.pemeriksaan.approve', $pemeriksaan), false);
        $response->assertSee(route('pengajuan.pemeriksaan.revise', $pemeriksaan), false);
        $response->assertSee(route('pengajuan.pemeriksaan.reject', $pemeriksaan), false);
    }

    public function test_approval_queue_denies_non_super_admin(): void
    {
        $this->actingAs($this->adminBpk)
            ->get(route('pengajuan.index'))
            ->assertForbidden();

        $this->actingAs($this->opdDinkes)
            ->get(route('pengajuan.index'))
            ->assertForbidden();
    }

    public function test_sidebar_shows_pending_approval_badge_only_for_super_admin(): void
    {
        $pemeriksaan = Pemeriksaan::first();
        $pemeriksaan->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        // Super Admin: menu + badge muncul
        $superHtml = $this->actingAs($this->superAdmin)->get(route('dashboard'))->getContent();
        $this->assertStringContainsString('Antrean Persetujuan', $superHtml);
        $this->assertStringContainsString(route('pengajuan.index'), $superHtml);

        // Admin Sumber: menu tidak boleh muncul (halaman pun 403)
        $adminHtml = $this->actingAs($this->adminBpk)->get(route('dashboard'))->getContent();
        $this->assertStringNotContainsString('Antrean Persetujuan', $adminHtml);
    }

    public function test_sidebar_badge_hides_when_no_pending_approvals(): void
    {
        Pemeriksaan::query()->update(['status_pengajuan' => 'DRAFT']);

        $html = $this->actingAs($this->superAdmin)->get(route('dashboard'))->getContent();

        // Menu tetap ada, tetapi tanpa badge angka
        $this->assertStringContainsString('Antrean Persetujuan', $html);
        $this->assertStringNotContainsString('menunggu keputusan Anda', $html);
    }

    // =========================================================================
    // Daftar temuan: tombol Buat & Edit
    // =========================================================================

    public function test_temuan_index_has_create_button_for_authorised_roles(): void
    {
        $html = $this->actingAs($this->superAdmin)->get(route('temuan.index'))->getContent();

        $this->assertStringContainsString('Tambah Temuan', $html);
        $this->assertStringContainsString(route('temuan.create'), $html);
    }

    public function test_temuan_index_has_edit_button_for_authorised_roles(): void
    {
        $temuan = Temuan::first();
        $this->assertNotNull($temuan, 'Butuh data temuan untuk test ini.');

        $html = $this->actingAs($this->superAdmin)->get(route('temuan.index'))->getContent();

        $this->assertStringContainsString(route('temuan.edit', $temuan), $html);
    }

    public function test_temuan_index_hides_mutation_buttons_for_readonly_roles(): void
    {
        // OPD & Pemantau tidak boleh melihat tombol Buat
        foreach (['opd' => $this->opdDinkes] as $label => $user) {
            $html = $this->actingAs($user)->get(route('temuan.index'))->getContent();

            $this->assertStringNotContainsString(
                route('temuan.create'),
                $html,
                "Role [{$label}] tidak boleh melihat tombol Tambah Temuan."
            );
        }
    }

    // =========================================================================
    // Konfirmasi hapus temuan menyebut konteks
    // =========================================================================

    public function test_delete_temuan_confirmation_mentions_related_data(): void
    {
        $temuan = Temuan::first();
        $this->assertNotNull($temuan);

        $html = $this->actingAs($this->superAdmin)->get(route('temuan.index'))->getContent();

        // Pesan harus menjelaskan bahwa tindak lanjut & rekomendasi ikut terhapus
        $this->assertStringContainsString('Tindak lanjut dan rekomendasi yang terkait juga akan ikut terhapus', $html);
    }
}
