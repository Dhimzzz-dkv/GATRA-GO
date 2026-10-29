<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
            || request()->isSecure()
            || str_contains(request()->getHost(), 'ngrok')
            || str_starts_with(config('app.url'), 'https://')
        ) {
            URL::forceScheme('https');
        }

        // preventLazyLoading aktif di SEMUA lingkungan, termasuk saat test.
        //
        // PENTING: jangan menonaktifkan preventLazyLoading saat test. Bila
        // dimatikan, test tidak lagi mewakili perilaku produksi dan bug lazy
        // loading hanya akan muncul di server instansi — bukan di CI.
        // Penjaganya: RelationEagerLoadTest::test_no_page_triggers_lazy_loading
        Model::preventLazyLoading();

        // Mode ketat aktif di semua lingkungan non-produksi — TERMASUK saat test.
        //
        // PENTING: jangan matikan mode ketat ini saat test.
        //
        // Pernah terjadi: preventAccessingMissingAttributes() dimatikan saat test
        // sehingga bug nyata (AuditLogger membaca kolom `catatan_revisi` yang
        // tidak ada pada model Pemeriksaan) LOLOS dari pengujian dan baru muncul
        // sebagai HTTP 500 di runtime — mematikan 5 endpoint pengajuan sekaligus.
        //
        // Test harus mewakili perilaku runtime. Penjaganya:
        //   - AuditLoggerRobustnessTest::test_mode_ketat_aktif_saat_test
        //   - RelationEagerLoadTest::test_no_page_triggers_lazy_loading
        if (! $this->app->environment('production')) {
            Model::preventSilentlyDiscardingAttributes();
            Model::preventAccessingMissingAttributes();
        }
    }
}
