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
        Schema::create('pegawais', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique()->nullable();
            $table->string('nama'); // Nama lengkap beserta gelar
            $table->string('jabatan'); // e.g. Inspektur, Inspektur Pembantu Wilayah I, Auditor Ahli Madya, PPUPD
            $table->string('pangkat_golongan')->nullable(); // e.g. Pembina Utama Muda (IV/c), Penata Tk. I (III/d)
            $table->string('unit_bidang'); // e.g. Sekretariat, Irban I, Irban II, Irban III, Irban Khusus
            $table->string('no_hp')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawais');
    }
};
