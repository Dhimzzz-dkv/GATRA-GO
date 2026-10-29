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
        // 1. Buat tabel pivot lhp_opd untuk relasi Many-to-Many LHP dan OPD
        if (! Schema::hasTable('lhp_opd')) {
            Schema::create('lhp_opd', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lhp_id')->constrained('lhps')->cascadeOnDelete();
                $table->foreignId('opd_id')->constrained('opds')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['lhp_id', 'opd_id']);
            });
        }

        // 2. Ubah kolom opd_id pada pemeriksaans menjadi nullable
        Schema::table('pemeriksaans', function (Blueprint $table) {
            $table->foreignId('opd_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lhp_opd');

        Schema::table('pemeriksaans', function (Blueprint $table) {
            $table->foreignId('opd_id')->nullable(false)->change();
        });
    }
};
