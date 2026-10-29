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
        Schema::create('opds', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique(); // e.g. DINKES, DIKBUD, BAPPEDA
            $table->string('nama'); // e.g. Dinas Kesehatan Provinsi Gorontalo
            $table->string('singkatan')->nullable();
            $table->string('kepala_opd')->nullable();
            $table->string('nip_kepala')->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
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
        Schema::dropIfExists('opds');
    }
};
