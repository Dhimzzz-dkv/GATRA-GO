<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit relasi: menelusuri SEMUA halaman utama dengan lazy loading DIAKTIFKAN,
 * untuk menangkap relasi yang belum di-eager load.
 *
 * Test ini penting karena lingkungan produksi mengaktifkan preventLazyLoading.
 * Bila di test dimatikan, bug semacam ini hanya muncul di server instansi.
 */
class RelationEagerLoadTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();

        // Nyalakan strict lazy loading seperti di produksi.
        // Sebelumnya test mematikan ini, sehingga bug lazy loading tidak terdeteksi.
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(true);
    }

    protected function tearDown(): void
    {
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(false);
        parent::tearDown();
    }

    /**
     * @return array<string, string>
     */
    private function pages(): array
    {
        $pemeriksaan = \App\Models\Pemeriksaan::first();
        $temuan = \App\Models\Temuan::first();
        $lhp = \App\Models\Lhp::first();
        $opd = \App\Models\Opd::first();
        $pegawai = \App\Models\Pegawai::first();
        $user = User::where('email', 'admin.bpk@inspektorat.go.id')->first();

        $pages = [
            'dashboard' => route('dashboard'),
            'pemeriksaan.index' => route('pemeriksaan.index'),
            'temuan.index' => route('temuan.index'),
            'lhp.index' => route('lhp.index'),
            'tindak-lanjut.index' => route('tindak-lanjut.index'),
            'laporan.index' => route('laporan.index', ['jenis' => 'kerugian']),
            'laporan.index (administrasi)' => route('laporan.index', ['jenis' => 'administrasi']),
            'laporan.cetak' => route('laporan.cetak'),
            'pengajuan.index' => route('pengajuan.index'),
            'audit-log.index' => route('audit-log.index'),
            'audit-log.trash' => route('audit-log.trash'),
            'opd.index' => route('opd.index'),
            'pegawai.index' => route('pegawai.index'),
            'user.index' => route('user.index'),
        ];

        if ($pemeriksaan) {
            $pages['pemeriksaan.show'] = route('pemeriksaan.show', $pemeriksaan);
            $pages['pemeriksaan.edit'] = route('pemeriksaan.edit', $pemeriksaan);
        }
        if ($temuan) {
            $pages['temuan.show'] = route('temuan.show', $temuan);
        }
        if ($lhp) {
            $pages['lhp.show'] = route('pemeriksaan.lhp.show', [$lhp->pemeriksaan_id, $lhp->id]);
        }
        if ($opd) {
            $pages['opd.show'] = route('opd.show', $opd);
        }
        if ($pegawai) {
            $pages['pegawai.show'] = route('pegawai.show', $pegawai);
        }
        if ($user) {
            $pages['user.edit'] = route('user.edit', $user);
        }

        return $pages;
    }

    public function test_no_page_triggers_lazy_loading(): void
    {
        $failures = [];

        foreach ($this->pages() as $label => $url) {
            try {
                $response = $this->actingAs($this->superAdmin)->get($url);

                if ($response->getStatusCode() >= 500) {
                    $failures[$label] = 'HTTP ' . $response->getStatusCode();
                }
            } catch (\Illuminate\Database\LazyLoadingViolationException $e) {
                $failures[$label] = 'Lazy loading: ' . $e->getMessage();
            } catch (\Throwable $e) {
                $failures[$label] = get_class($e) . ': ' . $e->getMessage();
            }
        }

        $this->assertSame(
            [],
            $failures,
            "Halaman berikut gagal saat lazy loading diaktifkan (seperti di produksi):\n"
            . json_encode($failures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Menu "Pemeriksaan & SPT" harus dapat dibuka oleh SETIAP peran yang
     * melihatnya di sidebar. Sebelumnya halaman ini melempar 500 karena
     * relasi creator.pegawai diakses tanpa eager load.
     */
    public function test_pemeriksaan_index_opens_for_every_role(): void
    {
        $roles = [
            'Super Admin' => 'superadmin@inspektorat.go.id',
            'Admin Inspektorat' => 'admin.inspektorat@inspektorat.go.id',
            'Admin BPK' => 'admin.bpk@inspektorat.go.id',
            'Admin BPKP' => 'admin.bpkp@inspektorat.go.id',
            'Admin ITJEN' => 'admin.itjen@inspektorat.go.id',
            'Pemantau' => 'pemantau@gorontaloprov.go.id',
            'OPD' => 'opd.dinkes@gorontaloprov.go.id',
        ];

        foreach ($roles as $label => $email) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "Akun untuk peran {$label} tidak ditemukan.");

            $response = $this->actingAs($user)->get(route('pemeriksaan.index'));

            $this->assertSame(
                200,
                $response->getStatusCode(),
                "Menu 'Pemeriksaan & SPT' gagal dibuka oleh peran {$label} (HTTP {$response->getStatusCode()})."
            );
        }
    }

    public function test_pemeriksaan_pages_open_without_lazy_loading(): void
    {
        $pemeriksaan = \App\Models\Pemeriksaan::first();
        $this->assertNotNull($pemeriksaan);

        $urls = [
            'index' => route('pemeriksaan.index'),
            'show' => route('pemeriksaan.show', $pemeriksaan),
            'edit' => route('pemeriksaan.edit', $pemeriksaan),
            'create' => route('pemeriksaan.create'),
            'create-wizard' => route('pemeriksaan.create-wizard'),
        ];

        foreach ($urls as $label => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "Halaman pemeriksaan.{$label} gagal dibuka (HTTP {$response->getStatusCode()})."
            );
        }
    }

    public function test_pemeriksaan_index_renders_creator_name(): void
    {
        // Kolom "Penginput" membaca creator->official_name, yang di baliknya
        // memanggil relasi pegawai. Keduanya harus sudah dimuat.
        $html = $this->actingAs($this->superAdmin)
            ->get(route('pemeriksaan.index'))
            ->getContent();

        $this->assertStringContainsString('Penginput:', $html);
    }
}
