<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard halaman error kustom.
 *
 * Tanpa halaman kustom, pengguna yang mengalami error akan melihat halaman
 * bawaan framework — tanpa navigasi, tanpa penjelasan, dan pada konfigurasi
 * yang salah dapat menampilkan stack trace beserta kredensial database.
 *
 * Test ini menjaga tiga hal:
 *   1. Keempat halaman ada dan dapat dirender
 *   2. Halaman TIDAK membocorkan detail teknis apa pun
 *   3. Laravel benar-benar memakai halaman kustom saat error terjadi
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Frasa yang menandakan kebocoran informasi internal.
     *
     * @var array<int, string>
     */
    private const PENANDA_BOCOR = [
        'Stack trace',
        'stack trace',
        'QueryException',
        'LazyLoadingViolationException',
        'RuntimeException',
        'Fatal error',
        'SQLSTATE',
        'vendor/',
        'vendor\\',
        'app/Http',
        'app\\Http',
        'Whoops',
        'APP_KEY',
        'DB_PASSWORD',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    // =========================================================================
    // 1. Keempat halaman ada dan dapat dirender
    // =========================================================================

    public function test_all_error_pages_render(): void
    {
        foreach (['403', '404', '419', '500'] as $kode) {
            $html = view("errors.{$kode}")->render();

            $this->assertNotEmpty($html, "Halaman errors/{$kode} menghasilkan keluaran kosong.");
            $this->assertStringContainsString('<!DOCTYPE html>', $html);
            $this->assertStringContainsString('</html>', $html);
            $this->assertStringContainsString($kode, $html, "Kode {$kode} harus tampil di halaman.");
        }
    }

    public function test_error_page_files_exist(): void
    {
        foreach (['403', '404', '419', '500'] as $kode) {
            $this->assertFileExists(
                resource_path("views/errors/{$kode}.blade.php"),
                "Halaman error untuk kode {$kode} belum dibuat."
            );
        }
    }

    // =========================================================================
    // 2. Tidak membocorkan detail teknis
    // =========================================================================

    public function test_error_pages_do_not_leak_technical_details(): void
    {
        foreach (['403', '404', '419', '500'] as $kode) {
            $html = view("errors.{$kode}")->render();

            foreach (self::PENANDA_BOCOR as $penanda) {
                $this->assertStringNotContainsString(
                    $penanda,
                    $html,
                    "Halaman errors/{$kode} membocorkan detail teknis: '{$penanda}'."
                );
            }
        }
    }

    public function test_error_pages_provide_user_guidance(): void
    {
        // Setiap halaman harus memberi penjelasan & jalan keluar, bukan sekadar
        // menampilkan kode angka.
        foreach (['403', '404', '419', '500'] as $kode) {
            $html = view("errors.{$kode}")->render();

            $this->assertMatchesRegularExpression(
                '/Dashboard|Muat Ulang|muat ulang/i',
                $html,
                "Halaman errors/{$kode} tidak memberi jalan keluar kepada pengguna."
            );

            // Harus ada identitas instansi agar pengguna tahu ini halaman resmi
            $this->assertStringContainsString('Inspektorat', $html);
        }
    }

    public function test_error_pages_do_not_require_authentication(): void
    {
        // Halaman error harus dapat dirender TANPA pengguna terautentikasi.
        // Bila bergantung pada auth()->user(), error pada halaman login akan
        // memicu error kedua saat menampilkan error pertama.
        $this->assertGuest();

        foreach (['403', '404', '419', '500'] as $kode) {
            $html = view("errors.{$kode}")->render();
            $this->assertNotEmpty($html, "Halaman errors/{$kode} gagal dirender tanpa autentikasi.");
        }
    }

    // =========================================================================
    // 3. Laravel benar-benar memakai halaman kustom
    // =========================================================================

    public function test_forbidden_response_uses_custom_page(): void
    {
        $user = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        // OPD tidak berhak mengakses master pengguna
        $response = $this->actingAs($user)->get(route('user.index'));

        $response->assertStatus(403);
        $response->assertSee('Akses Ditolak');
        $response->assertSee('Inspektorat', false);
    }

    public function test_not_found_response_uses_custom_page(): void
    {
        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();

        $response = $this->actingAs($user)->get('/halaman-yang-tidak-pernah-ada');

        $response->assertStatus(404);
        $response->assertSee('Tidak Ditemukan');
        $response->assertSee('Inspektorat', false);
    }

    public function test_server_error_page_hides_details_when_debug_off(): void
    {
        // Simulasikan lingkungan produksi: APP_DEBUG dimatikan.
        config(['app.debug' => false]);

        $response = $this->get('/__uji-kesalahan-server');

        // Route sengaja tidak ada → 404. Untuk menguji 500 yang sebenarnya,
        // kita render view-nya langsung dan pastikan tidak ada detail teknis.
        $html = view('errors.500')->render();

        foreach (self::PENANDA_BOCOR as $penanda) {
            $this->assertStringNotContainsString($penanda, $html);
        }

        $this->assertStringContainsString('Terjadi Kesalahan', $html);
        $this->assertStringContainsString('Terjadi Kesalahan', $html);

        // Halaman 500 harus menyebut bahwa kejadian dicatat, agar pengguna
        // tahu ada tindak lanjut dan dapat melaporkan waktunya.
        $this->assertMatchesRegularExpression('/tercatat|dicatat/i', $html);
    }

    public function test_expired_session_page_offers_reload(): void
    {
        $html = view('errors.419')->render();

        $this->assertStringContainsString('Muat Ulang', $html);
        // Halaman 419 harus menjelaskan sesi berakhir, bukan sekadar "error"
        $this->assertMatchesRegularExpression('/sesi/i', $html);
    }

    public function test_error_layout_does_not_extend_app_layout(): void
    {
        // Layout aplikasi mengakses auth()->user() melalui sidebar.
        // Halaman error harus mandiri agar tidak memicu error berantai.
        $layout = file_get_contents(resource_path('views/errors/layout.blade.php'));

        $this->assertStringNotContainsString(
            "@extends('layouts.app')",
            $layout,
            'Layout error tidak boleh mewarisi layouts.app.'
        );

        $this->assertStringNotContainsString(
            "@include('layouts.",
            $layout,
            'Layout error tidak boleh menyertakan partial dari layouts.'
        );

        // Periksa PEMAKAIAN nyata, bukan penyebutan di komentar.
        // Buang komentar Blade dan komentar PHP lebih dulu.
        $kode = preg_replace('~\{\{--.*?--\}\}~s', '', $layout);
        $kode = preg_replace('~/\*.*?\*/~s', '', $kode);
        $kode = preg_replace('~^\s*//.*$~m', '', $kode);

        foreach (['auth()->user()', 'auth()->check()', '@auth', '@can'] as $terlarang) {
            $this->assertStringNotContainsString(
                $terlarang,
                $kode,
                "Layout error tidak boleh bergantung pada autentikasi: '{$terlarang}'."
            );
        }
    }
}
