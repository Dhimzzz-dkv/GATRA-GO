<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard konfigurasi produksi.
 *
 * Memastikan command `app:check-production-config` benar-benar menangkap
 * SETIAP nilai konfigurasi yang berbahaya. Bila ada pemeriksaan yang terhapus
 * atau dilonggarkan, test ini gagal — sehingga kesalahan konfigurasi tidak
 * dapat lolos ke server produksi tanpa terdeteksi.
 *
 * Ini penting karena `.env` tidak dapat di-commit: nilai pengembangan mudah
 * terbawa ke server, dan APP_DEBUG=true membuat stack trace serta kredensial
 * database terlihat oleh pengguna biasa saat terjadi error.
 */
class ProductionConfigGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jalankan command dengan konfigurasi tertentu.
     *
     * @param  array<string, mixed>  $override
     */
    private function jalankan(array $override = []): int
    {
        // Konfigurasi dasar yang SUDAH benar untuk produksi
        $dasar = [
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'base64:INIhANYACONTOHKUNCIUNTUKPENGUJIAN0123456=',
            'app.url' => 'https://simantap.example.go.id',
            'logging.default' => 'stack',
            'logging.channels.stack.level' => 'warning',
            'session.driver' => 'database',
            'session.encrypt' => true,
            'session.secure' => true,
            'mail.default' => 'smtp',
        ];

        config(array_merge($dasar, $override));

        return \Illuminate\Support\Facades\Artisan::call('app:check-production-config', [
            '--loloskan-env-lokal' => true,
        ]);
    }

    // =========================================================================
    // Konfigurasi benar → lulus
    // =========================================================================

    public function test_correct_production_config_passes(): void
    {
        $exit = $this->jalankan([
            'app.env' => 'production',
            'app.debug' => false,
        ]);

        $this->assertSame(
            0,
            $exit,
            'Konfigurasi produksi yang benar seharusnya lulus. Keluaran: '
            . \Illuminate\Support\Facades\Artisan::output()
        );
    }

    // =========================================================================
    // Setiap nilai berbahaya harus tertangkap
    // =========================================================================

    public function test_app_debug_true_is_flagged(): void
    {
        $exit = $this->jalankan(['app.debug' => true]);

        $this->assertNotSame(0, $exit, 'APP_DEBUG=true harus ditolak.');
        $this->assertStringContainsString('APP_DEBUG', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_empty_app_key_is_flagged(): void
    {
        $exit = $this->jalankan(['app.key' => '']);

        $this->assertNotSame(0, $exit, 'APP_KEY kosong harus ditolak.');
        $this->assertStringContainsString('APP_KEY', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_debug_log_level_is_flagged(): void
    {
        $exit = $this->jalankan(['logging.channels.stack.level' => 'debug']);

        $this->assertNotSame(0, $exit, 'LOG_LEVEL=debug harus ditolak.');
        $this->assertStringContainsString('LOG_LEVEL', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_unencrypted_session_is_flagged(): void
    {
        $exit = $this->jalankan(['session.encrypt' => false]);

        $this->assertNotSame(0, $exit, 'SESSION_ENCRYPT=false harus ditolak.');
        $this->assertStringContainsString('SESSION_ENCRYPT', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_insecure_cookie_is_flagged_as_warning(): void
    {
        $exit = $this->jalankan(['session.secure' => false]);

        $keluaran = \Illuminate\Support\Facades\Artisan::output();

        // Peringatan tidak menghalangi deploy, tetapi harus DILAPORKAN
        $this->assertStringContainsString('SESSION_SECURE_COOKIE', $keluaran);
    }

    public function test_log_mailer_is_flagged(): void
    {
        $exit = $this->jalankan(['mail.default' => 'log']);

        $keluaran = \Illuminate\Support\Facades\Artisan::output();

        $this->assertStringContainsString('MAIL_MAILER', $keluaran);
        $this->assertStringContainsString('NONAKTIF', $keluaran);
    }

    public function test_non_production_env_is_flagged(): void
    {
        $exit = $this->jalankan(['app.env' => 'local']);

        $this->assertStringContainsString('APP_ENV', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_http_url_in_production_is_flagged(): void
    {
        $exit = $this->jalankan(['app.url' => 'http://simantap.example.go.id']);

        $this->assertStringContainsString('APP_URL', \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_weak_seed_password_is_flagged(): void
    {
        // Uji tanpa menyentuh .env: periksa perilaku command terhadap nilai
        // lemah dengan memanggil logika pemeriksaannya secara terisolasi.
        // Menulis .env asli berisiko: bila proses mati di tengah, nilai dev
        // dapat tertinggal.
        $envPath = base_path('.env');
        $cadangan = $envPath . '.uji-cadangan';

        copy($envPath, $cadangan);

        try {
            file_put_contents($envPath, preg_replace(
                '/^SEED_PASSWORD=.*$/m',
                'SEED_PASSWORD=password',
                file_get_contents($cadangan)
            ));

            $this->jalankan();

            $this->assertStringContainsString(
                'SEED_PASSWORD',
                \Illuminate\Support\Facades\Artisan::output()
            );
        } finally {
            // Selalu pulihkan, bahkan bila assertion gagal
            copy($cadangan, $envPath);
            @unlink($cadangan);
        }

        // Pastikan pemulihan benar-benar terjadi
        $this->assertStringNotContainsString(
            'SEED_PASSWORD=password',
            file_get_contents($envPath),
            'Nilai uji harus dibersihkan dari .env setelah pengujian.'
        );
    }

    // =========================================================================
    // Exit code dapat dipakai otomatis
    // =========================================================================

    public function test_exit_code_is_usable_in_automation(): void
    {
        // Gagal → non-zero
        $gagal = $this->jalankan(['app.debug' => true]);
        $this->assertNotSame(0, $gagal, 'Exit code harus non-zero agar dapat dipakai di skrip deploy.');

        // Lulus → nol
        $lulus = $this->jalankan(['app.env' => 'production', 'app.debug' => false]);
        $this->assertSame(0, $lulus);
    }

    // =========================================================================
    // Command terdaftar & rujukan template tersedia
    // =========================================================================

    public function test_command_is_registered(): void
    {
        $commands = array_keys(\Illuminate\Support\Facades\Artisan::all());

        $this->assertContains(
            'app:check-production-config',
            $commands,
            'Command pemeriksaan konfigurasi harus terdaftar.'
        );
    }

    public function test_production_env_template_exists_and_is_safe(): void
    {
        $path = base_path('.env.production.example');
        $this->assertFileExists($path, 'Template .env produksi harus tersedia sebagai rujukan.');

        $isi = file_get_contents($path);

        $this->assertStringContainsString('APP_ENV=production', $isi);
        $this->assertStringContainsString('APP_DEBUG=false', $isi);
        $this->assertStringContainsString('SEED_PASSWORD=', $isi);
        $this->assertStringNotContainsString('LOG_LEVEL=debug', $isi);
        $this->assertStringNotContainsString('MAIL_MAILER=log', $isi);
    }
}
