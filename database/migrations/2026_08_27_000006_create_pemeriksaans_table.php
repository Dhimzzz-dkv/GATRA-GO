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
        Schema::create('pemeriksaans', function (Blueprint $table) {
            $table->id();
            $table->string('sumber_pemeriksaan'); // BPK, BPKP, ITJEN, Inspektorat
            $table->foreignId('opd_id')->constrained('opds')->cascadeOnDelete();
            $table->year('tahun'); // 2021-2026
            $table->string('judul')->nullable(); // Keterangan/Judul Pemeriksaan
            
            // SPT (Surat Perintah Tugas)
            $table->string('nomor_spt')->nullable();
            $table->date('tanggal_spt')->nullable();
            $table->string('dokumen_spt')->nullable(); // File path
            $table->string('nama_dokumen_spt_asli')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            // Status Pengajuan (Draft -> Diajukan -> Menunggu Persetujuan -> Disetujui / Perlu Revisi / Ditolak)
            $table->string('status_pengajuan')->default('DRAFT');
            $table->text('catatan_persetujuan')->nullable(); // Alasan revisi/penolakan atau catatan

            // Metadata Penginput & Arsip
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemeriksaans');
    }
};
