<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemeriksaans', function (Blueprint $table): void {
            $table->string('jenis_pengajuan', 32)
                ->default('SPT_DAN_LHP')
                ->index()
                ->after('sumber_pemeriksaan');
        });

        DB::table('pemeriksaans')
            ->where('sumber_pemeriksaan', '!=', 'Inspektorat')
            ->update(['jenis_pengajuan' => 'LHP_ONLY']);
    }

    public function down(): void
    {
        Schema::table('pemeriksaans', function (Blueprint $table): void {
            $table->dropIndex(['jenis_pengajuan']);
            $table->dropColumn('jenis_pengajuan');
        });
    }
};
