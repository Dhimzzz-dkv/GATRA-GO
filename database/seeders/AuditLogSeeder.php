<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // with('pegawai') wajib: accessor User::official_name membaca relasi pegawai,
        // sehingga tanpa eager load akan terjadi lazy loading.
        $superAdmin = User::with('pegawai')->where('email', 'superadmin@inspektorat.go.id')->first();
        $adminBpk = User::with('pegawai')->where('email', 'admin.bpk@inspektorat.go.id')->first();
        $adminInsp = User::with('pegawai')->where('email', 'admin.inspektorat@inspektorat.go.id')->first();
        $opdDinkes = User::with('pegawai')->where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        $sampleLogs = [
            [
                'user_id' => $superAdmin?->id,
                'user_name' => $superAdmin?->official_name ?? 'Super Admin',
                'role_name' => 'Super Admin',
                'action' => 'LOGIN',
                'module' => 'AUTH',
                'description' => 'Super Admin berhasil login ke sistem.',
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(3),
            ],
            [
                'user_id' => $adminBpk?->id,
                'user_name' => $adminBpk?->official_name ?? 'Admin BPK',
                'role_name' => 'Admin Sumber (BPK)',
                'action' => 'CREATE',
                'module' => 'TEMUAN',
                'description' => 'Mencatat data temuan baru: 01/TEM-BPK/DINKES/2025.',
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(2),
            ],
            [
                'user_id' => $adminBpk?->id,
                'user_name' => $adminBpk?->official_name ?? 'Admin BPK',
                'role_name' => 'Admin Sumber (BPK)',
                'action' => 'SUBMIT',
                'module' => 'TEMUAN',
                'description' => 'Mengajukan data temuan 01/TEM-BPK/DINKES/2025 untuk persetujuan Super Admin.',
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(2)->addHours(1),
            ],
            [
                'user_id' => $superAdmin?->id,
                'user_name' => $superAdmin?->official_name ?? 'Super Admin',
                'role_name' => 'Super Admin',
                'action' => 'APPROVE',
                'module' => 'TEMUAN',
                'description' => 'Menyetujui data temuan 01/TEM-BPK/DINKES/2025.',
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDays(2)->addHours(3),
            ],
            [
                'user_id' => $opdDinkes?->id,
                'user_name' => $opdDinkes?->official_name ?? 'Akun OPD Dinas Kesehatan',
                'role_name' => 'Akun OPD',
                'action' => 'CREATE',
                'module' => 'TINDAK_LANJUT',
                'description' => 'Menyampaikan tindak lanjut setoran Rp 200.000.000 (No: STS-2025/06/001).',
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subDay(),
            ],
            [
                'user_id' => $adminInsp?->id,
                'user_name' => $adminInsp?->official_name ?? 'Admin Inspektorat',
                'role_name' => 'Admin Sumber (Inspektorat)',
                'action' => 'VERIFY',
                'module' => 'TINDAK_LANJUT',
                'description' => 'Memverifikasi SESUAI tindak lanjut setoran Rp 200.000.000 (No: STS-2025/06/001).',
                'ip_address' => '127.0.0.1',
                'created_at' => now()->subHours(6),
            ],
        ];

        foreach ($sampleLogs as $log) {
            AuditLog::create($log);
        }
    }
}
