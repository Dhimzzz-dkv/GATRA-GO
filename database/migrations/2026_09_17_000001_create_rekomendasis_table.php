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
        Schema::create('rekomendasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temuan_id')->constrained('temuans')->cascadeOnDelete();
            $table->text('uraian_rekomendasi');
            $table->string('status_penyelesaian')->default('Belum Ditindaklanjuti');
            $table->text('alasan_khusus')->nullable();
            $table->string('dokumen_pendukung_alasan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('tindak_lanjuts', function (Blueprint $table) {
            $table->foreignId('rekomendasi_id')->nullable()->after('temuan_id')->constrained('rekomendasis')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tindak_lanjuts', function (Blueprint $table) {
            $table->dropForeign(['rekomendasi_id']);
            $table->dropColumn('rekomendasi_id');
        });

        Schema::dropIfExists('rekomendasis');
    }
};
