<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Memeriksa konfigurasi .env untuk kesiapan produksi.
 *
 * Latar belakang: template `.env.production.example` sudah benar, tetapi
 * `.env` aktif tidak dapat di-commit (memuat kredensial). Akibatnya nilai
 * pengembangan — terutama APP_DEBUG=true — mudah terbawa ke server produksi.
 *
 * Bila APP_DEBUG=true di produksi, setiap error menampilkan stack trace,
 * potongan kode, dan variabel lingkungan (termasuk kredensial database)
 * kepada pengguna biasa.
 *
 * Command ini dijalankan sebagai langkah WAJIB sebelum deploy, dan mengembalikan
 * exit code ≠ 0 bila menemukan konfigurasi berbahaya sehingga dapat dipakai
 * dalam skrip otomatis (CI/CD atau prosedur rilis).
 */
class CheckProductionConfig extends Command
{
    protected $signature = 'app:check-production-config
                            {--loloskan-env-lokal : Perlakukan lingkungan lokal sebagai wajar (untuk pengujian)}';

    protected $description = 'Memeriksa .env dari nilai konfigurasi yang berbahaya untuk produksi';

    /**
     * Hasil pemeriksaan: [level, nama, nilai saat ini, alasan]
     *
     * @var array<int, array{level: string, nama: string, nilai: string, alasan: string}>
     */
    private array $temuan = [];

    public function handle(): int
    {
        $this->temuan = [];

        $this->periksaAplikasi();
        $this->periksaLogging();
        $this->periksaSesi();
        $this->periksaEmail();
        $this->periksaSeeder();

        return $this->laporkan();
    }

    // =========================================================================
    // Pemeriksaan
    // =========================================================================

    private function periksaAplikasi(): void
    {
        $env = (string) config('app.env');
        $debug = config('app.debug');
        $key = (string) config('app.key');
        $url = (string) config('app.url');

        if ($env !== 'production') {
            // Di lingkungan lokal, ini wajar — tandai sebagai peringatan saja
            // kecuali pemanggil meminta pemeriksaan ketat.
            $this->catat(
                $this->option('loloskan-env-lokal') ? 'info' : 'bahaya',
                'APP_ENV',
                $env,
                'Produksi harus memakai APP_ENV=production agar jalur kode produksi aktif.'
            );
        }

        if ($debug) {
            $this->catat(
                'bahaya',
                'APP_DEBUG',
                'true',
                'WAJIB false. Bila true, stack trace, potongan kode, dan variabel lingkungan '
                . '(termasuk kredensial database) terlihat oleh pengguna saat terjadi error.'
            );
        }

        if ($key === '') {
            $this->catat(
                'bahaya',
                'APP_KEY',
                '(kosong)',
                'Wajib diisi. Tanpa APP_KEY, sesi dan data terenkripsi tidak aman. '
                . 'Jalankan: php artisan key:generate'
            );
        }

        if (! str_starts_with($url, 'https://') && $env === 'production') {
            $this->catat(
                'peringatan',
                'APP_URL',
                $url,
                'Produksi sebaiknya memakai HTTPS agar cookie sesi terkirim dengan aman '
                . 'dan tautan yang dihasilkan menggunakan skema yang benar.'
            );
        }
    }

    private function periksaLogging(): void
    {
        $level = strtolower((string) config('logging.channels.stack.level', config('logging.level', 'debug')));

        if (in_array($level, ['debug', 'info'], true)) {
            $this->catat(
                'bahaya',
                'LOG_LEVEL',
                $level,
                'Produksi sebaiknya memakai warning atau error. Level debug menulis '
                . 'terlalu banyak ke berkas log dan dapat memenuhi disk server.'
            );
        }

        if (config('logging.default') === 'stack'
            && in_array(config('logging.channels.single'), ['single'], true)) {
            $this->catat(
                'peringatan',
                'LOG_STACK',
                (string) config('logging.channels.stack.driver', 'single'),
                'Kanal "single" menulis ke satu berkas tanpa rotasi. Gunakan "daily" '
                . 'agar berkas log tidak tumbuh tanpa batas.'
            );
        }
    }

    private function periksaSesi(): void
    {
        $encrypt = config('session.encrypt');
        $secure = config('session.secure');
        $driver = (string) config('session.driver');

        if (! $encrypt) {
            $this->catat(
                'bahaya',
                'SESSION_ENCRYPT',
                'false',
                'Produksi sebaiknya true agar isi sesi terenkripsi di penyimpanan.'
            );
        }

        if (! $secure) {
            $this->catat(
                'peringatan',
                'SESSION_SECURE_COOKIE',
                'false',
                'Produksi sebaiknya true agar cookie sesi hanya dikirim melalui HTTPS '
                . 'dan tidak dapat disadap pada koneksi HTTP.'
            );
        }

        if ($driver === '') {
            $this->catat('bahaya', 'SESSION_DRIVER', '(kosong)', 'Driver sesi wajib diisi.');
        }
    }

