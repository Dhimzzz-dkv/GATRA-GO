<?php

namespace Tests\Feature;

use App\Models\Lhp;
use App\Models\Opd;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemeriksaanTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminInsp;
    protected User $pemantau;
    protected User $opdDinkes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
        $this->pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $this->opdDinkes = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();
    }

    public function test_super_admin_can_view_all_examinations(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.index'));

        $response->assertStatus(200);
        $response->assertSee('05/SPT/BPK-GOR/02/2025');
        $response->assertSee('090/145/ITJEN-KEMENDAGRI/2024');
        $response->assertDontSee('ST-112/PW31/3/2025');
        $response->assertDontSee('700/ITPROV/042/SPT/2025');
    }

    public function test_super_admin_can_view_examination_detail(): void
    {
        $pemeriksaan = Pemeriksaan::where('status_pengajuan', '!=', 'DRAFT')->first();
        $response = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.show', $pemeriksaan));

        $response->assertStatus(200);
        $response->assertSee($pemeriksaan->nomor_spt);
    }

    public function test_admin_bpk_only_sees_bpk_examinations(): void
    {
        $response = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.index'));

        $response->assertStatus(200);
        $response->assertSee('05/SPT/BPK-GOR/02/2025');
        $response->assertDontSee('ST-112/PW31/3/2025'); // BPKP
        $response->assertDontSee('090/145/ITJEN-KEMENDAGRI/2024'); // ITJEN
    }

    public function test_admin_inspektorat_can_create_examination_with_spt_upload(): void
    {
        Storage::fake('local');

        $dinkes = Opd::where('kode', 'DINKES')->first();
        $file = UploadedFile::fake()->create('SPT_INSP_2026.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Rutin TA 2026 pada Dinas Kesehatan',
            'nomor_spt' => '01/SPT/INSP-TEST/2026',
            'tanggal_spt' => '2026-02-01',
            'tanggal_mulai' => '2026-02-05',
            'tanggal_selesai' => '2026-02-25',
            'dokumen_spt' => $file,
        ]);

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '01/SPT/INSP-TEST/2026')->first();
        $this->assertNotNull($pemeriksaan);
        $this->assertEquals('DRAFT', $pemeriksaan->status_pengajuan);
        $this->assertEquals('Inspektorat', $pemeriksaan->sumber_pemeriksaan);
        $this->assertEquals($this->adminInsp->id, $pemeriksaan->created_by);

        Storage::disk('local')->assertExists($pemeriksaan->dokumen_spt);
        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));
    }

    public function test_admin_inspektorat_can_create_unified_spt_and_lhp_in_one_form(): void
    {
        Storage::fake('local');

        $dinkes = Opd::where('kode', 'DINKES')->first();
        $fileSpt = UploadedFile::fake()->create('SPT_INSP_2026.pdf', 500, 'application/pdf');
        $fileLhp = UploadedFile::fake()->create('LHP_INSP_2026.pdf', 800, 'application/pdf');

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Terpadu TA 2026',
            'nomor_spt' => '02/SPT/INSP-UNIFIED/2026',
            'tanggal_spt' => '2026-02-01',
            'dokumen_spt' => $fileSpt,
            // Data LHP di form yang sama
            'nomor_lhp' => '700/02/LHP-UNIFIED/2026',
            'tanggal_lhp' => '2026-02-28',
            'judul_lhp' => 'Laporan Hasil Pemeriksaan Terpadu TA 2026',
            'ringkasan_lhp' => 'Ringkasan hasil temuan kepatuhan dan keandalan sistem akuntansi.',
            'dokumen_lhp' => $fileLhp,
        ]);

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '02/SPT/INSP-UNIFIED/2026')->first();
        $this->assertNotNull($pemeriksaan);

        $lhp = $pemeriksaan->lhps()->first();
        $this->assertNotNull($lhp);
        $this->assertEquals('700/02/LHP-UNIFIED/2026', $lhp->nomor_lhp);
        $this->assertEquals('Laporan Hasil Pemeriksaan Terpadu TA 2026', $lhp->judul_lhp);
        $this->assertEquals('Ringkasan hasil temuan kepatuhan dan keandalan sistem akuntansi.', $lhp->ringkasan);

        Storage::disk('local')->assertExists($pemeriksaan->dokumen_spt);
        Storage::disk('local')->assertExists($lhp->dokumen_lhp);
        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));
    }

    public function test_admin_inspektorat_can_create_spt_with_dynamic_multi_lhp_and_multi_tindak_lanjut(): void
    {
        Storage::fake('local');

        $dinkes = Opd::where('kode', 'DINKES')->first();
        $fileSpt = UploadedFile::fake()->create('SPT_MULTI.pdf', 500, 'application/pdf');
        $fileLhp1 = UploadedFile::fake()->create('LHP_1.pdf', 600, 'application/pdf');
        $fileLhp2 = UploadedFile::fake()->create('LHP_2.pdf', 700, 'application/pdf');
        $fileTl1 = UploadedFile::fake()->create('Bukti_STS_1.pdf', 400, 'application/pdf');
        $fileTl2 = UploadedFile::fake()->create('Bukti_STS_2.pdf', 450, 'application/pdf');

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Rutin Multi-LHP TA 2026',
            'nomor_spt' => '03/SPT/MULTI-LHP/2026',
            'tanggal_spt' => '2026-02-01',
            'dokumen_spt' => $fileSpt,

            // 2 LHP sekaligus dalam 1 request form
            //
            // Format ini mengikuti field yang benar-benar dikirim form produksi
            // (lihat resources/views/pemeriksaan/create.blade.php):
            // temuan dicatat pada `temuans_administrasi` / `temuans_kerugian`,
            // BUKAN pada level LHP.
            //
            // Sebelumnya test ini memakai format lama (`unsur_administrasi` dan
            // `nilai_kerugian` di level LHP) yang sudah tidak didukung, sehingga
            // tidak ada temuan yang terbuat dan test gagal.
            'lhps' => [
                0 => [
                    'nomor_lhp' => '700/01/LHP-REG/2026',
                    'tanggal_lhp' => '2026-02-15',
                    'judul_lhp' => 'LHP Kepatuhan Pengelolaan Kas',
                    'ringkasan' => 'Terdapat kelemahan administrasi dan kas tekor.',
                    'opd_ids' => [$dinkes->id],
                    'dokumen_lhp' => $fileLhp1,
                    'temuans_administrasi' => [
                        0 => [
                            'uraian' => 'Buku kas umum belum ditutup tiap bulan.',
                            'penyebab' => 'Petugas bendahara belum menertibkan penutupan kas.',
                            'rekomendasi' => 'Menertibkan penutupan buku kas umum setiap bulan.',
                        ],
                    ],
                    'temuans_kerugian' => [
                        0 => [
                            'uraian' => 'Kas tekor pada pengelolaan kas daerah.',
                            'kategori_kerugian' => 'Kerugian Daerah',
                            'jenis_kerugian' => 'Internal',
                            'nilai_kerugian' => '15000000',
                            'penyebab' => 'Pengeluaran tanpa bukti sah.',
                            'rekomendasi' => 'Setor ke kasda Rp 15.000.000',
                        ],
                    ],
                ],
                1 => [
                    'nomor_lhp' => '700/02/LHP-ASET/2026',
                    'tanggal_lhp' => '2026-02-20',
                    'judul_lhp' => 'LHP Pengelolaan BMD dan Kendaraan Dinas',
                    'ringkasan' => 'Aset belum terinventarisasi dengan baik.',
                    'opd_ids' => [$dinkes->id],
                    'dokumen_lhp' => $fileLhp2,
                    'temuans_administrasi' => [
                        0 => [
                            'uraian' => 'KIB A belum diperbarui.',
                            'penyebab' => 'Belum ada jadwal pemutakhiran KIB.',
                            'rekomendasi' => 'Melakukan inventarisasi dan pemutakhiran KIB.',
                        ],
                    ],
                    'temuans_kerugian' => [
                        0 => [
                            'uraian' => 'Aset belum tercatat pada KIB.',
                            'kategori_kerugian' => 'Kerugian Daerah',
                            'jenis_kerugian' => 'Internal',
                            'nilai_kerugian' => '5000000',
                            'penyebab' => 'Aset tidak terinventarisasi.',
                            'rekomendasi' => 'Lakukan inventarisasi fisik.',
                        ],
                    ],
                ],
            ],

            // 2 Tindak Lanjut terkait LHP masing-masing
            'tindak_lanjuts' => [
                0 => [
                    'lhp_index' => 0, // Terkait LHP #1
                    'tanggal_tindak_lanjut' => '2026-03-01',
                    'uraian_tindak_lanjut' => 'Penyetoran tahap 1 sebesar Rp 10.000.000',
                    'nomor_bukti_setor' => 'STS-001',
                    'tanggal_setor' => '2026-03-01',
                    'jumlah_setor' => '10.000.000',
                    'dokumen_bukti' => $fileTl1,
                ],
                1 => [
                    'lhp_index' => 1, // Terkait LHP #2
                    'tanggal_tindak_lanjut' => '2026-03-05',
                    'uraian_tindak_lanjut' => 'Penyetoran pelunasan aset Rp 5.000.000',
                    'nomor_bukti_setor' => 'STS-002',
                    'tanggal_setor' => '2026-03-05',
                    'jumlah_setor' => '5.000.000',
                    'dokumen_bukti' => $fileTl2,
                ],
            ],
        ]);

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '03/SPT/MULTI-LHP/2026')->first();
        $this->assertNotNull($pemeriksaan);

        // Assert 2 LHPs created
                $pemeriksaan->loadMissing('lhps');
        $this->assertCount(2, $pemeriksaan->lhps);
        $lhp1 = $pemeriksaan->lhps()->where('nomor_lhp', '700/01/LHP-REG/2026')->first();
        $lhp2 = $pemeriksaan->lhps()->where('nomor_lhp', '700/02/LHP-ASET/2026')->first();
        $this->assertNotNull($lhp1);
        $this->assertNotNull($lhp2);

        // Assert Temuans created for each LHP
        $this->assertNotNull($lhp1->temuans()->first());
        $this->assertEquals(15000000, $lhp1->temuans()->first()->nilai_kerugian);
        $this->assertNotNull($lhp2->temuans()->first());
        $this->assertEquals(5000000, $lhp2->temuans()->first()->nilai_kerugian);

        // Assert Tindak Lanjuts linked to LHP
                $lhp1->loadMissing('tindakLanjuts');
        $this->assertCount(1, $lhp1->tindakLanjuts);
        $this->assertEquals(10000000, $lhp1->tindakLanjuts()->first()->jumlah_setor);
                $lhp2->loadMissing('tindakLanjuts');
        $this->assertCount(1, $lhp2->tindakLanjuts);
        $this->assertEquals(5000000, $lhp2->tindakLanjuts()->first()->jumlah_setor);

        // Assert all files exist
        Storage::disk('local')->assertExists($pemeriksaan->dokumen_spt);
        Storage::disk('local')->assertExists($lhp1->dokumen_lhp);
        Storage::disk('local')->assertExists($lhp2->dokumen_lhp);
        Storage::disk('local')->assertExists($lhp1->tindakLanjuts()->first()->dokumen_bukti);
        Storage::disk('local')->assertExists($lhp2->tindakLanjuts()->first()->dokumen_bukti);

        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));
    }

    public function test_admin_inspektorat_can_create_spt_with_lhp_having_multiple_temuans(): void
    {
        Storage::fake('local');

        $dinkes = Opd::where('kode', 'DINKES')->first();
        $fileSpt = UploadedFile::fake()->create('SPT_TEMUAN_MULTI.pdf', 500, 'application/pdf');
        $fileLhp = UploadedFile::fake()->create('LHP_WITH_TEMUANS.pdf', 600, 'application/pdf');

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul' => 'Pemeriksaan dengan Banyak Temuan TA 2026',
            'nomor_spt' => '04/SPT/MULTI-TEMUAN/2026',
            'tanggal_spt' => '2026-02-01',
            'dokumen_spt' => $fileSpt,

            'lhps' => [
                0 => [
                    'nomor_lhp' => '700/01/LHP-MULTI-TEMUAN/2026',
                    'tanggal_lhp' => '2026-02-15',
                    'judul_lhp' => 'LHP Temuan Kompleks',
                    'ringkasan' => 'Ditemukan beberapa ketidaksesuaian administrasi dan finansial.',
                    'dokumen_lhp' => $fileLhp,

                    // Banyak Temuan di LHP yang sama
                    'temuans' => [
                        0 => [
                            'judul_temuan' => 'Temuan A: Honorarium Ganda',
                            'unsur_administrasi' => 'SK penetapan tim pelaksana tumpang tindih.',
                            'nilai_kerugian' => '8.500.000',
                            'rekomendasi' => 'Setor kelebihan bayar Rp 8.500.000 ke Kas Daerah.',
                        ],
                        1 => [
                            'judul_temuan' => 'Temuan B: Kekurangan Volume Pekerjaan',
                            'unsur_administrasi' => 'Laporan progress pekerjaan tidak diverifikasi PPK.',
                            'nilai_kerugian' => '22.000.000',
                            'rekomendasi' => 'Tuntut ganti rugi kepada pihak ketiga penyedia jasa.',
                        ],
                    ],
                ],
            ],
        ]);

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '04/SPT/MULTI-TEMUAN/2026')->first();
        $this->assertNotNull($pemeriksaan);

        $lhp = $pemeriksaan->lhps()->first();
        $this->assertNotNull($lhp);

        // Assert 2 Temuan records created under this single LHP
                $lhp->loadMissing('temuans');
        $this->assertCount(2, $lhp->temuans);
        $temuanA = $lhp->temuans()->where('judul_temuan', 'Temuan A: Honorarium Ganda')->first();
        $temuanB = $lhp->temuans()->where('judul_temuan', 'Temuan B: Kekurangan Volume Pekerjaan')->first();
        $this->assertNotNull($temuanA);
        $this->assertNotNull($temuanB);
        $this->assertEquals(8500000, $temuanA->nilai_kerugian);
        $this->assertEquals(22000000, $temuanB->nilai_kerugian);

        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));
    }

    public function test_admin_bpk_cannot_create_examinations(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();

        $response = $this->actingAs($this->adminBpk)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'BPK',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'nomor_spt' => 'TEST-BPK-UNAUTH',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_edit_submitted_examination(): void
    {
        $pemeriksaanBpk = Pemeriksaan::where('nomor_spt', '05/SPT/BPK-GOR/02/2025')->first();
        $pemeriksaanBpk->update(['status_pengajuan' => 'MENUNGGU PERSETUJUAN']);

        $response = $this->actingAs($this->adminBpk)->put(route('pemeriksaan.update', $pemeriksaanBpk), [
            'nomor_spt' => '05/SPT/BPK-GOR/MODIFIED',
            'opd_id' => $pemeriksaanBpk->opd_id,
            'tahun' => 2025,
            'sumber_pemeriksaan' => 'BPK',
        ]);

        $response->assertStatus(403);
    }

    public function test_opd_cannot_create_examinations(): void
    {
        $response = $this->actingAs($this->opdDinkes)->get(route('pemeriksaan.create'));
        $response->assertStatus(403);
    }

    public function test_pemantau_cannot_create_examinations(): void
    {
        $response = $this->actingAs($this->pemantau)->get(route('pemeriksaan.create'));
        $response->assertStatus(403);
    }

    public function test_user_can_download_spt_file(): void
    {
        Storage::fake('local');

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '05/SPT/BPK-GOR/02/2025')->first();
        $filePath = 'spt/test_spt.pdf';
        Storage::disk('local')->put($filePath, 'fake pdf content');

        $pemeriksaan->update([
            'dokumen_spt' => $filePath,
            'nama_dokumen_spt_asli' => 'SPT_BPK_05.pdf',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.download-spt', $pemeriksaan));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_admin_inspektorat_can_create_spt_without_opd_and_lhp_with_multiple_target_opds_via_pivot(): void
    {
        Storage::fake('local');

        $dinkes = Opd::where('kode', 'DINKES')->first();
        $dikbud = Opd::where('kode', 'DIKBUD')->first() ?? Opd::create(['kode' => 'DIKBUD', 'nama' => 'Dinas Pendidikan dan Kebudayaan', 'is_active' => true]);
        $pupr = Opd::where('kode', 'PUPR')->first() ?? Opd::create(['kode' => 'PUPR', 'nama' => 'Dinas PUPR', 'is_active' => true]);
        $dinsos = Opd::where('kode', 'DINSOS')->first() ?? Opd::create(['kode' => 'DINSOS', 'nama' => 'Dinas Sosial', 'is_active' => true]);

        $fileSpt = UploadedFile::fake()->create('SPT_MULTI_OPD.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Gabungan Lintas OPD TA 2026',
            'nomor_spt' => '05/SPT/GABUNGAN-OPD/2026',
            'tanggal_spt' => '2026-02-01',
            'dokumen_spt' => $fileSpt,

            // 2 LHP dengan masing-masing multiple target OPD
            'lhps' => [
                0 => [
                    'nomor_lhp' => '700/01/LHP-TRI-OPD/2026',
                    'tanggal_lhp' => '2026-02-15',
                    'judul_lhp' => 'LHP Pengelolaan Hibah dan Bansos',
                    'opd_ids' => [$dinkes->id, $dikbud->id, $pupr->id],
                    'ringkasan' => 'Evaluasi pengelolaan dana hibah.',
                    'unsur_administrasi' => 'Laporan pertanggungjawaban hibah terlambat.',
                    'nilai_kerugian' => '25.000.000',
                    'rekomendasi' => 'Tegur penerima hibah dan tertibkan LPJ.',
                ],
                1 => [
                    'nomor_lhp' => '700/02/LHP-DINSOS/2026',
                    'tanggal_lhp' => '2026-02-20',
                    'judul_lhp' => 'LHP Penyaluran Logistik Bencana',
                    'opd_ids' => [$dinsos->id],
                    'ringkasan' => 'Distribusi bantuan logistik belum terekap.',
                ],
            ],

            'tindak_lanjuts' => [
                0 => [
                    'lhp_index' => 0,
                    'tanggal_tindak_lanjut' => '2026-03-01',
                    'uraian_tindak_lanjut' => 'Pemberian teguran dan penyelesaian LPJ hibah',
                ],
            ],
        ]);

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '05/SPT/GABUNGAN-OPD/2026')->first();
        $this->assertNotNull($pemeriksaan);

        // Assert 2 LHPs exist
                $pemeriksaan->loadMissing('lhps');
        $this->assertCount(2, $pemeriksaan->lhps);

        $lhp1 = $pemeriksaan->lhps()->where('nomor_lhp', '700/01/LHP-TRI-OPD/2026')->first();
        $lhp2 = $pemeriksaan->lhps()->where('nomor_lhp', '700/02/LHP-DINSOS/2026')->first();

        // Assert Many-to-Many OPD Target via lhp_opd
                $lhp1->loadMissing('opds');
        $this->assertCount(3, $lhp1->opds);
        $this->assertTrue($lhp1->opds->contains('id', $dinkes->id));
        $this->assertTrue($lhp1->opds->contains('id', $dikbud->id));
        $this->assertTrue($lhp1->opds->contains('id', $pupr->id));

                $lhp2->loadMissing('opds');
        $this->assertCount(1, $lhp2->opds);
        $this->assertTrue($lhp2->opds->contains('id', $dinsos->id));

        // Assert database pivot table
        $this->assertDatabaseHas('lhp_opd', ['lhp_id' => $lhp1->id, 'opd_id' => $dinkes->id]);
        $this->assertDatabaseHas('lhp_opd', ['lhp_id' => $lhp1->id, 'opd_id' => $dikbud->id]);
        $this->assertDatabaseHas('lhp_opd', ['lhp_id' => $lhp1->id, 'opd_id' => $pupr->id]);
        $this->assertDatabaseHas('lhp_opd', ['lhp_id' => $lhp2->id, 'opd_id' => $dinsos->id]);

        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));
    }

    public function test_can_create_spt_with_lhp_having_multi_administrasi_and_kerugian_temuans(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Rutin BOK Dinkes TA 2026',
            'nomor_spt' => '06/SPT/MULTI-UNSUR/2026',
            'tanggal_spt' => '2026-03-01',

            'lhps' => [
                0 => [
                    'nomor_lhp' => '700/01/LHP-BOK/2026',
                    'tanggal_lhp' => '2026-03-10',
                    'judul_lhp' => 'LHP Pengelolaan BOK Dinkes',
                    'opd_ids' => [$dinkes->id],
                    'ringkasan' => 'Ditemukan kelemahan SOP dan kelebihan pembayaran belanja',

                    // 2 Temuan Administrasi
                    'temuans_administrasi' => [
                        0 => [
                            'uraian' => 'SOP Penatausahaan SPJ BOK belum ditetapkan secara formal.',
                            'rekomendasi' => 'Menyusun dan menetapkan SOP definitif.',
                        ],
                        1 => [
                            'uraian' => 'Arsip bukti pendukung belanja ATK belum bernomor urut.',
                            'rekomendasi' => 'Menertibkan penomoran dokumen arsip.',
                        ],
                    ],

                    // 1 Temuan Kerugian Daerah
                    'temuan_kerugian' => [
                        'uraian' => 'Kelebihan pembayaran honorarium pendamping BOK.',
                        'nilai_kerugian' => '15.000.000',
                        'rekomendasi' => 'Menyetorkan kelebihan honor Rp 15.000.000 ke Kas Daerah.',
                    ],
                ],
            ],
        ]);

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '06/SPT/MULTI-UNSUR/2026')->first();
        $this->assertNotNull($pemeriksaan);

        $lhp = $pemeriksaan->lhps()->first();
        $this->assertNotNull($lhp);

        // Assert 3 Temuans created (2 administrasi + 1 kerugian)
                $lhp->loadMissing('temuans');
        $temuans = $lhp->temuans;
        $this->assertCount(3, $temuans);

        $admTemuans = $temuans->where('ada_unsur_administrasi', true);
        $this->assertCount(2, $admTemuans);
        $this->assertEquals(0, $admTemuans->first()->nilai_kerugian);

        $kgTemuans = $temuans->where('ada_unsur_kerugian', true);
        $this->assertCount(1, $kgTemuans);
        $this->assertEquals(15000000.00, $kgTemuans->first()->nilai_kerugian);
        $this->assertEquals('Menyetorkan kelebihan honor Rp 15.000.000 ke Kas Daerah.', $kgTemuans->first()->rekomendasi);

        // Check LHP aggregation
        $this->assertEquals(15000000.00, $lhp->total_kerugian);
        $this->assertEquals(0, $lhp->total_disetor);
        $this->assertEquals('Belum Ditindaklanjuti', $lhp->status_penyelesaian);

        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));
    }

    public function test_admin_inspektorat_can_edit_spt_with_multiple_lhps_and_temuans(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $dikbud = Opd::where('kode', 'DIKBUD')->first();
        $pegawai1 = \App\Models\Pegawai::first();

        // Create initial draft SPT
        $pemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'Inspektorat',
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Awal TA 2026',
            'nomor_spt' => '07/SPT/EDIT-TEST/2026',
            'tanggal_spt' => '2026-03-01',
            'status_pengajuan' => 'DRAFT',
            'created_by' => $this->adminInsp->id,
        ]);

        $lhp1 = Lhp::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'nomor_lhp' => '700/01/LHP-EDIT-1/2026',
            'tanggal_lhp' => '2026-03-05',
            'judul_lhp' => 'LHP Awal 1',
            'created_by' => $this->adminInsp->id,
        ]);
        $lhp1->opds()->sync([$dinkes->id]);

        $admTemuan1 = Temuan::create([
            'pemeriksaan_id' => $pemeriksaan->id,
            'lhp_id' => $lhp1->id,
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => $dinkes->id,
            'tahun' => 2026,
            'judul_temuan' => 'Temuan Adm Awal',
            'uraian_temuan' => 'Uraian awal',
            'ada_unsur_administrasi' => true,
            'ada_unsur_kerugian' => false,
            'rekomendasi' => 'Rekomendasi awal',
            'status_pengajuan' => 'DRAFT',
            'status_penyelesaian' => 'Belum Ditindaklanjuti',
            'created_by' => $this->adminInsp->id,
        ]);

        // Submit edit with:
        // 1. Updated SPT title & tim pemeriksa
        // 2. Updated LHP #1 with 2 target OPDs and updated temuan
        // 3. Newly added LHP #2 with Kerugian temuan
        $response = $this->actingAs($this->adminInsp)->put(route('pemeriksaan.update', $pemeriksaan), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Hasil Pembaruan TA 2026',
            'nomor_spt' => '07/SPT/EDIT-TEST/2026',
            'tanggal_spt' => '2026-03-02',
            'nama_tim' => 'Tim Pemeriksa Khusus',

            'tim_pemeriksa' => [
                0 => [
                    'pegawai_id' => $pegawai1->id,
                    'peran_tim' => 'Ketua Tim',
                ],
            ],

            'lhps' => [
                0 => [
                    'id' => $lhp1->id,
                    'nomor_lhp' => '700/01/LHP-EDIT-1-REV/2026',
                    'tanggal_lhp' => '2026-03-06',
                    'judul_lhp' => 'LHP 1 Diperbarui',
                    'opd_ids' => [$dinkes->id, $dikbud->id],
                    'ringkasan' => 'Ringkasan LHP 1 diperbarui',

                    'temuans_administrasi' => [
                        0 => [
                            'id' => $admTemuan1->id,
                            'uraian' => 'Uraian diperbarui dengan dokumen lengkap',
                            'rekomendasi' => 'Rekomendasi perbaikan SOP',
                        ],
                    ],
                ],
                1 => [
                    'nomor_lhp' => '700/02/LHP-EDIT-2/2026',
                    'tanggal_lhp' => '2026-03-07',
                    'judul_lhp' => 'LHP 2 Ditambahkan saat Edit',
                    'opd_ids' => [$dikbud->id],
                    'ringkasan' => 'Ditemukan kelebihan belanja aset',

                    'temuan_kerugian' => [
                        'uraian' => 'Kelebihan pembayaran belanja laptop',
                        'nilai_kerugian' => '8.500.000',
                        'rekomendasi' => 'Setorkan Rp 8.500.000 ke kas daerah',
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));

        // Assert SPT updated
        $freshPemeriksaan = $pemeriksaan->fresh();
        $this->assertEquals('Pemeriksaan Hasil Pembaruan TA 2026', $freshPemeriksaan->judul);
        $this->assertEquals('Tim Pemeriksa Khusus', $freshPemeriksaan->nama_tim);
                $freshPemeriksaan->loadMissing('timPemeriksas');
        $this->assertCount(1, $freshPemeriksaan->timPemeriksas);
        $this->assertEquals($pegawai1->id, $freshPemeriksaan->timPemeriksas->first()->pegawai_id);

        // Assert 2 LHPs exist
                $freshPemeriksaan->loadMissing('lhps');
        $this->assertCount(2, $freshPemeriksaan->lhps);

        // Assert LHP 1 updated & multi-OPD
        $freshLhp1 = $lhp1->fresh();
        $this->assertEquals('700/01/LHP-EDIT-1-REV/2026', $freshLhp1->nomor_lhp);
                $freshLhp1->loadMissing('opds');
        $this->assertCount(2, $freshLhp1->opds);
        $this->assertEquals('Uraian diperbarui dengan dokumen lengkap', $admTemuan1->fresh()->uraian_temuan);

        // Assert LHP 2 created with kerugian temuan
        $lhp2 = $freshPemeriksaan->lhps()->where('nomor_lhp', '700/02/LHP-EDIT-2/2026')->first();
        $this->assertNotNull($lhp2);
                $lhp2->loadMissing('opds');
        $this->assertCount(1, $lhp2->opds);
        $this->assertEquals(8500000.00, $lhp2->temuans()->first()->nilai_kerugian);
    }

    public function test_only_admin_inspektorat_and_super_admin_can_create_spt(): void
    {
        // 1. Admin Inspektorat CAN access create form
        $resInsp = $this->actingAs($this->adminInsp)->get(route('pemeriksaan.create'));
        $resInsp->assertStatus(200);

        // 2. Super Admin CAN access create form
        $resSuper = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.create'));
        $resSuper->assertStatus(200);

        // 3. Admin BPK accessing pemeriksaan.create is redirected to lhp.create (tidak input SPT, hanya LHP)
        $resBpk = $this->actingAs($this->adminBpk)->get(route('pemeriksaan.create'));
        $resBpk->assertRedirect(route('lhp.create'));

        $resBpkLhp = $this->actingAs($this->adminBpk)->get(route('lhp.create'));
        $resBpkLhp->assertStatus(200);

        // 4. Pemantau CANNOT access create form (403)
        $resPemantau = $this->actingAs($this->pemantau)->get(route('pemeriksaan.create'));
        $resPemantau->assertStatus(403);

        // 5. OPD CANNOT access create form (403)
        $resOpd = $this->actingAs($this->opdDinkes)->get(route('pemeriksaan.create'));
        $resOpd->assertStatus(403);
    }

    public function test_admin_bpk_cannot_post_spt(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();

        $response = $this->actingAs($this->adminBpk)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'judul' => 'Uji Coba SPT BPK Terlarang',
            'nomor_spt' => '999/SPT/BPK/2026',
            'tanggal_spt' => '2026-03-01',
            'opd_ids' => [$dinkes->id],
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('pemeriksaans', ['nomor_spt' => '999/SPT/BPK/2026']);
    }

    public function test_lhp_index_page_is_accessible(): void
    {
        // Admin BPK can access LHP index
        $resBpk = $this->actingAs($this->adminBpk)->get(route('lhp.index'));
        $resBpk->assertStatus(200);
        $resBpk->assertSee('Laporan Hasil Pemeriksaan (LHP)');

        // Super Admin can access LHP index
        $resSuper = $this->actingAs($this->superAdmin)->get(route('lhp.index'));
        $resSuper->assertStatus(200);
        $resSuper->assertSee('Total Dokumen LHP');
    }

    public function test_admin_can_create_and_update_temuan_administrasi_with_penyebab(): void
    {
        Storage::fake('local');
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $fileSpt = UploadedFile::fake()->create('SPT_ADM.pdf', 300, 'application/pdf');

        // 1. Create pemeriksaan with temuan administrasi containing penyebab
        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => $dinkes->id,
            'opd_ids' => [$dinkes->id],
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Administrasi dengan Penyebab Laporan',
            'nomor_spt' => '04/SPT/INSP-ADM-PENYEBAB/2026',
            'tanggal_spt' => '2026-03-01',
            'dokumen_spt' => $fileSpt,
            'lhps' => [
                0 => [
                    'nomor_lhp' => '700/04/LHP-ADM/2026',
                    'tanggal_lhp' => '2026-03-15',
                    'judul_lhp' => 'LHP Uji Kepatuhan Administrasi',
                    'ringkasan' => 'Terdapat kelemahan administrasi SPJ.',
                    'has_administrasi' => '1',
                    'temuans_administrasi' => [
                        0 => [
                            'uraian' => 'Dokumen SPJ belum lengkap bukti pengeluaran sah.',
                            'penyebab' => 'Kelalaian bendahara dan kurangnya pengawasan atasan langsung.',
                            'rekomendasi_items' => [
                                'Melengkapi bukti fisik kuitansi belanja.',
                                'Meningkatkan supervisi berkala internal.',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect();

        $temuan = Temuan::where('uraian_temuan', 'Dokumen SPJ belum lengkap bukti pengeluaran sah.')->first();
        $this->assertNotNull($temuan);
        $this->assertTrue((bool)$temuan->ada_unsur_administrasi);
        $this->assertEquals('Kelalaian bendahara dan kurangnya pengawasan atasan langsung.', $temuan->penyebab);
        $temuan->loadMissing('rekomendasis');
        $this->assertCount(2, $temuan->rekomendasis);

        // 2. Update via pemeriksaan.update
                $temuan->loadMissing('pemeriksaan');
        $pemeriksaan = $temuan->pemeriksaan;
                $temuan->loadMissing('lhp');
        $lhp = $temuan->lhp;

        $updateResponse = $this->actingAs($this->adminInsp)->put(route('pemeriksaan.update', $pemeriksaan), [
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => $dinkes->id,
            'opd_ids' => [$dinkes->id],
            'tahun' => 2026,
            'judul' => 'Pemeriksaan Administrasi dengan Penyebab Laporan (Revisi)',
            'nomor_spt' => $pemeriksaan->nomor_spt,
            'tanggal_spt' => '2026-03-01',
            'lhps' => [
                0 => [
                    'id' => $lhp->id,
                    'nomor_lhp' => $lhp->nomor_lhp,
                    'tanggal_lhp' => '2026-03-15',
                    'judul_lhp' => $lhp->judul_lhp,
                    'ringkasan' => $lhp->ringkasan,
                    'has_administrasi' => '1',
                    'temuans_administrasi' => [
                        0 => [
                            'id' => $temuan->id,
                            'uraian' => 'Dokumen SPJ belum lengkap bukti pengeluaran sah (Updated).',
                            'penyebab' => 'Penyebab telah diperbarui: SOP verifikasi belum tersosialisasi.',
                            'rekomendasi' => 'Segera sosialisasikan SOP.',
                        ],
                    ],
                ],
            ],
        ]);

        $updateResponse->assertRedirect();
        $temuan->refresh();
        $this->assertEquals('Dokumen SPJ belum lengkap bukti pengeluaran sah (Updated).', $temuan->uraian_temuan);
        $this->assertEquals('Penyebab telah diperbarui: SOP verifikasi belum tersosialisasi.', $temuan->penyebab);
    }

    public function test_wizard_page_is_accessible_for_admin_inspektorat(): void
    {
        $response = $this->actingAs($this->adminInsp)->get(route('pemeriksaan.create-wizard'));

        $response->assertOk();
        $response->assertViewIs('pemeriksaan.create-wizard');
        $response->assertViewHas('lockedSumber');
        $response->assertViewHas('opds');
        $response->assertViewHas('pegawais');

        // Komponen wizard harus ter-render
        $response->assertSee('wizard-step-1');
        $response->assertSee('wizard-step-2');
        $response->assertSee('wizard-step-3');
        $response->assertSee('wizard-step-4');

        // Field tim pemeriksa harus memakai nama param yang dikenali backend (peran_tim)
        $response->assertSee('tim_pemeriksa[${timIndex}][peran_tim]', false);
    }

    public function test_wizard_page_denied_for_opd_and_pemantau(): void
    {
        $this->actingAs($this->opdDinkes)
            ->get(route('pemeriksaan.create-wizard'))
            ->assertForbidden();

        $this->actingAs($this->pemantau)
            ->get(route('pemeriksaan.create-wizard'))
            ->assertForbidden();
    }

    public function test_wizard_payload_persists_temuan_administrasi_dan_kerugian(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();
        $pegawai = \App\Models\Pegawai::first();

        // Payload ini meniru hidden input yang dihasilkan wizard (step1/2/3/4)
        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'judul' => 'PemeriksaanVia Wizard TA 2026',
            'nomor_spt' => '700/ITPROV/WIZARD/2026',
            'sumber_pemeriksaan' => 'Inspektorat',
            'tanggal_spt' => '2026-04-01',
            'tahun' => 2026,

            'tim_pemeriksa' => [
                0 => ['pegawai_id' => $pegawai->id, 'peran_tim' => 'Ketua Tim'],
            ],

            'lhps' => [
                0 => [
                    'nomor_lhp' => '700/01/LHP-WIZARD/2026',
                    'tanggal_lhp' => '2026-04-10',
                    'judul_lhp' => 'LHP melalui wizard',
                    'opd_ids' => [$dinkes->id],
                    'ringkasan' => 'Ringkasan hasil pemeriksaan via wizard',

                    'temuans_administrasi' => [
                        0 => [
                            'uraian' => 'SOP penatausahaan belum ditetapkan.',
                            'penyebab' => 'Belum ada rationally drafted SOP.',
                            'rekomendasi' => 'Menyusun SOP definitif.',
                        ],
                    ],

                    'temuans_kerugian' => [
                        0 => [
                            'uraian' => 'Kelebihan pembayaran honorarium pendamping.',
                            'kategori_kerugian' => 'Kerugian Negara',
                            'jenis_kerugian' => 'Eksternal',
                            'nilai_kerugian' => '25000000',
                            'penyebab' => 'Verifikasi invoice kurang cermat.',
                            'rekomendasi' => 'Menyetorkan kelebihan bayar ke Kas Negara.',
                        ],
                    ],
                ],
            ],
        ]);

        $pemeriksaan = Pemeriksaan::where('nomor_spt', '700/ITPROV/WIZARD/2026')->first();
        $this->assertNotNull($pemeriksaan, 'Wizard payload gagal disimpan');

        $lhp = $pemeriksaan->lhps()->first();
        $this->assertNotNull($lhp);

                $lhp->loadMissing('temuans');
        $temuans = $lhp->temuans;
        $this->assertCount(2, $temuans);

        $adm = $temuans->where('ada_unsur_administrasi', true)->first();
        $this->assertNotNull($adm);
        $this->assertEquals('SOP penatausahaan belum ditetapkan.', $adm->uraian_temuan);
        // Service menomori butir rekomendasi secara otomatis
        $this->assertEquals('1. Menyusun SOP definitif.', $adm->rekomendasi);
        $this->assertEquals('Belum ada rationally drafted SOP.', $adm->penyebab);
        $adm->loadMissing('rekomendasis');
        $this->assertCount(1, $adm->rekomendasis);

        $kg = $temuans->where('ada_unsur_kerugian', true)->first();
        $this->assertNotNull($kg);
        $this->assertEquals('Kerugian Negara', $kg->kategori_kerugian);
        $this->assertEquals('Eksternal', $kg->jenis_kerugian);
        $this->assertEquals(25000000.00, $kg->nilai_kerugian);
        $this->assertEquals('Menyetorkan kelebihan bayar ke Kas Negara.', $kg->rekomendasi);

        $response->assertRedirect(route('pemeriksaan.show', $pemeriksaan));
    }

    public function test_wizard_payload_rejects_invalid_kategori_and_jenis_kerugian(): void
    {
        $dinkes = Opd::where('kode', 'DINKES')->first();

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.store'), [
            'judul' => 'Wizard Validasi Kerugian',
            'nomor_spt' => '700/ITPROV/WIZARD-Invalid/2026',
            'sumber_pemeriksaan' => 'Inspektorat',
            'tahun' => 2026,
            'lhps' => [
                0 => [
                    'nomor_lhp' => '700/01/LHP-INVALID/2026',
                    'opd_ids' => [$dinkes->id],
                    'temuans_kerugian' => [
                        0 => [
                            'uraian' => 'Uraian kerugian',
                            'kategori_kerugian' => 'Kerugian Planet',
                            'jenis_kerugian' => 'Alien',
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertSessionHasErrors('lhps.0.temuans_kerugian.0.kategori_kerugian');
        $response->assertSessionHasErrors('lhps.0.temuans_kerugian.0.jenis_kerugian');

        $this->assertNull(Pemeriksaan::where('nomor_spt', '700/ITPROV/WIZARD-Invalid/2026')->first());
    }
}

