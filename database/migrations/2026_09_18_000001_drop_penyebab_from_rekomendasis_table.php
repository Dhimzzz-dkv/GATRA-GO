<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('rekomendasis', 'penyebab')) {
            Schema::table('rekomendasis', function (Blueprint $table) {
                $table->dropColumn('penyebab');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('rekomendasis', 'penyebab')) {
            Schema::table('rekomendasis', function (Blueprint $table) {
                $table->text('penyebab')->nullable()->after('uraian_rekomendasi');
            });
        }
    }
};