    private function periksaEmail(): void
    {
        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->catat(
                'peringatan',
                'MAIL_MAILER',
                $mailer,
                'Pengiriman email NONAKTIF. Email hanya ditulis ke berkas log dan '
                . 'tidak pernah terkirim. Gunakan smtp bila fitur email akan dipakai.'
            );
        }
    }

    private function periksaSeeder(): void
    {
        // Membaca langsung dari .env karena SEED_PASSWORD tidak dibaca oleh config.
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            $this->catat('peringatan', '.env', '(tidak ada)', 'Berkas .env belum dibuat.');

            return;
        }

        $isi = File::get($envPath);

        if (! preg_match('/^SEED_PASSWORD=(.*)$/m', $isi, $m)) {
            $this->catat(
                'peringatan',
                'SEED_PASSWORD',
                '(tidak diset)',
                'Belum diisi. Seeder akan berhenti dengan error bila dijalankan di produksi. '
                . 'Wajib diisi sebelum php artisan db:seed pada server produksi.'
            );

            return;
        }

        $nilai = trim($m[1], " \t\"'");

        if ($nilai === '') {
            $this->catat(
                'bahaya',
                'SEED_PASSWORD',
                '(kosong)',
                'Kosong. Seeder menolak berjalan di produksi, namun bila dipaksa '
                . 'akan membuat akun dengan kredensial default.'
            );
        } elseif (in_array(strtolower($nilai), ['password', 'admin', '123456', 'secret'], true)) {
            $this->catat(
                'bahaya',
                'SEED_PASSWORD',
                '(lemah)',
                'Nilai terlalu mudah ditebak. Gunakan password kuat dan unik.'
            );
        }
    }

    // =========================================================================
    // Pelaporan
    // =========================================================================

    private function catat(string $level, string $nama, string $nilai, string $alasan): void
    {
        $this->temuan[] = [
            'level' => $level,
            'nama' => $nama,
            'nilai' => $nilai,
            'alasan' => $alasan,
        ];
    }

    private function laporkan(): int
    {
        $bahaya = array_values(array_filter($this->temuan, fn ($t) => $t['level'] === 'bahaya'));
        $peringatan = array_values(array_filter($this->temuan, fn ($t) => $t['level'] === 'peringatan'));

        $this->newLine();
        $this->line('  <options=bold>Pemeriksaan Konfigurasi Produksi — GATRA-GO</>');
        $this->line('  Berkas: <options=bold>' . base_path('.env') . '</>');
        $this->newLine();

        if (empty($this->temuan)) {
            $this->line('  <fg=green;options=bold>✓ Tidak ada masalah ditemukan.</>');
            $this->line('  Konfigurasi siap untuk produksi.');
            $this->newLine();

            return self::SUCCESS;
        }

        $this->table(
            ['', 'Variabel', 'Nilai', 'Keterangan'],
            array_map(function ($t) {
                $penanda = match ($t['level']) {
                    'bahaya' => '<fg=red;options=bold>BAHAYA</>',
                    'peringatan' => '<fg=yellow>PERINGATAN</>',
                    default => '<fg=gray>INFO</>',
                };

                return [$penanda, $t['nama'], $t['nilai'], $this->bungkus($t['alasan'], 62)];
            }, $this->temuan)
        );

        $this->newLine();

        if (! empty($bahaya)) {
            $this->line('  <fg=red;options=bold>✗ DITEMUKAN ' . count($bahaya) . ' KONFIGURASI BERBAHAYA.</>');
            $this->line('  <fg=red>JANGAN deploy ke produksi sebelum diperbaiki.</>');

            if (! empty($peringatan)) {
                $this->line('  Selain itu ada ' . count($peringatan) . ' peringatan yang perlu ditinjau.');
            }

            $this->newLine();
            $this->line('  Rujukan: <options=bold>.env.production.example</>');
            $this->newLine();

            return self::FAILURE;
        }

        $this->line('  <fg=yellow;options=bold>⚠ Ada ' . count($peringatan) . ' peringatan.</>');
        $this->line('  Tidak menghalangi deploy, namun sebaiknya ditinjau.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Bungkus teks panjang agar tabel tetap terbaca.
     */
    private function bungkus(string $teks, int $lebar): string
    {
        return wordwrap($teks, $lebar, "\n", true);
    }
}
