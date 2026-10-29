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
            $table->foreignId('lhp_id')->nullable()->after('id')->constrained('lhps')->cascadeOnDelete();
            $table->foreignId('temuan_id')->nullable()->change();
            $table->date('tanggal_setor')->nullable()->after('nomor_bukti_setor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindak_lanjuts', function (Blueprint $table) {
            $table->dropForeign(['lhp_id']);
            $table->dropColumn(['lhp_id', 'tanggal_setor']);
        });
    }
};
