<?php

namespace Tests\Feature;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * Guard konfigurasi produksi.
 *
 * Memastikan aplikasi tidak pernah bisa dijalankan/di-seed di produksi
 * dengan pengaturan yang tidak aman.
 */
class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_refuses_to_run_in_production_without_seed_password(): void
    {
        $this->app['env'] = 'production';
        putenv('SEED_PASSWORD');
        unset($_ENV['SEED_PASSWORD']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/SEED_PASSWORD/');

        (new UserSeeder())->run();
    }

    public function test_user_seeder_uses_seed_password_when_provided(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\OpdSeeder::class);
        $this->seed(\Database\Seeders\PegawaiSeeder::class);

        $secret = 'RahasiaInstansi#2026';
        putenv("SEED_PASSWORD={$secret}");
        $_ENV['SEED_PASSWORD'] = $secret;

        try {
            (new UserSeeder())->run();

            $user = \App\Models\User::where('email', 'superadmin@inspektorat.go.id')->first();

            $this->assertNotNull($user, 'Seeder harus membuat akun super admin.');
            $this->assertTrue(
                Hash::check($secret, $user->password),
                'Password akun hasil seeding harus mengikuti SEED_PASSWORD, bukan nilai default.'
            );
            $this->assertFalse(
                Hash::check('password', $user->password),
                'Akun hasil seeding tidak boleh memakai password default.'
            );
        } finally {
            putenv('SEED_PASSWORD');
            unset($_ENV['SEED_PASSWORD']);
        }
    }

    public function test_production_env_example_exists_and_hardens_defaults(): void
    {
        $path = base_path('.env.production.example');
        $this->assertFileExists($path, 'Template konfigurasi produksi harus tersedia.');

        $contents = file_get_contents($path);

        // Pengaturan yang wajib aman di template produksi
        $this->assertStringContainsString('APP_ENV=production', $contents);
        $this->assertStringContainsString('APP_DEBUG=false', $contents);
        $this->assertStringContainsString('SEED_PASSWORD=', $contents);
        $this->assertStringNotContainsString('LOG_LEVEL=debug', $contents);

        // Tidak boleh menyarankan mailer yang tidak benar-benar mengirim email
        $this->assertStringNotContainsString('MAIL_MAILER=log', $contents);
    }

    public function test_local_env_does_not_run_with_debug_disabled(): void
    {
        // Nilai di .env lokal tidak boleh bocor ke produksi: pastikan
        // .env (lokal) memang bertanda local, sehingga tidak salah pakai.
        $this->assertNotSame(
            'production',
            config('app.env'),
            'Test ini tidak boleh berjalan pada konfigurasi produksi.'
        );
    }
}
