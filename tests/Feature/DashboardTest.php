<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $pemantau;
    protected User $opdDinkes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_full_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Rasio Pemulihan Finansial Kas Daerah');
        $response->assertSee('1. Distribusi per Sumber');
        $response->assertSee('2. Status Penyelesaian');
        $response->assertSee('Peringkat OPD Terbanyak Temuan');
        $response->assertSee('Dinas Kesehatan');
    }

    public function test_admin_sumber_can_view_scoped_dashboard(): void
    {
        $response = $this->actingAs($this->adminBpk)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Eksekutif & Monitoring');
    }

    public function test_pemantau_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->pemantau)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Rasio Pemulihan Finansial Kas Daerah');
    }

    public function test_opd_can_view_opd_scoped_dashboard(): void
    {
        $response = $this->actingAs($this->opdDinkes)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Dinas Kesehatan');
    }

    public function test_dashboard_can_be_filtered_by_opd_and_sumber(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard', [
            'sumber' => 'BPK',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Utama Pengawasan');
    }
}
