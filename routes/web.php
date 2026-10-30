<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AccountActivationController;
use App\Http\Controllers\Auth\AccountPasswordResetController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LhpController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PemeriksaanController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\RekomendasiController;
use App\Http\Controllers\TemuanController;
use App\Http\Controllers\TimPemeriksaController;
use App\Http\Controllers\TahunAnggaranController;
use App\Http\Controllers\TindakLanjutController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public account activation routes. A user may open an activation link while
// another account (for example, Super Admin) is signed in in the same browser.
Route::get('/aktivasi-akun/{token}', [AccountActivationController::class, 'show'])
    ->middleware('throttle:10,1')
    ->name('account.activation.show');
Route::post('/aktivasi-akun', [AccountActivationController::class, 'activate'])
    ->middleware('throttle:6,1')
    ->name('account.activation.complete');

// Password recovery is public so reset links work while another account is signed in.
Route::get('/lupa-kata-sandi', [AccountPasswordResetController::class, 'requestForm'])
    ->middleware('throttle:20,1')
    ->name('password.request');
Route::post('/lupa-kata-sandi', [AccountPasswordResetController::class, 'sendResetLink'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/atur-ulang-kata-sandi/{token}', [AccountPasswordResetController::class, 'showResetForm'])
    ->middleware('throttle:10,1')
    ->name('password.reset');
Route::post('/atur-ulang-kata-sandi', [AccountPasswordResetController::class, 'reset'])
    ->middleware('throttle:6,1')
    ->name('password.update');

// Verifikasi Dua Langkah (2FA OTP via Email)
Route::get('/verifikasi-2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
Route::post('/verifikasi-2fa', [TwoFactorController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('2fa.verify');
Route::post('/verifikasi-2fa/kirim-ulang', [TwoFactorController::class, 'resend'])
    ->middleware('throttle:3,1')
    ->name('2fa.resend');
Route::post('/verifikasi-2fa/batal', [TwoFactorController::class, 'cancel'])->name('2fa.cancel');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', fn () => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Antrean Pengajuan & Persetujuan (Super Admin)
    Route::get('/pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
    Route::get('/pengajuan-saya', [PengajuanController::class, 'mine'])->name('pengajuan-saya.index');

    // Alur Pengajuan Pemeriksaan
    Route::post('pemeriksaan/{pemeriksaan}/ajukan', [PengajuanController::class, 'submitPemeriksaan'])->name('pemeriksaan.ajukan');
    Route::post('pemeriksaan/{pemeriksaan}/batal-ajukan', [PengajuanController::class, 'cancelPemeriksaan'])->name('pemeriksaan.batal-ajukan');
    Route::post('pengajuan/pemeriksaan/{pemeriksaan}/approve', [PengajuanController::class, 'approvePemeriksaan'])->name('pengajuan.pemeriksaan.approve');
    Route::post('pengajuan/pemeriksaan/{pemeriksaan}/revise', [PengajuanController::class, 'revisePemeriksaan'])->name('pengajuan.pemeriksaan.revise');

    // Data Pemeriksaan & SPT (Accessible with role scoping)
    Route::get('pemeriksaan/create-wizard', [PemeriksaanController::class, 'createWizard'])->name('pemeriksaan.create-wizard');
    Route::get('pemeriksaan/{pemeriksaan}/download-spt', [PemeriksaanController::class, 'downloadSpt'])->name('pemeriksaan.download-spt');
    Route::post('pemeriksaan/{pemeriksaan}/archive', [PemeriksaanController::class, 'archive'])->name('pemeriksaan.archive');
    Route::resource('pemeriksaan', PemeriksaanController::class);

    // Tim Pemeriksa
    Route::post('pemeriksaan/{pemeriksaan}/tim', [TimPemeriksaController::class, 'store'])->name('pemeriksaan.tim.store');
    Route::delete('pemeriksaan/{pemeriksaan}/tim/{timPemeriksa}', [TimPemeriksaController::class, 'destroy'])->name('pemeriksaan.tim.destroy');

    // LHP (Laporan Hasil Pemeriksaan)
    Route::get('lhp', [LhpController::class, 'index'])->name('lhp.index');
    Route::get('lhp/create', [LhpController::class, 'create'])->name('lhp.create');
    Route::post('lhp/store-direct', [LhpController::class, 'storeDirect'])->name('lhp.store-direct');
    Route::get('pemeriksaan/{pemeriksaan}/lhp/{lhp}', [LhpController::class, 'show'])->name('pemeriksaan.lhp.show');
    Route::get('pemeriksaan/{pemeriksaan}/lhp/{lhp}/edit', [LhpController::class, 'edit'])->name('pemeriksaan.lhp.edit');
    Route::post('pemeriksaan/{pemeriksaan}/lhp', [LhpController::class, 'store'])->name('pemeriksaan.lhp.store');
    Route::put('pemeriksaan/{pemeriksaan}/lhp/{lhp}', [LhpController::class, 'update'])->name('pemeriksaan.lhp.update');
    Route::get('pemeriksaan/{pemeriksaan}/lhp/{lhp}/download', [LhpController::class, 'download'])->name('pemeriksaan.lhp.download');
    Route::delete('pemeriksaan/{pemeriksaan}/lhp/{lhp}', [LhpController::class, 'destroy'])->name('pemeriksaan.lhp.destroy');

    // Data Temuan
    Route::get('temuan/{temuan}/download-dokumen', [TemuanController::class, 'downloadDokumen'])->name('temuan.download-dokumen');
    Route::patch('temuan/{temuan}/jenis-kerugian', [TemuanController::class, 'updateJenisKerugian'])->name('temuan.update-jenis-kerugian');
    Route::resource('temuan', TemuanController::class);

    // Rekomendasi Temuan Administrasi
    Route::post('temuan/{temuan}/rekomendasi', [RekomendasiController::class, 'store'])->name('temuan.rekomendasi.store');
    Route::delete('temuan/{temuan}/rekomendasi/{rekomendasi}', [RekomendasiController::class, 'destroy'])->name('temuan.rekomendasi.destroy');

    // Tindak Lanjut Terpadu & Verifikasi
    Route::get('tindak-lanjut', [TindakLanjutController::class, 'index'])->name('tindak-lanjut.index');
    Route::post('temuan/{temuan}/tindak-lanjut', [TindakLanjutController::class, 'store'])->name('temuan.tindak-lanjut.store');
    Route::get('temuan/{temuan}/tindak-lanjut/{tindakLanjut}/download', [TindakLanjutController::class, 'downloadBukti'])->name('temuan.tindak-lanjut.download');
    Route::post('temuan/{temuan}/tindak-lanjut/{tindakLanjut}/verifikasi', [TindakLanjutController::class, 'verifikasi'])->name('temuan.tindak-lanjut.verifikasi');
    Route::delete('temuan/{temuan}/tindak-lanjut/{tindakLanjut}', [TindakLanjutController::class, 'destroy'])->name('temuan.tindak-lanjut.destroy');

    // Laporan, Cetak & Ekspor
    Route::get('laporan/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
    Route::get('laporan/export-csv', [LaporanController::class, 'exportCsv'])->name('laporan.export-csv');
    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');

    // Audit Trail & Arsip Pemulihan
    Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('arsip-data', [AuditLogController::class, 'trash'])->name('audit-log.trash');
    Route::get('arsip-data/pemeriksaan/{id}', [AuditLogController::class, 'showArchivedPemeriksaan'])->name('audit-log.show-pemeriksaan');
    Route::get('arsip-data/pemeriksaan/{id}/download-spt', [AuditLogController::class, 'downloadArchivedSpt'])->name('audit-log.download-spt');
    Route::post('arsip-data/{type}/{id}/restore', [AuditLogController::class, 'restore'])->name('audit-log.restore');
    Route::delete('arsip-data/{type}/{id}/force-delete', [AuditLogController::class, 'forceDelete'])->name('audit-log.force-delete');

    // Super Admin only routes (Master Data)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('tahun-anggaran', [TahunAnggaranController::class, 'index'])->name('tahun-anggaran.index');
        Route::post('tahun-anggaran', [TahunAnggaranController::class, 'store'])->name('tahun-anggaran.store');
        Route::patch('tahun-anggaran/{tahunAnggaran}/status', [TahunAnggaranController::class, 'toggleStatus'])->name('tahun-anggaran.toggle-status');
        Route::patch('tahun-anggaran/{tahunAnggaran}/current', [TahunAnggaranController::class, 'setCurrent'])->name('tahun-anggaran.set-current');
        Route::delete('tahun-anggaran/{tahunAnggaran}', [TahunAnggaranController::class, 'destroy'])->name('tahun-anggaran.destroy');

        // Master OPD
        Route::patch('opd/{opd}/toggle-status', [OpdController::class, 'toggleStatus'])->name('opd.toggle-status');
        Route::resource('opd', OpdController::class);

        // Master Pegawai Inspektorat
        Route::patch('pegawai/{pegawai}/toggle-status', [PegawaiController::class, 'toggleStatus'])->name('pegawai.toggle-status');
        Route::resource('pegawai', PegawaiController::class);

        // Manajemen Pengguna
        Route::patch('user/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('user.toggle-status');
        Route::post('user/{user}/send-activation', [UserController::class, 'sendActivation'])->name('user.send-activation');
        Route::post('user/{user}/send-password-reset', [UserController::class, 'sendPasswordReset'])
            ->middleware('throttle:3,1')
            ->name('user.send-password-reset');
        Route::resource('user', UserController::class);
    });
});
