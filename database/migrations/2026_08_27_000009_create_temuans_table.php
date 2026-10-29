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
        Schema::create('temuans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_temuan'); // e.g. TEM-BPK-2025-001
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaans')->cascadeOnDelete();
            $table->foreignId('lhp_id')->nullable()->constrained('lhps')->nullOnDelete();
            $table->string('sumber_pemeriksaan'); // BPK, BPKP, ITJEN, Inspektorat
            $table->foreignId('opd_id')->constrained('opds')->cascadeOnDelete();
            $table->year('tahun');

            // Uraian Temuan
            $table->string('judul_temuan');
            $table->longText('uraian_temuan');
            $table->text('penyebab')->nullable();
            $table->text('akibat')->nullable();
            $table->text('rekomendasi')->nullable();

            // Administrasi & Kerugian (PRD Seksi 12 - Bukan opsi eksklusif)
            $table->boolean('ada_unsur_administrasi')->default(false);
            $table->text('uraian_administrasi')->nullable();
            $table->boolean('ada_unsur_kerugian')->default(false);
            $table->decimal('nilai_kerugian', 18, 2)->default(0); // Nilai nominal kerugian
            $table->text('uraian_kerugian')->nullable();

            // Status Pengajuan (DRAFT, DIAJUKAN, MENUNGGU PERSETUJUAN, DISETUJUI, PERLU REVISI, DITOLAK)
            $table->string('status_pengajuan')->default('DRAFT');
            $table->text('catatan_revisi')->nullable();

            // Status Penyelesaian (PRD Seksi 15 & 16)
            // Belum Ditindaklanjuti, Belum Sesuai, Sesuai, Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah
            $table->string('status_penyelesaian')->default('Belum Ditindaklanjuti');
            $table->text('alasan_khusus')->nullable();
            $table->string('dokumen_pendukung_alasan')->nullable();

            // Berkas Pendukung Temuan
            $table->string('dokumen_pendukung')->nullable();
            $table->string('nama_dokumen_asli')->nullable();

            // Audit & Metadata Penginput
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('temuans');
    }
};
