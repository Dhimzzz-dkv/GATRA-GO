<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'Administrator utama dengan akses penuh ke seluruh sistem',
                'sumber_pemeriksaan' => null,
            ],
            [
                'name' => 'admin_bpk',
                'display_name' => 'Admin BPK',
                'description' => 'Administrator untuk data pemeriksaan BPK',
                'sumber_pemeriksaan' => 'BPK',
            ],
            [
                'name' => 'admin_bpkp',
                'display_name' => 'Admin BPKP',
                'description' => 'Administrator untuk data pemeriksaan BPKP',
                'sumber_pemeriksaan' => 'BPKP',
            ],
            [
                'name' => 'admin_itjen',
                'display_name' => 'Admin ITJEN',
                'description' => 'Administrator untuk data pemeriksaan ITJEN',
                'sumber_pemeriksaan' => 'ITJEN',
            ],
            [
                'name' => 'admin_inspektorat',
                'display_name' => 'Admin Inspektorat',
                'description' => 'Administrator untuk data pemeriksaan Inspektorat',
                'sumber_pemeriksaan' => 'Inspektorat',
            ],
            [
                'name' => 'pemantau',
                'display_name' => 'Pemantau',
                'description' => 'Akses read-only untuk monitoring (Gubernur, Wakil Gubernur, Sekda, Inspektur)',
                'sumber_pemeriksaan' => null,
            ],
            [
                'name' => 'opd',
                'display_name' => 'OPD',
                'description' => 'Akses read-only untuk melihat data temuan OPD terkait',
                'sumber_pemeriksaan' => null,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
