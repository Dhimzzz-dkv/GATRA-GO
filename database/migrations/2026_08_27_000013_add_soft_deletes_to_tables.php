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
        if (! Schema::hasColumn('opds', 'deleted_at')) {
            Schema::table('opds', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('pegawais', 'deleted_at')) {
            Schema::table('pegawais', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('opds', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
