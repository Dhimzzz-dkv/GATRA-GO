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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('pegawai_id')->nullable()->after('role_id')->constrained('pegawais')->nullOnDelete();
            $table->foreignId('opd_id')->nullable()->after('pegawai_id')->constrained('opds')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pegawai_id']);
            $table->dropForeign(['opd_id']);
            $table->dropColumn(['pegawai_id', 'opd_id']);
        });
    }
};
