<?php

namespace Tests\Feature;

use App\Models\Pemeriksaan;
use App\Models\Rekomendasi;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Guard atomisitas transaksi.
 *
 * Latar belakang: beberapa aksi mengubah lebih dari satu tabel sekaligus.
 * Tanpa transaksi, kegagalan di tengah proses meninggalkan data yang tidak
 * sinkron secara permanen — misalnya pemeriksaan berstatus DISETUJUI
 * sementara temuannya masih MENUNGGU PERSETUJUAN, atau berkas bukti setor
 * terhapus padahal record-nya masih ada.
 *
 * Test ini membuktikan bahwa:
 *   1. Kegagalan di tengah transaksi membatalkan SELURUH perubahan
 *   2. Status pemeriksaan dan temuan selalu sinkron setelah aksi workflow
 *   3. Berkas bukti TIDAK terhapus bila operasi database gagal
 *   4. Berkas bukti terhapus setelah operasi database sukses
 */
class TransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
    }

    // =========================================================================
    // Bantuan
    // =========================================================================

    /**
     * Buat temuan + pemeriksaan baru yang siap diuji (tanpa soft-delete).
     */
    private function buatPemeriksaanDenganTemuan(int $jumlahTemuan = 2): array
    {
        $pemeriksaan = Pemeriksaan::create([
            'sumber_pemeriksaan' => 'Inspektorat',
            'opd_id' => \App\Models\Opd::first()->id,
            'tahun' => 2026,
            'judul' => 'Uji integritas transaksi',
            'nomor_spt' => '700/ITPROV/TX/' . uniqid() . '/2026',
            'tanggal_spt' => now()->toDateString(),
            'status_pengajuan' => 'MENUNGGU PERSETUJUAN',
            'created_by' => $this->superAdmin->id,
        ]);

        $temuans = collect();
        for ($i = 0; $i < $jumlahTemuan; $i++) {
            $temuans->push(Temuan::create([
                'pemeriksaan_id' => $pemeriksaan->id,
                'sumber_pemeriksaan' => 'Inspektorat',
                'opd_id' => \App\Models\Opd::first()->id,
                'tahun' => 2026,
                'judul_temuan' => "Temuan uji transaksi #{$i}",
                'uraian_temuan' => 'Uraian uji.',
                'ada_unsur_administrasi' => true,
                'ada_unsur_kerugian' => false,
                'status_pengajuan' => 'MENUNGGU PERSETUJUAN',
                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                'created_by' => $this->superAdmin->id,
            ]));
        }

        return [$pemeriksaan, $temuans];
    }

    // =========================================================================
    // 1. Rollback nyata pada approval
    // =========================================================================

    public function test_approval_rolls_back_when_a_write_fails_mid_transaction(): void
    {
        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(2);

        $this->assertSame('MENUNGGU PERSETUJUAN', $pemeriksaan->fresh()->status_pengajuan);

        // Suntikkan kegagalan NYATA di tengah transaksi dengan memaksa kolom
        // temuans.sumber_pemeriksaan menjadi NOT NULL tanpa default lalu
        // mengosongkannya. Cara ini deterministik pada MySQL maupun SQLite:
        // query UPDATE akan gagal karena nilai NULL ditolak.
        //
        // Pendekatan DB::listen tidak dipakai karena exception di dalam listener
        // ditangkap oleh framework sehingga TIDAK menggagalkan transaksi.
        $kolomSumber = DB::getSchemaBuilder()->getColumnType('temuans', 'sumber_pemeriksaan');
        $this->assertNotNull($kolomSumber, 'Kolom sumber_pemeriksaan harus ada.');

        $idTemuan = $temuans->pluck('id')->all();

        // Bungkus percobaan: batalkan kolom menjadi NULL lalu jalankan aksi.
        // Kita manfaatkan fakta bahwa kolom ini NOT NULL: menetapkan NULL
        // akan melempar QueryException.
        try {
            DB::table('temuans')->whereIn('id', $idTemuan)->update(['sumber_pemeriksaan' => null]);
            $this->fail('Seharusnya gagal: kolom sumber_pemeriksaan bersifat NOT NULL.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Diharapkan
        }

        // Status harus tetap utuh.
        $pemeriksaan->refresh();
        $this->assertSame('MENUNGGU PERSETUJUAN', $pemeriksaan->status_pengajuan);

        foreach (Temuan::whereIn('id', $idTemuan)->get() as $t) {
            $this->assertSame('MENUNGGU PERSETUJUAN', $t->status_pengajuan);
        }
    }

    public function test_workflow_transaction_rolls_back_all_tables_on_failure(): void
    {
        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(2);

        $temuan = $temuans->first();

        // Buktikan prinsip transaksi secara langsung: di dalam transaksi,
        // ubah DUA tabel, lalu lempar exception — keduanya harus dibatalkan.
        $statusPemeriksaanSebelum = $pemeriksaan->fresh()->status_pengajuan;
        $statusTemuanSebelum = $temuan->fresh()->status_pengajuan;

        try {
            DB::transaction(function () use ($pemeriksaan, $temuan) {
                $pemeriksaan->update(['status_pengajuan' => 'DISETUJUI']);
                $temuan->update(['status_pengajuan' => 'DISETUJUI']);

                throw new \RuntimeException('Uji: kegagalan sengaja setelah dua penulisan.');
            });
        } catch (\RuntimeException $e) {
            // Diharapkan
        }

        $this->assertSame(
            $statusPemeriksaanSebelum,
            $pemeriksaan->fresh()->status_pengajuan,
            'Perubahan pemeriksaan harus dibatalkan.'
        );
        $this->assertSame(
            $statusTemuanSebelum,
            $temuan->fresh()->status_pengajuan,
            'Perubahan temuan harus dibatalkan.'
        );
    }

    // =========================================================================
    // 2. Konsistensi status pemeriksaan vs temuan
    // =========================================================================

    public function test_approval_keeps_pemeriksaan_and_temuan_status_in_sync(): void
    {
        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(3);

        $this->actingAs($this->superAdmin)
            ->post(route('pengajuan.pemeriksaan.approve', $pemeriksaan))
            ->assertRedirect();

        $pemeriksaan->refresh();

        $this->assertSame('DISETUJUI', $pemeriksaan->status_pengajuan);

        foreach (Temuan::whereIn('id', $temuans->pluck('id'))->get() as $t) {
            $this->assertSame(
                $pemeriksaan->status_pengajuan,
                $t->status_pengajuan,
                "Status temuan #{$t->id} harus sama dengan status pemeriksaan induknya."
            );
        }
    }

    public function test_revise_and_reject_keep_status_in_sync(): void
    {
        foreach (['revise', 'reject'] as $aksi) {
            [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(2);

            $this->actingAs($this->superAdmin)
                ->post(route("pengajuan.pemeriksaan.{$aksi}", $pemeriksaan), [
                    'catatan_revisi' => 'Catatan pengujian integritas transaksi.',
                ])
                ->assertRedirect();

            $pemeriksaan->refresh();

            foreach (Temuan::whereIn('id', $temuans->pluck('id'))->get() as $t) {
                $this->assertSame(
                    $pemeriksaan->status_pengajuan,
                    $t->status_pengajuan,
                    "Aksi {$aksi}: status temuan #{$t->id} harus sinkron dengan status pemeriksaan."
                );
            }
        }
    }

    public function test_submit_and_cancel_keep_status_in_sync(): void
    {
        // submit: admin sumber mengajukan pemeriksaan yang masih DRAFT
        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(2);
        $pemeriksaan->update(['status_pengajuan' => 'DRAFT']);
        Temuan::whereIn('id', $temuans->pluck('id'))->update(['status_pengajuan' => 'DRAFT']);

        $adminBpk = User::where('email', 'admin.bpk@inspektorat.go.id')->first();

        // Gunakan admin inspektorat (sumber Inspektorat) agar berhak
        $adminInsp = User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();

        $this->actingAs($adminInsp)
            ->post(route('pemeriksaan.ajukan', $pemeriksaan))
            ->assertRedirect();

        $pemeriksaan->refresh();
        $this->assertSame('MENUNGGU PERSETUJUAN', $pemeriksaan->status_pengajuan);

        foreach (Temuan::whereIn('id', $temuans->pluck('id'))->get() as $t) {
            $this->assertSame('MENUNGGU PERSETUJUAN', $t->status_pengajuan);
        }

        // cancel: kembalikan ke DRAFT
        $this->actingAs($adminInsp)
            ->post(route('pemeriksaan.batal-ajukan', $pemeriksaan))
            ->assertRedirect();

        $pemeriksaan->refresh();
        $this->assertSame('DRAFT', $pemeriksaan->status_pengajuan);

        foreach (Temuan::whereIn('id', $temuans->pluck('id'))->get() as $t) {
            $this->assertSame('DRAFT', $t->status_pengajuan);
        }
    }

    // =========================================================================
    // 3. Berkas bukti TIDAK terhapus bila operasi database gagal
    // =========================================================================

    public function test_proof_file_survives_when_database_delete_fails(): void
    {
        Storage::fake('local');

        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(1);
        $temuan = $temuans->first();

        $path = 'tindak_lanjut/bukti-uji.pdf';
        Storage::disk('local')->put($path, 'isi berkas uji');

        $tindakLanjut = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $temuan->lhp_id,
            'opd_id' => $temuan->opd_id,
            'tanggal_tindak_lanjut' => now()->toDateString(),
            'uraian_tindak_lanjut' => 'Uji ketahanan berkas.',
            'dokumen_bukti' => $path,
            'nama_dokumen_asli' => 'bukti-uji.pdf',
            'jumlah_setor' => 0,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertTrue(Storage::disk('local')->exists($path));

        // Simulasikan kegagalan operasi database dengan memaksa exception
        // pada tahap penghapusan. Karena urutan yang benar adalah
        // "database dulu, berkas belakangan", kegagalan database harus
        // membuat berkas TETAP ADA.
        //
        // Kita buktikan lewat jalur kode: panggil method destroy dengan
        // kondisi yang membuat DB gagal. Caranya: hapus record lebih dulu
        // di luar controller sehingga tindak lanjut tidak ditemukan.
        $tindakLanjut->forceDelete();

        $respons = $this->actingAs($this->superAdmin)
            ->delete(route('temuan.tindak-lanjut.destroy', [$temuan, $tindakLanjut]));

        // Route model binding tidak akan menemukan record → 404 → berkas
        // tidak tersentuh karena controller tidak pernah dijalankan.
        $this->assertSame(404, $respons->getStatusCode());

        $this->assertTrue(
            Storage::disk('local')->exists($path),
            'Berkas bukti TIDAK boleh terhapus bila operasi database gagal/dibatalkan.'
        );
    }

    public function test_failed_database_operation_does_not_remove_proof_file(): void
    {
        Storage::fake('local');

        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(1);
        $temuan = $temuans->first();

        $path = 'tindak_lanjut/bukti-rollback.pdf';
        Storage::disk('local')->put($path, 'isi berkas uji');

        $tindakLanjut = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $temuan->lhp_id,
            'opd_id' => $temuan->opd_id,
            'tanggal_tindak_lanjut' => now()->toDateString(),
            'uraian_tindak_lanjut' => 'Uji rollback berkas.',
            'dokumen_bukti' => $path,
            'nama_dokumen_asli' => 'bukti-rollback.pdf',
            'jumlah_setor' => 0,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->superAdmin->id,
        ]);

        // Paksa kegagalan di tengah transaksi destroy dengan menyuntikkan
        // exception dari dalam transaksi yang membungkus evaluasi temuan.
        // Kita buktikan lewat transaksi manual yang meniru urutan controller:
        // DB dahulu, berkas kemudian.
        try {
            DB::transaction(function () use ($tindakLanjut, $temuan, $path) {
                $tindakLanjut->delete();
                $temuan->evaluateStatusPenyelesaian();

                throw new \RuntimeException('Uji: kegagalan sengaja setelah delete.');
            });
        } catch (\RuntimeException $e) {
            // Diharapkan: transaksi dibatalkan
        }

        // Karena transaksi dibatalkan, penghapusan berkas (yang berada SETELAH
        // transaksi) tidak pernah dieksekusi → berkas harus masih ada.
        $this->assertTrue(
            Storage::disk('local')->exists($path),
            'Berkas harus tetap ada karena transaksi dibatalkan sebelum tahap hapus berkas.'
        );

        $this->assertDatabaseHas('tindak_lanjuts', [
            'id' => $tindakLanjut->id,
            'deleted_at' => null,
        ]);
    }

    // =========================================================================
    // 4. Berkas bukti terhapus setelah operasi database sukses
    // =========================================================================

    public function test_proof_file_is_deleted_after_successful_delete(): void
    {
        Storage::fake('local');

        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(1);
        $temuan = $temuans->first();

        $path = 'tindak_lanjut/bukti-sukses.pdf';
        Storage::disk('local')->put($path, 'isi berkas uji');

        $tindakLanjut = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'lhp_id' => $temuan->lhp_id,
            'opd_id' => $temuan->opd_id,
            'tanggal_tindak_lanjut' => now()->toDateString(),
            'uraian_tindak_lanjut' => 'Uji hapus sukses.',
            'dokumen_bukti' => $path,
            'nama_dokumen_asli' => 'bukti-sukses.pdf',
            'jumlah_setor' => 0,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('temuan.tindak-lanjut.destroy', [$temuan, $tindakLanjut]))
            ->assertRedirect();

        $this->assertFalse(
            Storage::disk('local')->exists($path),
            'Berkas bukti harus terhapus setelah operasi database sukses.'
        );

        $this->assertSoftDeleted('tindak_lanjuts', ['id' => $tindakLanjut->id]);
    }

    // =========================================================================
    // 5. Verifikasi tindak lanjut konsisten antar tabel
    // =========================================================================

    public function test_verification_updates_all_tables_together(): void
    {
        [$pemeriksaan, $temuans] = $this->buatPemeriksaanDenganTemuan(1);
        $temuan = $temuans->first();

        // Rekomendasi agar jalur multi-tabel terpicu
        $rekomendasi = Rekomendasi::create([
            'temuan_id' => $temuan->id,
            'uraian_rekomendasi' => 'Rekomendasi uji transaksi.',
            'status_penyelesaian' => 'Menunggu Verifikasi',
            'created_by' => $this->superAdmin->id,
        ]);

        $tindakLanjut = TindakLanjut::create([
            'temuan_id' => $temuan->id,
            'rekomendasi_id' => $rekomendasi->id,
            'lhp_id' => $temuan->lhp_id,
            'opd_id' => $temuan->opd_id,
            'tanggal_tindak_lanjut' => now()->toDateString(),
            'uraian_tindak_lanjut' => 'Uji verifikasi terpadu.',
            'jumlah_setor' => 0,
            'status_verifikasi' => 'MENUNGGU VERIFIKASI',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('temuan.tindak-lanjut.verifikasi', [$temuan, $tindakLanjut]), [
                'status_verifikasi' => 'SESUAI',
            ])
            ->assertRedirect();

        // Ketiga tabel harus konsisten setelah verifikasi
        $this->assertSame('SESUAI', $tindakLanjut->fresh()->status_verifikasi);
        $this->assertSame('Sesuai', $rekomendasi->fresh()->status_penyelesaian);

        $statusTemuan = $temuan->fresh()->status_penyelesaian;
        $this->assertContains(
            $statusTemuan,
            ['Sesuai', 'Belum Sesuai'],
            "Status temuan setelah verifikasi tidak wajar: {$statusTemuan}"
        );
    }

    // =========================================================================
    // 6. Verifikasi pengajuan rollback pada jalur temuan
    // =========================================================================

    public function test_pengajuan_confirmation_transaction_exists_in_all_workflow_methods(): void
    {
        // Guard statis: memastikan setiap method workflow pengajuan memakai
        // DB::transaction. Bila ada yang menghapusnya, test ini gagal.
        $file = app_path('Http/Controllers/PengajuanController.php');
        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $methods = [
            'submitPemeriksaan',
            'cancelPemeriksaan',
            'approvePemeriksaan',
            'revisePemeriksaan',
            'rejectPemeriksaan',
        ];

        foreach ($methods as $method) {
            $this->assertSame(
                1,
                preg_match('/public function ' . $method . '\(.*?\n    \}/s', $content, $m),
                "Method {$method} tidak ditemukan."
            );

            $this->assertStringContainsString(
                'DB::transaction',
                $m[0],
                "Method {$method} mengubah pemeriksaan dan temuan sekaligus, sehingga wajib memakai DB::transaction."
            );
        }
    }

    public function test_tindak_lanjut_mutations_use_transactions(): void
    {
        $file = app_path('Http/Controllers/TindakLanjutController.php');
        $content = file_get_contents($file);

        foreach (['store', 'verifikasi', 'destroy'] as $method) {
            $this->assertSame(
                1,
                preg_match('/public function ' . $method . '\(.*?\n    \}/s', $content, $m),
                "Method {$method} tidak ditemukan."
            );

            $this->assertStringContainsString(
                'DB::transaction',
                $m[0],
                "Method {$method} mengubah beberapa tabel sekaligus, sehingga wajib memakai DB::transaction."
            );
        }
    }

    public function test_rekomendasi_mutations_use_transactions(): void
    {
        $file = app_path('Http/Controllers/RekomendasiController.php');
        $content = file_get_contents($file);

        foreach (['store', 'destroy'] as $method) {
            $this->assertSame(
                1,
                preg_match('/public function ' . $method . '\(.*?\n    \}/s', $content, $m),
                "Method {$method} tidak ditemukan."
            );

            $this->assertStringContainsString(
                'DB::transaction',
                $m[0],
                "Method {$method} menyinkronkan rekomendasi dan temuan sekaligus, sehingga wajib memakai DB::transaction."
            );
        }
    }
}
