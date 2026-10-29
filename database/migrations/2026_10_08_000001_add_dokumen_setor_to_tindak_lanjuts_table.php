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
        Schema::table('tindak_lanjuts', function (Blueprint $table) {
            if (! Schema::hasColumn('tindak_lanjuts', 'dokumen_setor')) {
                $table->string('dokumen_setor')->nullable()->after('nama_dokumen_asli');
            }
            if (! Schema::hasColumn('tindak_lanjuts', 'nama_dokumen_setor_asli')) {
                $table->string('nama_dokumen_setor_asli')->nullable()->after('dokumen_setor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindak_lanjuts', function (Blueprint $table) {
            $table->dropColumn(['dokumen_setor', 'nama_dokumen_setor_asli']);
        });
    }
};