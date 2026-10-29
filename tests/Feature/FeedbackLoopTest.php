<?php

namespace Tests\Feature;

use App\Models\Pemeriksaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard umpan balik antarmuka (Phase 2).
 *
 * Mengunci tiga perbaikan:
 *  - ringkasan error validasi global tampil di setiap halaman
 *  - label pagination berbahasa Indonesia, bukan "Showing ... to ... of ..."
 *  - form tidak mengirim ulang dua kali (guard double-submit terpasang)
 */
class FeedbackLoopTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminInsp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
    }

    // =========================================================================
    // Ringkasan error validasi
    // =========================================================================

    public function test_validation_error_summary_appears_after_failed_submission(): void
    {
        // Simulasikan perilaku browser: POST dilakukan dari halaman form,
        // sehingga Laravel mengembalikan pengguna ke form yang sama
        // beserta ringkasan error validasi.
        $response = $this->actingAs($this->adminInsp)
            ->from(route('pemeriksaan.create'))
            ->followingRedirects()
            ->post(route('pemeriksaan.store'), []);

        $response->assertOk();
        $response->assertSee('Data belum dapat disimpan');
        $response->assertSee('focusValidationField', false);
        $response->assertSee('validation-error-summary', false);
    }

    public function test_failed_submission_returns_user_to_the_form_not_the_homepage(): void
    {
        // Tanpa perbaikan ini, pengguna yang gagal validasi dilempar ke beranda
        // dan seluruh isian hilang tanpa penjelasan.
        $response = $this->actingAs($this->adminInsp)
            ->from(route('pemeriksaan.create'))
            ->post(route('pemeriksaan.store'), []);

        $response->assertRedirect(route('pemeriksaan.create'));
    }

    public function test_validation_error_summary_lists_the_actual_messages(): void
    {
        $response = $this->actingAs($this->adminInsp)
            ->from(route('pemeriksaan.create'))
            ->followingRedirects()
            ->post(route('pemeriksaan.store'), [
                'sumber_pemeriksaan' => 'Inspektorat',
                // nomor_spt sengaja dikosongkan
                'tahun' => 2026,
            ]);

        $response->assertSee('Data belum dapat disimpan');
        // Nama field bermasalah ikut ditampilkan sebagai petunjuk
        $response->assertSee('nomor_spt', false);
    }

    public function test_error_summary_component_renders_with_error_bag(): void
    {
        // Uji komponen secara langsung: pesan, tombol gulir, dan jumlah error
        $errors = new \Illuminate\Support\ViewErrorBag();
        $errors->put('default', new \Illuminate\Support\MessageBag([
            'nomor_spt' => ['Nomor SPT wajib diisi.'],
            'lhps.0.nomor_lhp' => ['Nomor LHP wajib diisi.'],
        ]));
        view()->share('errors', $errors);

        $html = view('components.validation-errors')->render();

        $this->assertStringContainsString('Data belum dapat disimpan', $html);
        $this->assertStringContainsString('(2 masalah ditemukan)', $html);
        $this->assertStringContainsString('Nomor SPT wajib diisi.', $html);
        $this->assertStringContainsString('focusValidationField', $html);
        $this->assertStringContainsString('id="validation-error-summary"', $html);

        // Komponen tidak boleh merender apa pun bila tidak ada error
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $this->assertSame('', trim(view('components.validation-errors')->render()));
    }

    public function test_validation_error_summary_is_not_auto_dismissed(): void
    {
        $component = file_get_contents(resource_path('views/components/validation-errors.blade.php'));

        // Ringkasan error harus dikecualikan dari auto-dismiss otomatis
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString(
            ":not(#validation-error-summary)",
            $layout,
            'Ringkasan error tidak boleh ikut tertutup otomatis.'
        );

        // Menyediakan tombol gulir-ke-field
        $this->assertStringContainsString('focusValidationField', $component);
        // ID unik agar dapat ditutup manual dan tidak tertukar
        $this->assertStringContainsString('id="validation-error-summary"', $component);
    }

    // =========================================================================
    // Pagination Bahasa Indonesia
    // =========================================================================

    public function test_pagination_is_rendered_in_indonesian(): void
    {
        // Buat data secukupnya agar pagination muncul (15 baris per halaman).
        $this->ensureAtLeastPemeriksaan(18);

        $response = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.index'));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Menampilkan', $html, 'Label rentang data harus berbahasa Indonesia.');
        $this->assertStringContainsString('Berikutnya', $html, 'Tombol navigasi harus berbahasa Indonesia.');
        $this->assertStringNotContainsString('Showing', $html, 'Label bawaan Inggris tidak boleh tersisa.');
        $this->assertStringNotContainsString('of results', $html);
    }

    public function test_pagination_shows_data_range(): void
    {
        $this->ensureAtLeastPemeriksaan(18);

        $response = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.index'));

        // "Menampilkan 1 – 15 dari 18 data"
        $response->assertSee('Menampilkan');
        $response->assertSee('dari');
        $response->assertSee('data');
    }

    /**
     * Pastikan jumlah record pemeriksaan mencukupi untuk menguji pagination.
     */
    private function ensureAtLeastPemeriksaan(int $target): void
    {
        $existing = Pemeriksaan::count();

        if ($existing >= $target) {
            return;
        }

        $base = Pemeriksaan::first();

        for ($i = 0; $i < ($target - $existing); $i++) {
            Pemeriksaan::create([
                'sumber_pemeriksaan' => 'Inspektorat',
                'opd_id' => $base?->opd_id,
                'tahun' => 2026,
                'judul' => 'Pemeriksaan Uji Pagination ' . $i,
                'nomor_spt' => '700/PAGE/' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '/2026',
                'status_pengajuan' => 'DISETUJUI',
                'created_by' => $this->superAdmin->id,
            ]);
        }

        $this->assertGreaterThanOrEqual($target, Pemeriksaan::count());
    }

    public function test_custom_pagination_view_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/vendor/pagination/tailwind.blade.php'),
            'View pagination kustom harus ada agar label berbahasa Indonesia dipakai.'
        );
    }

    // =========================================================================
    // Loading state / pencegahan double-submit
    // =========================================================================

    public function test_global_submit_guard_is_installed(): void
    {
        $html = $this->actingAs($this->superAdmin)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString("document.addEventListener('submit'", $html);
        $this->assertStringContainsString('Memproses', $html);
        // Harus menghormati pembatalan konfirmasi
        $this->assertStringContainsString('confirm', $html);
    }

    public function test_forms_can_opt_out_of_loading_state(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('data-no-loading', $layout);
        $this->assertStringContainsString('data-loading-text', $layout);
    }

    public function test_flash_message_dismiss_pauses_on_hover(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        // Pesan sukses/error harus berhenti menghilang saat kursor di atasnya
        $this->assertStringContainsString('mouseenter', $layout);
        $this->assertStringContainsString('mouseleave', $layout);
    }
}
