<?php

namespace Database\Seeders;

use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Password default untuk akun hasil seeding.
     *
     * Di lingkungan non-produksi, nilai jatuh ke 'password' agar memudahkan
     * pengembangan. Di produksi, SEED_PASSWORD WAJIB diisi â€” seeder akan
     * berhenti dengan error agar tidak pernah membuat akun berpassword default.
     */
    private function seedPassword(): string
    {
        $configured = env('SEED_PASSWORD');

        if (! empty($configured)) {
            return $configured;
        }

        if (app()->environment('production')) {
            throw new RuntimeException(
                'SEED_PASSWORD belum diisi. Seeder tidak boleh dijalankan di produksi '
                . 'tanpa password eksplisit, karena akan membuat akun dengan kredensial default.'
            );
        }

        return 'password';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make($this->seedPassword());

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $adminBpkRole = Role::where('name', 'admin_bpk')->first();
        $adminBpkpRole = Role::where('name', 'admin_bpkp')->first();
        $adminItjenRole = Role::where('name', 'admin_itjen')->first();
        $adminInspektoratRole = Role::where('name', 'admin_inspektorat')->first();
        $pemantauRole = Role::where('name', 'pemantau')->first();
        $opdRole = Role::where('name', 'opd')->first();

        // Pegawais
        $pegawaiAhmad = Pegawai::where('nip', '19750814 200003 1 005')->first();
        $pegawaiBudi = Pegawai::where('nip', '19780321 200501 1 008')->first();
        $pegawaiSri = Pegawai::where('nip', '19810615 200801 2 004')->first();
        $pegawaiFahmi = Pegawai::where('nip', '19840210 200902 1 003')->first();
        $pegawaiInspektur = Pegawai::where('nip', '19690510 199503 1 002')->first();

        // OPDs
        $opdDinkes = Opd::where('kode', 'DINKES')->first();
        $opdDikbud = Opd::where('kode', 'DIKBUD')->first();

        $users = [
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@inspektorat.go.id',
                'password' => $password,
                'role_id' => $superAdminRole?->id,
                'pegawai_id' => null,
                'opd_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Admin BPK â€” Ahmad',
                'email' => 'admin.bpk@inspektorat.go.id',
                'password' => $password,
                'role_id' => $adminBpkRole?->id,
                'pegawai_id' => $pegawaiAhmad?->id,
                'opd_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Admin BPKP â€” Budi',
                'email' => 'admin.bpkp@inspektorat.go.id',
                'password' => $password,
                'role_id' => $adminBpkpRole?->id,
                'pegawai_id' => $pegawaiBudi?->id,
                'opd_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Admin ITJEN â€” Sri Wahyuni',
                'email' => 'admin.itjen@inspektorat.go.id',
                'password' => $password,
                'role_id' => $adminItjenRole?->id,
                'pegawai_id' => $pegawaiSri?->id,
                'opd_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Admin Inspektorat â€” Fahmi',
                'email' => 'admin.inspektorat@inspektorat.go.id',
                'password' => $password,
                'role_id' => $adminInspektoratRole?->id,
                'pegawai_id' => $pegawaiFahmi?->id,
                'opd_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Pemantau Pimpinan',
                'email' => 'pemantau@gorontaloprov.go.id',
                'password' => $password,
                'role_id' => $pemantauRole?->id,
                'pegawai_id' => $pegawaiInspektur?->id,
                'opd_id' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Akun OPD Dinas Kesehatan',
                'email' => 'opd.dinkes@gorontaloprov.go.id',
                'password' => $password,
                'role_id' => $opdRole?->id,
                'pegawai_id' => null,
                'opd_id' => $opdDinkes?->id,
                'is_active' => true,
            ],
            [
                'name' => 'Akun OPD Dikbud',
                'email' => 'opd.dikbud@gorontaloprov.go.id',
                'password' => $password,
                'role_id' => $opdRole?->id,
                'pegawai_id' => null,
                'opd_id' => $opdDikbud?->id,
                'is_active' => true,
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                $u
            );
        }
    }
}
