<?php

namespace Tests\Feature;

use App\Models\Lhp;
use App\Models\Opd;
use App\Models\Pegawai;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard anti-lazy-loading menyeluruh.
 *
 * Lingkungan produksi mengaktifkan preventLazyLoading. Bila ada halaman yang
 * mengakses relasi tanpa eager load, halaman itu mengembalikan HTTP 500 hanya
 * di server instansi — bukan saat pengembangan.
 *
 * Test ini menyapu SETIAP halaman untuk SETIAP peran yang berhak membukanya,
 * dan memastikan data uji mencukupi di semua jalur:
 *   - koleksi (get/paginate)
 *   - satu baris (first/find)
 *   - route model binding
 *
 * Sejarah: pola ini sudah menimbulkan error berulang tiga kali —
 * pemeriksaan.index (User::pegawai), pemeriksaan.show (Temuan::opd), dan
 * sapuan awal yang hanya memakai data seeder menghasilkan 266 halaman gagal.
 */
class LazyLoadingSweepTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        \Illuminate\Database\Eloquent\Model::preventLazyLoading(true);
        $this->assertTrue(\Illuminate\Database\Eloquent\Model::preventsLazyLoading());
    }

    protected function tearDown(): void
    {
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(false);
        parent::tearDown();
    }

    /**
     * @return array<string, User>
     */
    private function actors(): array
    {
        $emails = [
            'super_admin' => 'superadmin@inspektorat.go.id',
            'admin_inspektorat' => 'admin.inspektorat@inspektorat.go.id',
            'admin_bpk' => 'admin.bpk@inspektorat.go.id',
            'admin_bpkp' => 'admin.bpkp@inspektorat.go.id',
            'admin_itjen' => 'admin.itjen@inspektorat.go.id',
            'pemantau' => 'pemantau@gorontaloprov.go.id',
            'opd' => 'opd.dinkes@gorontaloprov.go.id',
        ];

        $actors = [];
        foreach ($emails as $role => $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $actors[$role] = $user;
            }
        }

        $this->assertCount(7, $actors, 'Seluruh peran harus memiliki akun uji.');

        return $actors;
    }

    /**
     * Pastikan setiap entitas memiliki lebih dari satu baris, sehingga jalur
     * koleksi (get/paginate) benar-benar terpicu dan tidak lolos diam-diam.
     *
     * Juga membuat variasi data yang MEMICU jalur fallback di view, karena
     * tanpa variasi tersebut banyak bug lazy loading tidak akan terdeteksi.
     * Contoh nyata: `$temuan?->lhp ?? $tl->lhp` pada tindak_lanjut/index —
     * selama semua data punya `temuan.lhp` terisi, cabang `$tl->lhp` tidak
     * pernah dievaluasi dan bug-nya lolos dari pengujian.
     */
    private function ensureMultipleRowsForEveryModel(): void
    {
        $opd = Opd::first();
        $pegawai = Pegawai::first();
        $user = User::where('email', 'admin.bpk@inspektorat.go.id')->first();
        $temuan = Temuan::first();
        $lhp = Lhp::first();

        // TindakLanjut hanya 1 baris pada data seeder; tambah agar jalur
        // koleksi terpicu saat menyapu halaman tindak-lanjut.index.
        if ($temuan && TindakLanjut::count() < 2) {
            TindakLanjut::create([
                'temuan_id' => $temuan->id,
                'lhp_id' => $temuan->lhp_id,
                'opd_id' => $temuan->opd_id,
                'tanggal_tindak_lanjut' => now()->toDateString(),
                'uraian_tindak_lanjut' => 'Uji sapuan lazy loading.',
                'jumlah_setor' => 0,
                'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                'created_by' => $user?->id,
            ]);
        }

        // Variasi penting: tindak lanjut yang temuan induknya TIDAK punya LHP.
        // Ini memaksa view mengevaluasi cabang `$tl->lhp` (fallback), sehingga
        // relasi lhp pada TindakLanjut benar-benar diuji.
        $sudahAdaTemuanTanpaLhp = TindakLanjut::with('temuan')->get()->contains(
            fn ($tl) => $tl->temuan !== null && $tl->temuan->lhp_id === null
        );

        if (! $sudahAdaTemuanTanpaLhp && $temuan && $opd) {
            $temuanTanpaLhp = Temuan::create([
                'pemeriksaan_id' => $temuan->pemeriksaan_id,
                'lhp_id' => null,
                'sumber_pemeriksaan' => $temuan->sumber_pemeriksaan,
                'opd_id' => $opd->id,
                'tahun' => $temuan->tahun,
                'judul_temuan' => 'Uji temuan tanpa LHP (sapuan lazy loading)',
                'uraian_temuan' => 'Temuan untuk menguji fallback relasi lhp.',
                'ada_unsur_administrasi' => true,
                'ada_unsur_kerugian' => false,
                'status_pengajuan' => 'DISETUJUI',
                'status_penyelesaian' => 'Belum Ditindaklanjuti',
                'created_by' => $user?->id,
            ]);

            TindakLanjut::create([
                'temuan_id' => $temuanTanpaLhp->id,
                'lhp_id' => $lhp?->id,
                'opd_id' => $opd->id,
                'tanggal_tindak_lanjut' => now()->toDateString(),
                'uraian_tindak_lanjut' => 'Uji tindak lanjut tanpa LHP pada temuan.',
                'jumlah_setor' => 0,
                'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                'created_by' => $user?->id,
            ]);
        }

        // Variasi: temuan yang sudah dihapus tetapi masih dirujuk tindak lanjut.
        // Relasi TindakLanjut::temuan() memakai withTrashed() sehingga konteks
        // temuan harus tetap tampil.
        if ($temuan) {
            $sudahAdaTemuanTerhapus = TindakLanjut::with('temuan')->get()
                ->contains(fn ($tl) => $tl->temuan !== null && $tl->temuan->trashed());

            if (! $sudahAdaTemuanTerhapus) {
                $temuanTerhapus = Temuan::create([
                    'pemeriksaan_id' => $temuan->pemeriksaan_id,
                    'lhp_id' => $temuan->lhp_id,
                    'sumber_pemeriksaan' => $temuan->sumber_pemeriksaan,
                    'opd_id' => $opd?->id ?? $temuan->opd_id,
                    'tahun' => $temuan->tahun,
                    'judul_temuan' => 'Uji temuan terhapus (sapuan lazy loading)',
                    'uraian_temuan' => 'Temuan yang dihapus namun masih ditindaklanjuti.',
                    'ada_unsur_administrasi' => true,
                    'ada_unsur_kerugian' => false,
                    'status_pengajuan' => 'DISETUJUI',
                    'status_penyelesaian' => 'Belum Ditindaklanjuti',
                    'created_by' => $user?->id,
                ]);

                TindakLanjut::create([
                    'temuan_id' => $temuanTerhapus->id,
                    'lhp_id' => $temuanTerhapus->lhp_id,
                    'opd_id' => $temuanTerhapus->opd_id,
                    'tanggal_tindak_lanjut' => now()->toDateString(),
                    'uraian_tindak_lanjut' => 'Uji tindak lanjut untuk temuan terhapus.',
                    'jumlah_setor' => 0,
                    'status_verifikasi' => 'MENUNGGU VERIFIKASI',
                    'created_by' => $user?->id,
                ]);

                $temuanTerhapus->delete();
            }
        }

        $this->assertGreaterThanOrEqual(2, Pemeriksaan::count());
        $this->assertGreaterThanOrEqual(2, Temuan::count());
        $this->assertGreaterThanOrEqual(2, Opd::count());
        $this->assertGreaterThanOrEqual(2, Pegawai::count());
        $this->assertGreaterThanOrEqual(2, User::count());
        $this->assertGreaterThanOrEqual(2, TindakLanjut::count(), 'Butuh >1 tindak lanjut agar jalur koleksi terpicu.');
        $this->assertGreaterThanOrEqual(
            1,
            TindakLanjut::whereNotNull('temuan_id')->count(),
            'Butuh tindak lanjut yang merujuk temuan untuk menguji jalur relasi temuan.'
        );

        if ($pegawai) {
            $this->assertTrue(true);
        }
    }

    /**
     * @return array<string, string>
     */
    private function urls(): array
    {
        $urls = [
            'dashboard' => route('dashboard'),
            'pemeriksaan.index' => route('pemeriksaan.index'),
            'pemeriksaan.create' => route('pemeriksaan.create'),
            'pemeriksaan.create-wizard' => route('pemeriksaan.create-wizard'),
            'temuan.index' => route('temuan.index'),
            'temuan.create' => route('temuan.create'),
            'lhp.index' => route('lhp.index'),
            'lhp.create' => route('lhp.create'),
            'tindak-lanjut.index' => route('tindak-lanjut.index'),
            'laporan.index.kerugian' => route('laporan.index', ['jenis' => 'kerugian']),
            'laporan.index.administrasi' => route('laporan.index', ['jenis' => 'administrasi']),
            'laporan.index.rincian' => route('laporan.index', ['tab' => 'rincian', 'jenis' => 'administrasi']),
            'laporan.index.rincian.kerugian' => route('laporan.index', ['tab' => 'rincian', 'jenis' => 'kerugian']),
            'laporan.cetak.kerugian' => route('laporan.cetak', ['jenis' => 'kerugian']),
            'laporan.cetak.administrasi' => route('laporan.cetak', ['tab' => 'rincian', 'jenis' => 'administrasi']),
            'pengajuan.index' => route('pengajuan.index'),
            'audit-log.index' => route('audit-log.index'),
            'audit-log.trash' => route('audit-log.trash'),
            'audit-log.trash.temuan' => route('audit-log.trash', ['type' => 'temuan']),
            'audit-log.trash.opd' => route('audit-log.trash', ['type' => 'opd']),
            'audit-log.trash.pegawai' => route('audit-log.trash', ['type' => 'pegawai']),
            'audit-log.trash.user' => route('audit-log.trash', ['type' => 'user']),
            'opd.index' => route('opd.index'),
            'opd.create' => route('opd.create'),
            'pegawai.index' => route('pegawai.index'),
            'pegawai.create' => route('pegawai.create'),
            'user.index' => route('user.index'),
            'user.create' => route('user.create'),
        ];

        // Jalur route model binding untuk SETIAP entitas utama
        foreach (Pemeriksaan::all() as $i => $p) {
            $urls["pemeriksaan.show.{$i}"] = route('pemeriksaan.show', $p);
            $urls["pemeriksaan.edit.{$i}"] = route('pemeriksaan.edit', $p);
        }

        foreach (Temuan::all() as $i => $t) {
            $urls["temuan.show.{$i}"] = route('temuan.show', $t);
            $urls["temuan.edit.{$i}"] = route('temuan.edit', $t);
        }

        foreach (Lhp::all() as $i => $l) {
            if ($l->pemeriksaan_id) {
                $urls["lhp.show.{$i}"] = route('pemeriksaan.lhp.show', [$l->pemeriksaan_id, $l->id]);
            }
        }

        foreach (Opd::all() as $i => $o) {
            $urls["opd.show.{$i}"] = route('opd.show', $o);
            $urls["opd.edit.{$i}"] = route('opd.edit', $o);
        }

        foreach (Pegawai::all() as $i => $pg) {
            $urls["pegawai.show.{$i}"] = route('pegawai.show', $pg);
            $urls["pegawai.edit.{$i}"] = route('pegawai.edit', $pg);
        }

        foreach (User::where('role_id', '!=', null)->limit(4)->get() as $i => $u) {
            $urls["user.edit.{$i}"] = route('user.edit', $u);
        }

        return $urls;
    }

    public function test_no_page_throws_lazy_loading_for_any_role(): void
    {
        $this->ensureMultipleRowsForEveryModel();

        $actors = $this->actors();
        $urls = $this->urls();

        $failures = [];

        foreach ($actors as $role => $user) {
            foreach ($urls as $label => $url) {
                try {
                    $response = $this->actingAs($user)->get($url);
                    $status = $response->getStatusCode();

                    // 403/404 wajar (peran tidak berhak / entitas tidak ada).
                    // Yang tidak boleh: 500.
                    if ($status >= 500) {
                        $failures["{$role} → {$label}"] = 'HTTP ' . $status;
                    }
                } catch (\Illuminate\Database\LazyLoadingViolationException $e) {
                    $failures["{$role} → {$label}"] = 'LAZY LOADING: ' . $e->getMessage();
                } catch (\Throwable $e) {
                    $failures["{$role} → {$label}"] = get_class($e) . ': ' . $e->getMessage();
                }
            }
        }

        $this->assertSame(
            [],
            $failures,
            'Ditemukan ' . count($failures) . ' halaman bermasalah saat lazy loading aktif:'
            . PHP_EOL . json_encode($failures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function test_pemeriksaan_show_detail_renders_temuan_with_opd(): void
    {
        // Kasus yang dilaporkan pengguna: membuka detail pemeriksaan yang memuat temuan.
        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();
        $pemeriksaan = Pemeriksaan::first();
        $this->assertNotNull($pemeriksaan);

        $response = $this->actingAs($user)->get(route('pemeriksaan.show', $pemeriksaan));

        $response->assertOk();
        $response->assertDontSee('LazyLoadingViolation');
        $response->assertDontSee('Attempted to lazy load');
        $response->assertSee($pemeriksaan->nomor_spt);
    }

    public function test_pages_do_not_render_error_indicators(): void
    {
        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();

        $pages = [
            'dashboard' => route('dashboard'),
            'pemeriksaan.index' => route('pemeriksaan.index'),
            'temuan.index' => route('temuan.index'),
            'lhp.index' => route('lhp.index'),
            'tindak-lanjut.index' => route('tindak-lanjut.index'),
            'laporan.index' => route('laporan.index'),
        ];

        foreach ($pages as $label => $url) {
            $html = $this->actingAs($user)->get($url)->getContent();

            foreach (['Whoops', 'Attempted to lazy load', 'Undefined variable', 'Call to a member function', 'Server Error'] as $bad) {
                $this->assertStringNotContainsString(
                    $bad,
                    $html,
                    "Halaman [{$label}] memuat indikator error: '{$bad}'."
                );
            }
        }
    }

    /**
     * Kasus yang dilaporkan pengguna pada /tindak-lanjut:
     * `Attempted to lazy load [lhp] on model [App\Models\TindakLanjut]`.
     *
     * View memakai `$temuan?->lhp ?? $tl->lhp`. Selama semua data uji punya
     * `temuan.lhp` terisi, cabang `$tl->lhp` tidak pernah dievaluasi sehingga
     * relasi lhp pada TindakLanjut tidak pernah diuji.
     *
     * Test ini sengaja membuat data yang memaksa evaluasi cabang tersebut.
     */
    public function test_tindak_lanjut_index_handles_temuan_without_lhp(): void
    {
        $this->ensureMultipleRowsForEveryModel();

        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();

        // Pastikan ada tindak lanjut yang temuan induknya tanpa LHP,
        // sehingga view mengevaluasi $tl->lhp sebagai fallback.
        $adaKasus = TindakLanjut::with('temuan')->get()->contains(
            fn ($tl) => $tl->temuan !== null && $tl->temuan->lhp_id === null
        );

        $this->assertTrue($adaKasus, 'Data uji harus memuat tindak lanjut dengan temuan tanpa LHP.');

        $response = $this->actingAs($user)->get(route('tindak-lanjut.index'));

        $response->assertOk();
        $response->assertDontSee('Attempted to lazy load');
        $response->assertDontSee('LazyLoadingViolation');
    }

    /**
     * Tindak lanjut yang merujuk temuan terhapus harus tetap menampilkan
     * konteks temuan (relasi memakai withTrashed()).
     */
    public function test_tindak_lanjut_index_handles_soft_deleted_temuan(): void
    {
        $this->ensureMultipleRowsForEveryModel();

        $user = User::where('email', 'superadmin@inspektorat.go.id')->first();

        $adaTemuanTerhapus = TindakLanjut::with('temuan')->get()->contains(
            fn ($tl) => $tl->temuan !== null && $tl->temuan->trashed()
        );

        $this->assertTrue($adaTemuanTerhapus, 'Data uji harus memuat tindak lanjut dengan temuan terhapus.');

        $response = $this->actingAs($user)->get(route('tindak-lanjut.index'));

        $response->assertOk();
        $response->assertDontSee('Attempted to lazy load');
    }
}
