<?php

namespace Tests\Feature;

use App\Models\Lhp;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\TimPemeriksa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TimAndLhpTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminBpk;
    protected User $adminInsp;
    protected User $opdUser;
    protected Pemeriksaan $pemeriksaanInsp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $this->adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $this->adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();
        $this->opdUser = User::where('email', 'opd.dinkes@gorontaloprov.go.id')->first();

        $this->pemeriksaanInsp = Pemeriksaan::where('nomor_spt', '700/ITPROV/042/SPT/2025')->first();
    }

    public function test_admin_can_add_member_to_inspection_team(): void
    {
        $pegawai = Pegawai::where('nip', '19910718 201503 1 002')->first(); // Mohamad Rizki

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.tim.store', $this->pemeriksaanInsp), [
            'pegawai_id' => $pegawai->id,
            'peran_tim' => 'Anggota Tim',
            'keterangan' => 'Pemeriksaan Sistem IT',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tim_pemeriksas', [
            'pemeriksaan_id' => $this->pemeriksaanInsp->id,
            'pegawai_id' => $pegawai->id,
            'peran_tim' => 'Anggota Tim',
        ]);
    }

    public function test_cannot_add_duplicate_member_to_same_team(): void
    {
        $existing = TimPemeriksa::where('pemeriksaan_id', $this->pemeriksaanInsp->id)->first();

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.tim.store', $this->pemeriksaanInsp), [
            'pegawai_id' => $existing->pegawai_id,
            'peran_tim' => 'Anggota Tim',
        ]);

        $response->assertSessionHasErrors('pegawai_id');
    }

    public function test_admin_can_remove_member_from_team(): void
    {
        $member = TimPemeriksa::where('pemeriksaan_id', $this->pemeriksaanInsp->id)->first();

        $response = $this->actingAs($this->adminInsp)->delete(route('pemeriksaan.tim.destroy', [$this->pemeriksaanInsp, $member]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tim_pemeriksas', [
            'id' => $member->id,
        ]);
    }

    public function test_opd_cannot_manage_inspection_team(): void
    {
        $pegawai = Pegawai::first();

        $response = $this->actingAs($this->opdUser)->post(route('pemeriksaan.tim.store', $this->pemeriksaanInsp), [
            'pegawai_id' => $pegawai->id,
            'peran_tim' => 'Anggota Tim',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_lhp_with_document_upload(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('LHP_RSUD_2025.pdf', 800, 'application/pdf');

        $response = $this->actingAs($this->adminInsp)->post(route('pemeriksaan.lhp.store', $this->pemeriksaanInsp), [
            'nomor_lhp' => '700/ITPROV/099/LHP/TEST',
            'tanggal_lhp' => '2025-06-01',
            'judul_lhp' => 'LHP Uji Coba Pengadaan',
            'ringkasan' => 'Ringkasan hasil audit pengadaan obat',
            'dokumen_lhp' => $file,
        ]);

        $response->assertRedirect();
        $lhp = Lhp::where('nomor_lhp', '700/ITPROV/099/LHP/TEST')->first();
        $this->assertNotNull($lhp);
        $this->assertEquals($this->pemeriksaanInsp->id, $lhp->pemeriksaan_id);
        Storage::disk('local')->assertExists($lhp->dokumen_lhp);
    }

    public function test_user_can_download_lhp_document(): void
    {
        Storage::fake('local');

        $lhp = Lhp::first();
        $lhp->loadMissing('pemeriksaan')->pemeriksaan->update(['status_pengajuan' => 'DISETUJUI']);
        $filePath = 'lhp/sample_lhp.pdf';
        Storage::disk('local')->put($filePath, 'fake lhp pdf content');

        $lhp->update([
            'dokumen_lhp' => $filePath,
            'nama_dokumen_asli' => 'LHP_Test.pdf',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('pemeriksaan.lhp.download', [$this->pemeriksaanInsp, $lhp]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
    }

    public function test_admin_bpk_can_access_lhp_create_form(): void
    {
        $response = $this->actingAs($this->adminBpk)->get(route('lhp.create'));
        $response->assertStatus(200);
        $response->assertSee('BAGIAN 1: INPUT LAPORAN HASIL PEMERIKSAAN (LHP)');
        $response->assertSee('BPK');
    }

    public function test_pemantau_and_opd_cannot_access_lhp_create(): void
    {
        $pemantau = User::where('email', 'pemantau@gorontaloprov.go.id')->first();
        $resPemantau = $this->actingAs($pemantau)->get(route('lhp.create'));
        $resPemantau->assertStatus(403);

        $resOpd = $this->actingAs($this->opdUser)->get(route('lhp.create'));
        $resOpd->assertStatus(403);
    }

    public function test_admin_bpk_can_store_lhp_directly_with_temuans_and_tindak_lanjut(): void
    {
        $dinkes = \App\Models\Opd::where('kode', 'DINKES')->first();
        $dikbud = \App\Models\Opd::where('kode', 'DIKBUD')->first();

        $response = $this->actingAs($this->adminBpk)->post(route('lhp.store-direct'), [
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'nomor_lhp' => '05/LHP/BPK/DIRECT/2026',
            'tanggal_lhp' => '2026-03-01',
            'judul_lhp' => 'LHP BPK atas LKPD TA 2025',
            'opd_ids' => [$dinkes->id, $dikbud->id],
            'ringkasan' => 'Ditemukan kelemahan pengendalian SPI dan kelebihan pembayaran belanja jasa.',

            // Temuan Administrasi
            'temuans_administrasi' => [
                0 => [
                    'uraian' => 'Penatausahaan aset tetap belum tertib.',
                    'rekomendasi' => 'Melakukan inventarisasi dan rekonsiliasi aset secara berkala.',
                ],
            ],

            // Temuan Kerugian
            'temuan_kerugian' => [
                'uraian' => 'Kelebihan pembayaran belanja jasa konsultansi.',
                'nilai_kerugian' => '25.000.000',
                'rekomendasi' => 'Menyetorkan kelebihan pembayaran Rp 25.000.000 ke Kas Daerah.',
            ],

            // Tindak Lanjut Awal Kerugian
            'tindak_lanjut_kg' => [
                'tanggal' => '2026-03-05',
                'nomor_sts' => 'STS/2026/03/001',
                'tanggal_setor' => '2026-03-05',
                'jumlah_setor' => '25.000.000',
                'uraian' => 'Penyetoran lunas ke Kas Daerah melalui Bank SulutGo.',
            ],
        ]);

        $response->assertRedirect(route('lhp.index'));

        // Assert LHP created
        $lhp = Lhp::where('nomor_lhp', '05/LHP/BPK/DIRECT/2026')->first();
        $this->assertNotNull($lhp);
        $lhp->loadMissing('pemeriksaan');
        $this->assertEquals('BPK', $lhp->pemeriksaan->sumber_pemeriksaan);

        $lhp->loadMissing(['opds', 'temuans']);
        $this->assertCount(2, $lhp->opds);

        // Assert Temuans created
        $this->assertCount(2, $lhp->temuans);
        $adm = $lhp->temuans->where('ada_unsur_administrasi', true)->first();
        $kg = $lhp->temuans->where('ada_unsur_kerugian', true)->first();
        $this->assertNotNull($adm);
        $this->assertNotNull($kg);
        $this->assertEquals(25000000.00, $kg->nilai_kerugian);

        // Assert Tindak Lanjut created
        // Muat ulang dengan eager load: preventLazyLoading aktif di test agar
        // perilakunya sama dengan produksi.
        $kg->loadMissing('tindakLanjuts');
        $this->assertCount(1, $kg->tindakLanjuts);
        $this->assertEquals(25000000.00, $kg->tindakLanjuts->first()->jumlah_setor);
    }

    public function test_admin_bpk_can_store_multi_lhp_directly(): void
    {
        $dinkes = \App\Models\Opd::where('kode', 'DINKES')->first();
        $dikbud = \App\Models\Opd::where('kode', 'DIKBUD')->first();

        $response = $this->actingAs($this->adminBpk)->post(route('lhp.store-direct'), [
            'sumber_pemeriksaan' => 'BPK',
            'tahun' => 2026,
            'lhps' => [
                0 => [
                    'nomor_lhp' => '05/LHP/BPK/MULTI-1/2026',
                    'tanggal_lhp' => '2026-03-01',
                    'judul_lhp' => 'LHP BPK Sistem Pengendalian Intern',
                    'opd_ids' => [$dinkes->id],
                    'ringkasan' => 'Evaluasi kepatuhan SPI.',
                    'temuans_administrasi' => [
                        0 => [
                            'uraian' => 'SOP Pengadaan belum dimutakhirkan.',
                            'rekomendasi' => 'Menyusun SOP terbaru.',
                        ],
                    ],
                ],
                1 => [
                    'nomor_lhp' => '05/LHP/BPK/MULTI-2/2026',
                    'tanggal_lhp' => '2026-03-02',
                    'judul_lhp' => 'LHP BPK Kepatuhan Belanja Modal',
                    'opd_ids' => [$dikbud->id],
                    'ringkasan' => 'Pemeriksaan fisik belanja modal sekolah.',
                    'temuan_kerugian' => [
                        'uraian' => 'Kekurangan volume pekerjaan gedung sekolah.',
                        'nilai_kerugian' => '15.000.000',
                        'rekomendasi' => 'Menyetorkan kelebihan bayar Rp 15.000.000 ke Kasda.',
                    ],
                ],
            ],
            'tindak_lanjuts' => [
                0 => [
                    'lhp_index' => 1,
                    'tanggal_tindak_lanjut' => '2026-03-05',
                    'temuan_type' => 'kerugian',
                    'uraian_tindak_lanjut' => 'Penyetoran lunas ke Kas Daerah.',
                    'nomor_bukti_setor' => 'STS/2026/03/999',
                    'tanggal_setor' => '2026-03-05',
                    'jumlah_setor' => '15.000.000',
                ],
            ],
        ]);

        $response->assertRedirect(route('lhp.index'));

        // Assert 2 LHPs created
        $lhp1 = Lhp::where('nomor_lhp', '05/LHP/BPK/MULTI-1/2026')->first();
        $lhp2 = Lhp::where('nomor_lhp', '05/LHP/BPK/MULTI-2/2026')->first();

        $this->assertNotNull($lhp1);
        $this->assertNotNull($lhp2);
        $lhp1->loadMissing('pemeriksaan');
        $this->assertEquals('BPK', $lhp1->pemeriksaan->sumber_pemeriksaan);
        $lhp2->loadMissing('pemeriksaan');
        $this->assertEquals('BPK', $lhp2->pemeriksaan->sumber_pemeriksaan);

        // Assert Temuans & TL
        $lhp1->loadMissing('temuans');
        $lhp2->loadMissing('temuans');
        $this->assertCount(1, $lhp1->temuans);
        $this->assertCount(1, $lhp2->temuans);

        $kgTemuan = $lhp2->temuans->where('ada_unsur_kerugian', true)->first();
        $this->assertNotNull($kgTemuan);
        $this->assertEquals(15000000.00, $kgTemuan->nilai_kerugian);
        $kgTemuan->loadMissing('tindakLanjuts');
        $this->assertCount(1, $kgTemuan->tindakLanjuts);
        $this->assertEquals(15000000.00, $kgTemuan->tindakLanjuts->first()->jumlah_setor);
    }
}
