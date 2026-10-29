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
        Schema::create('lhps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaans')->cascadeOnDelete();
            $table->string('nomor_lhp');
            $table->date('tanggal_lhp');
            $table->string('judul_lhp')->nullable();
            $table->string('dokumen_lhp')->nullable(); // File path PDF/DOC
            $table->string('nama_dokumen_lhp_asli')->nullable();
            $table->text('ringkasan')->nullable(); // Ringkasan eksekutif
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lhps');
    }
};
