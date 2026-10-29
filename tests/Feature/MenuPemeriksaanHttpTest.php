<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi menu "Pemeriksaan & SPT" lewat alur HTTP lengkap
 * (middleware, session, view sharing) — bukan sekadar memanggil controller.
 */
class MenuPemeriksaanHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pemeriksaan_index_returns_200_for_every_sidebar_role(): void
    {
        $roles = [
            'Super Admin' => 'superadmin@inspektorat.go.id',
            'Admin Inspektorat' => 'admin.inspektorat@inspektorat.go.id',
            'Admin BPK' => 'admin.bpk@inspektorat.go.id',
            'Admin BPKP' => 'admin.bpkp@inspektorat.go.id',
            'Admin ITJEN' => 'admin.itjen@inspektorat.go.id',
            'Pemantau' => 'pemantau@gorontaloprov.go.id',
            'OPD' => 'opd.dinkes@gorontaloprov.go.id',
        ];

        foreach ($roles as $label => $email) {
            $user = User::where('email', $email)->first();

            $response = $this->actingAs($user)->get('/pemeriksaan');

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Menu 'Pemeriksaan & SPT' gagal dibuka oleh {$label} (HTTP {$response->getStatusCode()})."
            );

            $response->assertSee('Pemeriksaan');
        }
    }

    public function test_pemeriksaan_index_renders_content_not_error_page(): void
    {
        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();

        $response = $this->actingAs($user)->get('/pemeriksaan');

        $response->assertOk();
        $response->assertDontSee('Whoops');
        $response->assertDontSee('Attempted to lazy load');
        $response->assertDontSee('Undefined variable');
        $response->assertDontSee('Server Error');
    }

    public function test_lazy_loading_protection_is_active_in_test_environment(): void
    {
        // Bila ini gagal, berarti strict mode dimatikan saat test dan
        // bug lazy loading tidak akan terdeteksi sebelum sampai produksi.
        $this->assertTrue(
            \Illuminate\Database\Eloquent\Model::preventsLazyLoading(),
            'preventLazyLoading harus aktif saat test agar perilakunya sama dengan produksi.'
        );
    }
}
