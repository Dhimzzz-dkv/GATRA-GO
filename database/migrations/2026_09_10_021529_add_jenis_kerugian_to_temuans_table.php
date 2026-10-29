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
        Schema::table('temuans', function (Blueprint $table) {
            $table->enum('jenis_kerugian', ['Internal', 'Eksternal'])->nullable()->after('ada_unsur_kerugian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temuans', function (Blueprint $table) {
            $table->dropColumn('jenis_kerugian');
        });
    }
};
