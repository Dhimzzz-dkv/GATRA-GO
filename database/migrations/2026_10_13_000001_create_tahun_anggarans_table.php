<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_anggarans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('tahun')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        $currentYear = (int) now()->year;
        $years = range(2020, max(2030, $currentYear + 5));

        if (Schema::hasTable('pemeriksaans')) {
            $years = array_merge($years, DB::table('pemeriksaans')->distinct()->pluck('tahun')->map(fn ($year) => (int) $year)->all());
        }

        foreach (array_unique($years) as $year) {
            DB::table('tahun_anggarans')->insert([
                'tahun' => $year,
                'is_active' => true,
                'is_current' => $year === $currentYear,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_anggarans');
    }
};
