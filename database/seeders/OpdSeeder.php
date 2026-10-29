<?php

namespace Database\Seeders;

use App\Models\Opd;
use Illuminate\Database\Seeder;

class OpdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opds = [
            [
                'kode' => 'DINKES',
                'nama' => 'Dinas Kesehatan Provinsi Gorontalo',
                'singkatan' => 'Dinas Kesehatan',
                'kepala_opd' => 'dr. Rosina Kiu',
                'nip_kepala' => '19710315 200212 2 003',
                'alamat' => 'Jl. Taman Pendidikan No. 36, Kota Gorontalo',
                'telepon' => '0435-822156',
                'email' => 'dinkes@gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'kode' => 'DIKBUD',
                'nama' => 'Dinas Pendidikan dan Kebudayaan Provinsi Gorontalo',
                'singkatan' => 'Dikbud',
                'kepala_opd' => 'Drs. Rusli Nusi, M.Pd.',
                'nip_kepala' => '19680814 199403 1 005',
                'alamat' => 'Jl. Brigjen Piola Isa No. 27, Kota Gorontalo',
                'telepon' => '0435-821214',
                'email' => 'dikbud@gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'kode' => 'BAPPPEDA',
                'nama' => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah Provinsi Gorontalo',
                'singkatan' => 'Bappeda',
                'kepala_opd' => 'Sofian Ibrahim, S.E., M.Si.',
                'nip_kepala' => '19741208 200003 1 004',
                'alamat' => 'Jl. Sapta Marga No. 1, Kota Gorontalo',
                'telepon' => '0435-821034',
                'email' => 'bappeda@gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'kode' => 'PUPR-PKP',
                'nama' => 'Dinas Pekerjaan Umum, Penataan Ruang, Perumahan dan Kawasan Permukiman Provinsi Gorontalo',
                'singkatan' => 'Dinas PUPR-PKP',
                'kepala_opd' => 'Ir. Aries Ardianto, S.T., M.T.',
                'nip_kepala' => '19730510 199803 1 007',
                'alamat' => 'Jl. Thayeb M. Gobel No. 100, Kota Gorontalo',
                'telepon' => '0435-821567',
                'email' => 'pupr@gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'kode' => 'BKD',
                'nama' => 'Badan Kepegawaian Daerah Provinsi Gorontalo',
                'singkatan' => 'BKD',
                'kepala_opd' => 'Zukri Surotinojo, S.Kom., M.Si.',
                'nip_kepala' => '19790622 200501 1 008',
                'alamat' => 'Kompleks Kantor Gubernur Gorontalo, Botu',
                'telepon' => '0435-827890',
                'email' => 'bkd@gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'kode' => 'RSUD-HAH',
                'nama' => 'RSUD dr. Hasri Ainun Habibie Provinsi Gorontalo',
                'singkatan' => 'RSUD dr. Hasri Ainun Habibie',
                'kepala_opd' => 'dr. Fitriyanto Rajak',
                'nip_kepala' => '19820210 201001 1 009',
                'alamat' => 'Jl. Kusno Danupoyo No. 1, Limboto',
                'telepon' => '0435-881234',
                'email' => 'rsud.ainun@gorontaloprov.go.id',
                'is_active' => true,
            ],
            [
                'kode' => 'DINSOS',
                'nama' => 'Dinas Sosial Provinsi Gorontalo',
                'singkatan' => 'Dinas Sosial',
                'kepala_opd' => 'M. Nadjamuddin, S.Sos., M.Si.',
                'nip_kepala' => '19700412 199603 1 003',
                'alamat' => 'Jl. Pangeran Hidayat No. 45, Kota Gorontalo',
                'telepon' => '0435-823412',
                'email' => 'dinsos@gorontaloprov.go.id',
                'is_active' => true,
            ],
        ];

        foreach ($opds as $opd) {
            Opd::updateOrCreate(
                ['kode' => $opd['kode']],
                $opd
            );
        }
    }
}
