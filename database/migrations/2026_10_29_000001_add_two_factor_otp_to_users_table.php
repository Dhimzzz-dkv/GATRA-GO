<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom OTP untuk verifikasi dua langkah (2FA) via email.
     *
     * Kolom ini dipakai oleh TwoFactorController untuk menyimpan kode OTP
     * sementara beserta waktu kedaluwarsanya. Kode dihapus setelah berhasil
     * diverifikasi atau setelah kedaluwarsa.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code', 64)->nullable()->after('remember_token');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_code', 'otp_expires_at']);
        });
    }
};

