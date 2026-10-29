<?php

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\User;
use App\Services\SignatoryResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard penandatangan laporan cetak.
 *
 * Sebelumnya nama dan NIP kedua penandatangan ditulis tetap di dalam blade
 * `laporan/cetak.blade.php` — bahkan tidak ada di master Pegawai — sehingga
 * laporan resmi mencetak pejabat yang salah dan tidak dapat diperbaiki
 * oleh Super Admin.
 */
class SignatoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
    }

    public function test_signatories_are_resolved_from_pegawai_master_data(): void
    {
        $ttd = SignatoryResolver::forLaporan();

        foreach (['pembuat', 'pengesah'] as $pihak) {
            $this->assertNotEmpty($ttd[$pihak]['nama'], "Nama penandatangan [{$pihak}] harus terisi dari master Pegawai.");
            $this->assertNotNull($ttd[$pihak]['nip'], "NIP penandatangan [{$pihak}] harus terisi.");

            // Nama harus benar-benar ada di tabel pegawais
            $this->assertTrue(
                Pegawai::where('nama', $ttd[$pihak]['nama'])->exists(),
                "Penandatangan [{$pihak}] harus berasal dari master Pegawai."
            );
        }
    }

    public function test_signing_official_is_the_inspektur_daerah(): void
    {
        $ttd = SignatoryResolver::forLaporan();

        $this->assertStringContainsString('Inspektur Daerah', $ttd['pengesah']['jabatan_label']);

        $pegawai = Pegawai::where('nama', $ttd['pengesah']['nama'])->first();
        $this->assertNotNull($pegawai);
        $this->assertStringContainsString('Inspektur Daerah', $pegawai->jabatan);
    }

    public function test_print_page_renders_master_data_not_hardcoded_names(): void
    {
        $html = $this->actingAs($this->superAdmin)
            ->get(route('laporan.cetak'))
            ->getContent();

        $ttd = SignatoryResolver::forLaporan();

        // Nama dari master harus muncul
        $this->assertStringContainsString($ttd['pengesah']['nama'], $html);
        $this->assertStringContainsString($ttd['pembuat']['nama'], $html);

        // Nama hardcoded lama tidak boleh tersisa
        $this->assertStringNotContainsString('RAHMAT S. ISMAIL', $html);
        $this->assertStringNotContainsString('ZUKRI SUROTINOJO', $html);
        $this->assertStringNotContainsString('19850612 201001 1 012', $html);
        $this->assertStringNotContainsString('19751110 199412 1 002', $html);
    }

    public function test_explicit_nip_config_overrides_jabatan_match(): void
    {
        $target = Pegawai::where('jabatan', 'Auditor Ahli Pertama')->first();
        $this->assertNotNull($target, 'Butuh pegawai dengan jabatan Auditor Ahli Pertama.');

        config()->set('simantap.penandatangan.pembuat.nip', $target->nip);
        config()->set('simantap.penandatangan.pembuat.jabatan_match', 'Inspektur Daerah');

        $ttd = SignatoryResolver::resolve('pembuat');

        $this->assertSame(
            $target->nama,
            $ttd['nama'],
            'NIP eksplisit pada config harus menang atas pencocokan jabatan.'
        );
    }

    public function test_missing_signatory_produces_safe_fallback(): void
    {
        config()->set('simantap.penandatangan.pengesah.nip', '99999999 999999 9 999');
        config()->set('simantap.penandatangan.pengesah.jabatan_match', 'Jabatan Yang Tidak Ada');

        $ttd = SignatoryResolver::resolve('pengesah');

        $this->assertSame('', $ttd['nama']);
        $this->assertNotNull($ttd['jabatan_label']);

        // Halaman mustah tetap dapat dirender, dengan penanda yang jelas
        $html = $this->actingAs($this->superAdmin)->get(route('laporan.cetak'))->getContent();
        $this->assertStringContainsString('Penandatangan belum diatur', $html);
    }

    public function test_signatory_config_file_exists_and_is_documented(): void
    {
        $this->assertFileExists(config_path('simantap.php'));

        $this->assertNotNull(config('simantap.penandatangan.pembuat'));
        $this->assertNotNull(config('simantap.penandatangan.pengesah'));
        $this->assertSame('Inspektur Daerah Provinsi Gorontalo', config('simantap.penandatangan.pengesah.jabatan_label'));
    }
}
