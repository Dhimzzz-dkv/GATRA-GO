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
        Schema::create('tindak_lanjuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('temuan_id')->constrained('temuans')->cascadeOnDelete();
            $table->foreignId('opd_id')->constrained('opds')->cascadeOnDelete();
            $table->date('tanggal_tindak_lanjut');
            $table->longText('uraian_tindak_lanjut');
            $table->decimal('jumlah_setor', 18, 2)->default(0); // Nominal setoran kas daerah
            $table->string('nomor_bukti_setor')->nullable(); // Nomor STS/NTPN/Kuitansi
            $table->string('dokumen_bukti')->nullable(); // File PDF/DOC/JPG
            $table->string('nama_dokumen_asli')->nullable();

            // Verifikasi oleh Inspektorat
            $table->string('status_verifikasi')->default('MENUNGGU VERIFIKASI'); // MENUNGGU VERIFIKASI, SESUAI, BELUM SESUAI, DITOLAK
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            // Audit
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
        Schema::dropIfExists('tindak_lanjuts');
    }
};
