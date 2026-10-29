<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WizardRenderTest extends TestCase
{
    use RefreshDatabase;

    private function wizardHtml(): string
    {
        $this->seed();

        $adminInsp = \App\Models\User::where('email', 'admin.inspektorat@inspektorat.go.id')->first();

        return $this->actingAs($adminInsp)
            ->get(route('pemeriksaan.create-wizard'))
            ->getContent();
    }

    /**
     * @return array<int, string>
     */
    private function inlineScripts(string $html): array
    {
        preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>(.*?)<\/script>/s', $html, $m);

        return array_values(array_filter(
            $m[1] ?? [],
            fn ($code) => trim($code) !== ''
        ));
    }

    private function assertNoUndefinedTemplateVars(string $code, string $label): void
    {
        // Nama variabel yang HARUS ada di dalam template literal JS
        $templateVars = [];
        if (preg_match_all('/\$\{\s*([A-Za-z_$][A-Za-z0-9_$]*)\s*\}/', $code, $mm)) {
            $templateVars = array_unique($mm[1]);
        }

        if ($templateVars === []) {
            return;
        }

        foreach ($templateVars as $var) {
            $this->assertTrue(
                $this->isDefinedInCode($code, $var),
                "Variabel JS '\${$var}' dipakai di template literal pada [{$label}] tetapi tidak pernah dideklarasikan/di-assign. "
                . 'Ini menyebabkan ReferenceError saat fungsi dijalankan.'
            );
        }
    }

    private function isDefinedInCode(string $code, string $var): bool
    {
        $patterns = [
            "/\blet\s+\Q{$var}\E\b/",
            "/\bconst\s+\Q{$var}\E\b/",
            "/\bvar\s+\Q{$var}\E\b/",
            "/function\s+\Q{$var}\E\s*\(/",
            // parameter fungsi
            "/function\s*\([^)]*\b\Q{$var}\E\b[^)]*\)/",
            "/\([^)]*\b\Q{$var}\E\b[^)]*\)\s*=>/",
            "/\bforEach\s*\(\s*\(?\s*\Q{$var}\E\b/",
            "/\bcatch\s*\(\s*\Q{$var}\E\b/",
            // object property shorthand / destructuring
            "/\{\s*\Q{$var}\E\s*\}\s*=/",
            // object literal key
            "/\Q{$var}\E\s*[:,]/",
            "/\.\Q{$var}\E\b/",
        ];

        foreach ($patterns as $p) {
            if (preg_match($p, $code)) {
                return true;
            }
        }

        return false;
    }

    public function test_wizard_inline_scripts_have_valid_javascript_syntax(): void
    {
        $scripts = $this->inlineScripts($this->wizardHtml());
        $this->assertNotEmpty($scripts);

        $dir = sys_get_temp_dir() . '/wizard_script_check';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $checked = 0;
        foreach ($scripts as $i => $code) {
            $file = $dir . "/block_{$i}.js";
            file_put_contents($file, $code);

            $result = [];
            $exit = 1;
            exec('node --check ' . escapeshellarg($file) . ' 2>&1', $result, $exit);

            $this->assertSame(
                0,
                $exit,
                "Script block #{$i} memiliki syntax error:\n" . implode("\n", $result)
            );

            $checked++;
        }

        $this->assertGreaterThanOrEqual(5, $checked, 'Harus ada script wizard utama + 4 step');
    }

    public function test_no_undefined_javascript_variables_in_step_templates(): void
    {
        $html = $this->wizardHtml();
        $scripts = implode("\n", $this->inlineScripts($html));

        // Nama variabel yang pernah kita hapus / tidak pernah dideklarasikan
        $this->assertNoUndefinedTemplateVars($scripts, 'wizard scripts');
    }

    public function test_lhp_card_template_uses_dom_derived_index(): void
    {
        $scripts = implode("\n", $this->inlineScripts($this->wizardHtml()));

        // Ambil body fungsi addLhpCard
        $this->assertSame(1, preg_match('/function addLhpCard\(\)\s*\{(.*?)\n\}/s', $scripts, $m), 'Fungsi addLhpCard tidak ditemukan');
        $body = $m[1];

        // Index kartu LHP harus dihitung dari DOM, bukan variabel global
        $this->assertStringContainsString(
            "container.querySelectorAll('.lhp-card').length",
            $body
        );

        // Tidak boleh ada referensi ke variabel global lhpIndex yang sudah dihapus
        $this->assertStringNotContainsString('${lhpIndex', $body);

        // Semua variabel template literal di body harus terdefinisi
        $this->assertNoUndefinedTemplateVars($body, 'addLhpCard');
    }

    public function test_wizard_step_partials_are_all_included(): void
    {
        $html = $this->wizardHtml();

        foreach (['wizard-step-1', 'wizard-step-2', 'wizard-step-3', 'wizard-step-4'] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html, "Step {$id} tidak ter-render");
        }

        $this->assertStringContainsString('id="btn-tambah-lhp"', $html);
        $this->assertStringContainsString('id="tim-pemeriksa-container"', $html);
        $this->assertStringContainsString('id="lhp-cards-container"', $html);
        $this->assertStringContainsString('id="lhp-temuan-container"', $html);
    }
}