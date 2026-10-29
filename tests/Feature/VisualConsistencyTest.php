<?php

namespace Tests\Feature;

use App\Models\Lhp;
use App\Models\Pemeriksaan;
use App\Models\Temuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guard konsistensi visual (Phase 4).
 *
 * Mengunci:
 *  - badge "Sumber Pemeriksaan" memakai satu skema warna (komponen Blade)
 *  - tidak ada lagi skema warna Sumber yang ditulis inline di view
 *  - label tabel laporan kerugian memakai label eksplisit, bukan "Nilai / Disetor / Sisa"
 *  - tombol filter & tautan detail memakai satu label yang seragam
 */
class VisualConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    /** Skema kanonik — satu-satunya sumber kebenaran. */
    private const SKEMA_KANONIK = [
        'BPK' => 'emerald',
        'BPKP' => 'blue',
        'ITJEN' => 'amber',
        'Inspektorat' => 'purple',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('email', 'superadmin@inspektorat.go.id')->first();
    }

    // =========================================================================
    // Badge Sumber Pemeriksaan
    // =========================================================================

    public function test_sumber_badge_component_exists(): void
    {
        $this->assertFileExists(
            resource_path('views/components/sumber-badge.blade.php'),
            'Komponen badge Sumber harus ada sebagai satu sumber kebenaran skema warna.'
        );
    }

    public function test_sumber_badge_renders_canonical_colour_per_source(): void
    {
        foreach (self::SKEMA_KANONIK as $sumber => $warna) {
            $html = view('components.sumber-badge', ['sumber' => $sumber])->render();

            $this->assertStringContainsString(
                "bg-{$warna}-100",
                $html,
                "Sumber {$sumber} harus memakai warna {$warna}."
            );
            $this->assertStringContainsString($sumber, $html);
        }
    }

    public function test_sumber_badge_falls_back_gracefully_for_unknown_source(): void
    {
        $html = view('components.sumber-badge', ['sumber' => null])->render();
        $this->assertStringContainsString('bg-gray-100', $html);

        $html2 = view('components.sumber-badge', ['sumber' => 'Sumber Ajaib'])->render();
        $this->assertStringContainsString('bg-gray-100', $html2);
        $this->assertStringContainsString('Sumber Ajaib', $html2);
    }

    public function test_no_inline_sumber_colour_schemes_remain_in_views(): void
    {
        // Skema warna hanya boleh hidup di dalam komponen.
        $allowed = 'components' . DIRECTORY_SEPARATOR . 'sumber-badge.blade.php';

        $offenders = [];
        foreach ($this->bladeFiles() as $relative => $full) {
            if (str_ends_with($relative, $allowed)) {
                continue;
            }

            $content = file_get_contents($full);

            // Pola skema lama: match(...sumber_pemeriksaan) { 'BPK' => ...
            if (preg_match("/match\s*\(\s*\\\$[A-Za-z_>?\-]+sumber_pemeriksaan\s*\)/", $content)) {
                $offenders[] = $relative;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Skema warna Sumber masih ditulis inline di: " . implode(', ', $offenders)
        );
    }

    public function test_bpk_and_bpkp_are_not_swapped_on_archive_page(): void
    {
        // Sebelumnya halaman arsip menukar warna BPK (blue) dan BPKP (emerald).
        $pemeriksaan = Pemeriksaan::first();
        $pemeriksaan->update(['sumber_pemeriksaan' => 'BPK']);
        $pemeriksaan->delete();

        $html = $this->actingAs($this->superAdmin)
            ->get(route('audit-log.trash', ['type' => 'pemeriksaan']))
            ->getContent();

        // Cari badge BPK dan pastikan memakai emerald, bukan blue
        $this->assertMatchesRegularExpression(
            '/bg-emerald-100[^>]*>\s*BPK/',
            $html,
            'Badge BPK di halaman arsip harus memakai emerald, bukan tertukar dengan BPKP.'
        );
    }

    public function test_sumber_badge_renders_on_primary_pages(): void
    {
        // Pastikan data punya sumber yang jelas agar badge pasti dirender
        $pemeriksaan = Pemeriksaan::first();
        $this->assertNotNull($pemeriksaan);
        $pemeriksaan->update(['sumber_pemeriksaan' => 'BPK']);

        $temuan = Temuan::first();
        $this->assertNotNull($temuan);

        $pages = [
            'pemeriksaan.index' => route('pemeriksaan.index'),
            'temuan.index' => route('temuan.index'),
            'lhp.index' => route('lhp.index'),
            'pemeriksaan.show' => route('pemeriksaan.show', $pemeriksaan),
            'temuan.show' => route('temuan.show', $temuan),
        ];

        foreach ($pages as $label => $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertOk();

            // Badge dari komponen membawa atribut title yang khas
            $response->assertSee('Sumber Pemeriksaan:', false);
        }
    }

    // =========================================================================
    // Label tabel laporan
    // =========================================================================

    public function test_kerugian_table_uses_explicit_value_labels(): void
    {
        $html = $this->actingAs($this->superAdmin)
            ->get(route('laporan.index', ['jenis' => 'kerugian']))
            ->getContent();

        // Label eksplisit menggantikan "Nilai / Disetor / Sisa" yang ambigu
        $this->assertStringContainsString('Nilai Kerugian (Rp)', $html);
        $this->assertStringContainsString('Telah Disetor (Rp)', $html);
        $this->assertStringContainsString('Sisa Kerugian (Rp)', $html);

        // Label lama yang membingungkan tidak boleh tersisa
        $this->assertStringNotContainsString('Nilai / Disetor / Sisa', $html);
    }

    // =========================================================================
    // Label tombol & tautan
    // =========================================================================

    public function test_filter_buttons_use_consistent_label(): void
    {
        $pages = [
            'tindak-lanjut.index' => route('tindak-lanjut.index'),
            'temuan.index' => route('temuan.index'),
            'pemeriksaan.index' => route('pemeriksaan.index'),
            'opd.index' => route('opd.index'),
            'pegawai.index' => route('pegawai.index'),
            'user.index' => route('user.index'),
        ];

        foreach ($pages as $label => $url) {
            $html = $this->actingAs($this->superAdmin)->get($url)->getContent();

            $this->assertStringContainsString(
                'Filter',
                $html,
                "Halaman [{$label}] harus memakai label tombol 'Filter'."
            );

            // Label menyimpang tidak boleh ada
            $this->assertStringNotContainsString('>Cari</button>', $html, "Halaman [{$label}] masih memakai label 'Cari'.");
        }
    }

    public function test_detail_links_use_consistent_label(): void
    {
        // Halaman arsip butuh data agar baris tabel (beserta tautannya) dirender
        Pemeriksaan::first()->delete();

        $pages = [
            // Tab rekap administrasi memuat kolom Aksi dengan tautan detail
            'laporan.index (rekap, administrasi)' => route('laporan.index', ['tab' => 'rekap', 'jenis' => 'administrasi']),
            'tindak-lanjut.index' => route('tindak-lanjut.index'),
            'audit-log.trash' => route('audit-log.trash'),
        ];

        foreach ($pages as $label => $url) {
            $html = $this->actingAs($this->superAdmin)->get($url)->getContent();

            $this->assertStringContainsString(
                'Lihat Detail',
                $html,
                "Halaman [{$label}] harus memakai label tautan 'Lihat Detail'."
            );
        }

        // Label menyimpang yang dulu dipakai tidak boleh tersisa
        $trash = $this->actingAs($this->superAdmin)->get(route('audit-log.trash'))->getContent();
        $this->assertStringNotContainsString('>Lihat Rincian<', $trash);
    }

    // =========================================================================
    // Helper
    // =========================================================================

    /**
     * @return array<string, string> path relatif => path absolut
     */
    private function bladeFiles(): array
    {
        $base = resource_path('views');
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $relative = substr($file->getPathname(), strlen($base) + 1);
                $files[$relative] = $file->getPathname();
            }
        }

        return $files;
    }
}
