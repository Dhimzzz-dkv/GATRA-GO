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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. super_admin, admin_bpk
            $table->string('display_name'); // e.g. Super Admin, Admin BPK
            $table->text('description')->nullable();
            $table->string('sumber_pemeriksaan')->nullable(); // BPK, BPKP, ITJEN, Inspektorat
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
