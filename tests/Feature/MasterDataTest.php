<?php

namespace Tests\Feature;

use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $opdUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->opdUser = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
    }

    public function test_super_admin_can_access_opd_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('opd.index'));

        $response->assertStatus(200);
        $response->assertSee('Dinas Kesehatan');
        $response->assertSee('DINKES');
    }

    public function test_non_super_admin_cannot_access_opd_index(): void
    {
        $response = $this->actingAs($this->adminBpk)->get(route('opd.index'));
        $response->assertStatus(403);

        $responseOpd = $this->actingAs($this->opdUser)->get(route('opd.index'));
        $responseOpd->assertStatus(403);
    }

    public function test_super_admin_can_create_opd(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('opd.store'), [
            'kode' => 'DISKOMINFO',
            'nama' => 'Dinas Komunikasi, Informatika dan Statistik',
            'singkatan' => 'Diskominfo',
            'kepala_opd' => 'Dr. H. Stefen M., M.Kom.',
            'nip_kepala' => '19800101 200501 1 001',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('opd.index'));
        $this->assertDatabaseHas('opds', [
            'kode' => 'DISKOMINFO',
            'nama' => 'Dinas Komunikasi, Informatika dan Statistik',
        ]);
    }

    public function test_super_admin_can_toggle_opd_status(): void
    {
        $opd = Opd::first();
        $initialStatus = $opd->is_active;

        $response = $this->actingAs($this->superAdmin)->patch(route('opd.toggle-status', $opd));

        $response->assertRedirect();
        $this->assertDatabaseHas('opds', [
            'id' => $opd->id,
            'is_active' => ! $initialStatus,
        ]);
    }

    public function test_super_admin_can_access_pegawai_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('pegawai.index'));

        $response->assertStatus(200);
        $response->assertSee('Ahmad R. Gobel');
        $response->assertSee('Irban Wilayah I');
    }

    public function test_super_admin_can_create_pegawai(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('pegawai.store'), [
            'nip' => '19950101 202001 1 001',
            'nama' => 'Zulkifli Monoarfa, S.E.',
            'jabatan' => 'Auditor Pertama',
            'pangkat_golongan' => 'Penata Muda (III/a)',
            'unit_bidang' => 'Irban Wilayah III',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('pegawai.index'));
        $this->assertDatabaseHas('pegawais', [
            'nip' => '19950101 202001 1 001',
            'nama' => 'Zulkifli Monoarfa, S.E.',
        ]);
    }

    public function test_super_admin_can_access_user_index(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('user.index'));

        $response->assertStatus(200);
        $response->assertSee('admin.bpk@inspektorat.go.id');
        $response->assertSee('Super Administrator');
    }

    public function test_super_admin_can_create_user_linked_to_pegawai(): void
    {
        $pegawai = Pegawai::where('nip', '19910718 201503 1 002')->first();
        $adminRole = Role::where('name', 'admin_bpk')->first();

        $response = $this->actingAs($this->superAdmin)->post(route('user.store'), [
            'name' => 'Mohamad Rizki (Admin Test)',
            'email' => 'rizki.test@inspektorat.go.id',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'pegawai_id' => $pegawai->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('user.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'rizki.test@inspektorat.go.id',
            'pegawai_id' => $pegawai->id,
        ]);
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $response = $this->actingAs($this->superAdmin)->patch(route('user.toggle-status', $this->superAdmin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id,
            'is_active' => true,
        ]);
    }

    public function test_user_name_auto_fills_from_pegawai_when_empty_on_create(): void
    {
        $pegawai = Pegawai::where('nip', '19910718 201503 1 002')->first();
        $adminRole = Role::where('name', 'admin_bpk')->first();

        $response = $this->actingAs($this->superAdmin)->post(route('user.store'), [
            'name' => '', // empty name submitted
            'email' => 'auto.sync.test@inspektorat.go.id',
            'password' => 'password123',
            'role_id' => $adminRole->id,
            'pegawai_id' => $pegawai->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('user.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'auto.sync.test@inspektorat.go.id',
            'name' => $pegawai->nama,
            'pegawai_id' => $pegawai->id,
        ]);
    }

    public function test_user_name_auto_fills_from_pegawai_when_empty_on_update(): void
    {
        $pegawai = Pegawai::where('nip', '19910718 201503 1 002')->first();
        $adminRole = Role::where('name', 'admin_bpk')->first();

        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'update.sync@inspektorat.go.id',
            'role_id' => $adminRole->id,
            'pegawai_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('user.update', $user), [
            'name' => '', // empty name submitted
            'email' => 'update.sync@inspektorat.go.id',
            'role_id' => $adminRole->id,
            'pegawai_id' => $pegawai->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('user.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $pegawai->nama,
            'pegawai_id' => $pegawai->id,
        ]);
    }

    public function test_updating_pegawai_name_automatically_syncs_to_linked_user(): void
    {
        $pegawai = Pegawai::where('nip', '19910718 201503 1 002')->first();
        $adminRole = Role::where('name', 'admin_bpk')->first();

        $user = User::factory()->create([
            'name' => $pegawai->nama,
            'email' => 'pegawai.linked@inspektorat.go.id',
            'role_id' => $adminRole->id,
            'pegawai_id' => $pegawai->id,
            'is_active' => true,
        ]);

        // Update pegawai's name
        $response = $this->actingAs($this->superAdmin)->put(route('pegawai.update', $pegawai), [
            'nip' => $pegawai->nip,
            'nama' => 'Dhimaz Pramudya Lc Lasabang S. Kom (Updated)',
            'jabatan' => $pegawai->jabatan,
            'pangkat_golongan' => $pegawai->pangkat_golongan,
            'unit_bidang' => $pegawai->unit_bidang,
            'no_hp' => $pegawai->no_hp,
            'email' => $pegawai->email,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('pegawai.index'));

        // Linked user's name must be automatically updated
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Dhimaz Pramudya Lc Lasabang S. Kom (Updated)',
        ]);
    }
}

