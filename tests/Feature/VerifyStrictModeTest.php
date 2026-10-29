<?php

namespace Tests\Feature;

use App\Models\Temuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyStrictModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_result_also_enforces_lazy_loading(): void
    {
        $this->seed();

        // Model hasil first() harus memiliki flag aktif, sama seperti hasil get().
        $temuan = Temuan::query()->first();
        $this->assertNotNull($temuan);

        $this->assertTrue(
            $temuan->preventsLazyLoading,
            'Model hasil first() harus ikut diperiksa, bukan hanya hasil get()/paginate().'
        );

        // Buktikan benar-benar menegakkan: relasi yang belum dimuat harus melempar.
        $this->expectException(\Illuminate\Database\LazyLoadingViolationException::class);
        $temuan->opd;
    }

    public function test_route_model_binding_enforces_lazy_loading(): void
    {
        $this->seed();

        $temuan = Temuan::query()->first();
        $this->assertNotNull($temuan);

        $bound = (new Temuan)->resolveRouteBinding($temuan->getKey());

        $this->assertNotNull($bound);
        $this->assertTrue(
            $bound->preventsLazyLoading,
            'Model hasil route binding harus ikut diperiksa.'
        );
    }

    public function test_eager_loaded_relation_does_not_throw(): void
    {
        $this->seed();

        // Relasi yang sudah dimuat harus tetap dapat diakses.
        $temuan = Temuan::query()->with('opd')->first();
        $this->assertNotNull($temuan);
        $this->assertTrue($temuan->relationLoaded('opd'));

        $this->assertNotNull($temuan->opd);
    }
}
