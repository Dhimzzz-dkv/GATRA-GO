<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pegawais = [
            [
                'nip' => '19690510 199503 1 002',
                'nama' => 'Misranda Nalole, S.E., M.Si., QGIA',
                'jabatan' => 'Inspektur Daerah Provinsi Gorontalo',
                'pangkat_golongan' => 'Pembina Utama Muda (IV/c)',
                'unit_bidang' => 'Pimpinan',
                'no_hp' => '0811-4321-001',
                'email' => 'inspektur@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'nip' => '19750814 200003 1 005',
                'nama' => 'Ahmad R. Gobel, S.E., Ak., CA',
                'jabatan' => 'Inspektur Pembantu Wilayah I',
                'pangkat_golongan' => 'Pembina Tk. I (IV/b)',
                'unit_bidang' => 'Irban Wilayah I',
                'no_hp' => '0812-4455-6677',
                'email' => 'ahmad.gobel@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'nip' => '19780321 200501 1 008',
                'nama' => 'Budi Santoso, S.STP., M.Si.',
                'jabatan' => 'Inspektur Pembantu Wilayah II',
                'pangkat_golongan' => 'Pembina (IV/a)',
                'unit_bidang' => 'Irban Wilayah II',
                'no_hp' => '0813-5566-7788',
                'email' => 'budi.santoso@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'nip' => '19810615 200801 2 004',
                'nama' => 'Sri Wahyuni, S.E., M.M.',
                'jabatan' => 'Inspektur Pembantu Wilayah III',
                'pangkat_golongan' => 'Pembina (IV/a)',
                'unit_bidang' => 'Irban Wilayah III',
                'no_hp' => '0812-9988-7766',
                'email' => 'sri.wahyuni@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'nip' => '19840210 200902 1 003',
                'nama' => 'Fahmi Usman, S.H., M.H.',
                'jabatan' => 'Inspektur Pembantu Khusus / Investigasi',
                'pangkat_golongan' => 'Pembina (IV/a)',
                'unit_bidang' => 'Irban Khusus',
                'no_hp' => '0821-3344-5566',
                'email' => 'fahmi.usman@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'nip' => '19860925 201101 1 007',
                'nama' => 'Rahmat Hidayat, S.E., CRMO',
                'jabatan' => 'Auditor Ahli Muda / Pengendali Teknis',
                'pangkat_golongan' => 'Penata Tk. I (III/d)',
                'unit_bidang' => 'Irban Wilayah I',
                'no_hp' => '0852-1122-3344',
                'email' => 'rahmat.hidayat@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'nip' => '19890412 201402 2 006',
                'nama' => 'Nurul Aini, S.Ak.',
                'jabatan' => 'Auditor Ahli Pertama',
                'pangkat_golongan' => 'Penata Muda Tk. I (III/b)',
                'unit_bidang' => 'Irban Wilayah II',
                'no_hp' => '0853-4455-6677',
                'email' => 'nurul.aini@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'nip' => '19910718 201503 1 002',
                'nama' => 'Mohamad Rizki, S.Kom.',
                'jabatan' => 'Pranata Komputer Ahli Pertama / Admin Sistem',
                'pangkat_golongan' => 'Penata Muda Tk. I (III/b)',
                'unit_bidang' => 'Sekretariat',
                'no_hp' => '0822-7788-9900',
                'email' => 'rizki@inspektorat.gorontaloprov.go.id',
                'is_active' => true,
            ],
        ];

        foreach ($pegawais as $pegawai) {
            Pegawai::updateOrCreate(
                ['nip' => $pegawai['nip']],
                $pegawai
            );
        }
    }
}
