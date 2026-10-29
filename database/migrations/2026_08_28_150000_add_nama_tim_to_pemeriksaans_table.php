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
        Schema::table('pemeriksaans', function (Blueprint $table) {
            if (! Schema::hasColumn('pemeriksaans', 'nama_tim')) {
                $table->string('nama_tim')->nullable()->after('nomor_spt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemeriksaans', function (Blueprint $table) {
            if (Schema::hasColumn('pemeriksaans', 'nama_tim')) {
                $table->dropColumn('nama_tim');
            }
        });
    }
};
