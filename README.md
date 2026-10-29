# GATRA-GO

**Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit** — sistem informasi rekapitulasi, pemantauan, dan tindak lanjut pemeriksaan Inspektorat Provinsi Gorontalo.

Aplikasi internal untuk mengelola Surat Perintah Tugas (SPT), Laporan Hasil Pemeriksaan (LHP),
temuan pemeriksaan, rekomendasi, serta tindak lanjut dan verifikasinya.

---

## Daftar Isi

- [Teknologi](#teknologi)
- [Peran Pengguna](#peran-pengguna)
- [Alur Kerja Utama](#alur-kerja-utama)
- [Instalasi Lokal](#instalasi-lokal)
- [Menjalankan Test](#menjalankan-test)
- [Deploy ke Produksi](#deploy-ke-produksi)
- [Catatan Keamanan](#catatan-keamanan)
- [Struktur Direktori](#struktur-direktori)

---

## Teknologi

| Komponen | Versi |
|---|---|
| PHP | 8.3 |
| Laravel | 12/13 |
| Database | MySQL |
| Frontend | Blade + Tailwind CSS v4 (Vite) |
| Testing | PHPUnit |

---

## Peran Pengguna

| Peran | Kode | Kewenangan |
|---|---|---|
| Super Admin | `super_admin` | Akses penuh. Persetujuan pengajuan, verifikasi tindak lanjut, master data (OPD/Pegawai/Pengguna), audit trail, arsip & pemulihan |
| Admin Inspektorat | `admin_inspektorat` | Input SPT, LHP, temuan, tindak lanjut |
| Admin BPK | `admin_bpk` | Input LHP & tindak lanjut untuk sumber BPK |
| Admin BPKP | `admin_bpkp` | Input LHP & tindak lanjut untuk sumber BPKP |
| Admin ITJEN | `admin_itjen` | Input LHP & tindak lanjut untuk sumber ITJEN |
| Pemantau | `pemantau` | Hanya baca (read-only) seluruh data dan audit trail |
| OPD | `opd` | Menyampaikan realisasi tindak lanjut & bukti setor untuk instansinya |

Helper peran tersedia di `app/Models/User.php`:
`isSuperAdmin()`, `isAdminSumber()`, `isAdminInspektorat()`, `isPemantau()`, `isOpd()`, `canManageSpt()`.

---

## Alur Kerja Utama

```
DRAFT ──ajukan──▶ MENUNGGU PERSETUJUAN ──approve──▶ DISETUJUI
                        │
                        ├──revise──▶ PERLU REVISI ──ajukan──▶ MENUNGGU PERSETUJUAN
                        │
                        └──reject──▶ DITOLAK
```

1. **Input Pemeriksaan** — Super Admin / Admin Inspektorat membuat SPT beserta tim pemeriksa.
   Tersedia dua mode: *Wizard* (4 langkah) dan *Form Klasik*.
2. **Input LHP & Temuan** — satu SPT dapat memiliki banyak LHP, dan satu LHP banyak temuan
   (administrasi dan/atau kerugian daerah/negara).
3. **Pengajuan** — pemeriksaan diajukan untuk diverifikasi.
4. **Persetujuan** — Super Admin menyetujui, meminta revisi, atau menolak.
5. **Tindak Lanjut** — OPD menyampaikan realisasi dan mengunggah bukti setor.
6. **Verifikasi** — Super Admin memverifikasi keabsahan bukti setor.
7. **Laporan** — rekapitulasi, rincian, ekspor, dan cetak format resmi.

---

## Instalasi Lokal

### Prasyarat

- PHP 8.3 dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`
- Composer
- Node.js & npm
- MySQL 8

### Langkah

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Sesuaikan kredensial database di .env (DB_DATABASE, DB_USERNAME, DB_PASSWORD)

# 4. Skema database + data contoh
php artisan migrate --seed

# 5. Build aset
npm run build
```

### Menjalankan

```bash
php artisan serve
# atau
npm run dev
```

### Akun Bawaan (hanya lingkungan lokal)

Semua akun hasil seeding memakai password `password` bila variabel `SEED_PASSWORD`
tidak diisi **dan** lingkungan bukan produksi.

| Peran | Email |
|---|---|
| Super Admin | `superadmin@inspektorat.go.id` |
| Admin Inspektorat | `admin.inspektorat@inspektorat.go.id` |
| Admin BPK | `admin.bpk@inspektorat.go.id` |
| Admin BPKP | `admin.bpkp@inspektorat.go.id` |
| Admin ITJEN | `admin.itjen@inspektorat.go.id` |
| Pemantau | `pemantau@gorontaloprov.go.id` |
| OPD Dinas Kesehatan | `opd.dinkes@gorontaloprov.go.id` |
| OPD Dinas Pendidikan | `opd.dikbud@gorontaloprov.go.id` |

> **Penting:** akun-akun ini hanya untuk pengembangan. Di produksi, `SEED_PASSWORD`
> wajib diisi — seeder akan berhenti dengan error bila kosong.

---

## Menjalankan Test

```bash
# Seluruh suite
php artisan test

# Filter kelas tertentu
php artisan test --filter=PemeriksaanTest

# Hanya test yang gagal
php artisan test --filter=Phase1CorrectnessTest
```

### Status Suite

| Keterangan | Jumlah |
|---|---|
| Total test | 296 |
| Lulus | 296 |
| Gagal | 0 |

Seluruh test lulus. Tidak ada kegagalan yang diketahui.

Test penjaga regresi penting:

| File | Yang dijaga |
|---|---|
| `Phase1CorrectnessTest.php` | DOM ID duplikat, UI mati, arsip multi-entitas, guard link role, audit trail aksi workflow |
| `FeedbackLoopTest.php` | Ringkasan error validasi, pagination Bahasa Indonesia, guard double-submit |
| `SafetyTest.php` | Konfirmasi toggle-status, antrean persetujuan, badge sidebar, tombol Buat/Edit temuan |
| `VisualConsistencyTest.php` | Skema warna badge Sumber seragam, label tabel laporan eksplisit, label tombol Filter/Detail seragam |
| `WizardRenderTest.php` | Syntax JS inline, variabel template yang tidak terdefinisi |
| `ProductionReadinessTest.php` | Seeder menolak jalan di produksi tanpa `SEED_PASSWORD`, template `.env.production.example` aman |
| `RelationEagerLoadTest.php` | Tidak ada halaman yang memicu lazy loading; menu Pemeriksaan & SPT terbuka untuk semua peran |
| `MenuPemeriksaanHttpTest.php` | Menu "Pemeriksaan & SPT" mengembalikan 200 (bukan halaman error) lewat alur HTTP lengkap |
| `LazyLoadingSweepTest.php` | Sapuan 7 peran x 35+ halaman (semua entitas via route binding), termasuk penambahan data agar tiap model >1 baris |
| `VerifyStrictModeTest.php` | Membuktikan `first()` dan route binding juga menegakkan lazy loading, bukan hanya koleksi |
| `RelationNameIntegrityTest.php` | Nama relasi pada `with()`/`load()` valid; semua method relasi punya return type |
| `TransactionIntegrityTest.php` | Rollback transaksi nyata; status pemeriksaan & temuan selalu sinkron; berkas bukti tidak terhapus bila DB gagal |
| `ErrorPageTest.php` | 4 halaman error ada & dirender; tidak membocorkan detail teknis; tidak butuh autentikasi |
| `ProductionConfigGuardTest.php` | Command cek konfigurasi menangkap 9 nilai berbahaya; exit code dapat dipakai di skrip deploy |

---

## Deploy ke Produksi

### 1. Siapkan `.env`

```bash
cp .env.production.example .env
php artisan key:generate
```

Isi seluruh nilai bertanda `<<< GANTI >>>`. Yang **wajib** dipastikan:

| Variabel | Nilai wajib | Alasan |
|---|---|---|
| `APP_ENV` | `production` | Mengaktifkan jalur kode produksi |
| `APP_DEBUG` | `false` | Jika `true`, stack trace dan kredensial database tampil ke pengguna saat terjadi error |
| `APP_URL` | URL HTTPS instansi | Dipakai untuk pembentukan tautan dan paksa HTTPS |
| `SEED_PASSWORD` | Password kuat & unik | Mencegah akun bawaan berpassword default |
| `LOG_LEVEL` | `warning` atau `error` | `debug` menulis terlalu banyak ke berkas log |
| `MAIL_MAILER` | `smtp` | `log` **tidak mengirim** email apa pun |
| `SESSION_ENCRYPT` | `true` | Mengenkripsi data sesi |
| `SESSION_SECURE_COOKIE` | `true` | Cookie hanya dikirim melalui HTTPS |

### 2. Build & cache

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# WAJIB: periksa konfigurasi sebelum lanjut.
# Command ini GAGAL (exit code ≠ 0) bila ada nilai berbahaya.
php artisan app:check-production-config

php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan db:seed --class=OpdSeeder --force
php artisan db:seed --class=PegawaiSeeder --force
php artisan db:seed --class=UserSeeder --force   # memerlukan SEED_PASSWORD

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Jangan mengakses `.env` dari web server. Arahkan document root ke direktori `public/`.

#### Pemeriksaan konfigurasi produksi

`php artisan app:check-production-config` memeriksa `.env` aktif dan melaporkan
nilai yang berbahaya untuk produksi:

| Variabel | Nilai berbahaya | Akibat bila dibiarkan |
|---|---|---|
| `APP_DEBUG` | `true` | **Stack trace dan kredensial database terlihat pengguna** |
| `APP_ENV` | bukan `production` | Jalur kode produksi tidak aktif |
| `APP_KEY` | kosong | Sesi & enkripsi tidak aman |
| `LOG_LEVEL` | `debug`/`info` | Berkas log membengkak memenuhi disk |
| `SESSION_ENCRYPT` | `false` | Isi sesi tidak terenkripsi |
| `SESSION_SECURE_COOKIE` | `false` | Cookie sesi dapat disadap via HTTP |
| `MAIL_MAILER` | `log`/`array` | Email tidak pernah terkirim |
| `SEED_PASSWORD` | kosong / lemah | Akun dibuat dengan kredensial default |
| `APP_URL` | bukan `https://` | Tautan & cookie tidak aman |

Exit code ≠ 0 bila ada nilai berbahaya, sehingga command ini dapat dipakai
sebagai langkah wajib dalam skrip rilis otomatis.

Rujukan: `.env.production.example`

### 3. Queue Worker

`QUEUE_CONNECTION=database` digunakan. Jalankan worker melalui supervisor:

```ini
[program:simantap-worker]
command=php /var/www/simantap/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
```

### 4. Pasca-deploy

```bash
# Setiap kali ada perubahan kode
php artisan down --retry=60
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

**Backup rutin wajib** untuk:
- Database MySQL (`mysqldump` harian)
- Direktori `storage/app/private` — berisi berkas SPT, LHP, dan bukti setor

---

## Catatan Keamanan

### Yang sudah diterapkan

- **Otorisasi berlapis** — middleware `role:super_admin` untuk master data, 83 pemeriksaan
  peran eksplisit di controller, dan 63 test otorisasi negatif.
- **Pembatasan percobaan login** — 5 kali gagal per 60 detik per kombinasi email+IP,
  dengan pencatatan `LOGIN_LOCKED` ke audit trail.
- **Audit trail** — 14 jenis aksi tercatat, termasuk `LOGIN_FAILED` dan `FORCE_DELETE`,
  dengan diff sebelum/sesudah untuk aksi workflow.
- **Soft delete + pemulihan** — Pemeriksaan, Temuan, OPD, Pegawai, dan User dapat dipulihkan
  melalui menu *Arsip & Pemulihan*.
- **Penyimpanan berkas privat** — `FILESYSTEM_DISK=local` menulis ke `storage/app/private`,
  tidak dapat diakses langsung dari web.
- **Eskaping output** — tidak ada penggunaan `{!! !!}` di seluruh view.
- **Paksa HTTPS** — otomatis bila `APP_URL` diawali `https://` atau melalui `X-Forwarded-Proto`.

### Yang perlu diperhatikan saat operasional

- **Rotasi password akun bawaan** segera setelah deploy pertama.
- **Batasi akses database** hanya dari host aplikasi.
- **Aktifkan HTTPS** dan arahkan HTTP ke HTTPS di level web server.
- **Pantau Audit Trail** secara berkala, khususnya aksi `LOGIN_FAILED` dan `FORCE_DELETE`.
- Direktori `storage/` dan `bootstrap/cache/` harus dapat ditulis oleh user web server.

### Mode ketat (khusus pengembangan)

Di lingkungan `local`, `AppServiceProvider` mengaktifkan:

- `Model::preventLazyLoading()` — menangkap N+1 query sedini mungkin
- `Model::preventSilentlyDiscardingAttributes()` — menangkap kolom yang tidak ada di `$fillable`
- `Model::preventAccessingMissingAttributes()` — menangkap akses atribut yang tidak dimuat

Di produksi, `preventLazyLoading` tetap aktif untuk mencegah pembebanan berlebih pada database.

---

## Struktur Direktori

```
app/
├── Http/
│   ├── Controllers/      # 18 controller
│   ├── Middleware/       # RoleMiddleware (alias: role)
│   └── Requests/         # FormRequest validasi
├── Models/               # 10 model (semuanya SoftDeletes kecuali Role)
├── Services/
│   ├── AuditLogger.php       # Pencatatan audit trail
│   └── PemeriksaanService.php # Logika transaksi SPT/LHP/Temuan
└── Providers/

database/
├── migrations/           # 25 migrasi
└── seeders/              # 10 seeder

resources/views/
├── layouts/              # app, sidebar, navbar
├── dashboard.blade.php
├── pemeriksaan/          # termasuk wizard/ (5 partial)
├── lhp/
├── temuan/
├── tindak_lanjut/
├── laporan/
├── audit_log/
├── opd/ · pegawai/ · user/
└── auth/

tests/Feature/            # 18 kelas test
```

---

## Status Pengembangan

| Tahap | Status |
|---|---|
| Phase 1 — Correctness (blocker & risiko data loss) | Selesai |
| Hardening produksi | Selesai |
| Phase 2 — Feedback (error summary, loading state, pagination ID) | Selesai |
| Phase 3 — Safety (approval queue, konfirmasi toggle-status) | Selesai |
| Phase 4 — Konsistensi visual & perbaikan label tabel laporan | Selesai |

### Umpan balik antarmuka (Phase 2)

- **Ringkasan error validasi global** — `resources/views/components/validation-errors.blade.php`,
  di-include dari `layouts/app.blade.php`. Menampilkan seluruh pesan error dengan jumlahnya,
  dapat diklik untuk menggulir dan memfokuskan field bermasalah (termasuk membuka modal atau
  `<details>` yang menutupinya). Tidak ditutup otomatis — pengguna harus mengatasi masalahnya dulu.
- **Loading state global** — setiap form menampilkan spinner dan menonaktifkan tombol submit
  selama pengiriman, mencegah pengiriman ganda. Form dapat dikecualikan dengan `data-no-loading`.
- **Pagination Bahasa Indonesia** — `resources/views/vendor/pagination/tailwind.blade.php`.
- **Flash message** — tertutup otomatis setelah 8 detik, tetapi berhenti selama kursor berada
  di atasnya atau saat ada elemen di dalamnya yang difokuskan.

### Keamanan operasional (Phase 3)

- **Antrean Persetujuan** — halaman tersendiri di `/pengajuan` untuk Super Admin, dengan kartu
  ringkasan (menunggu persetujuan / perlu revisi), filter status & sumber, serta tiga aksi
  keputusan langsung: Setujui, Minta Revisi, Tolak. Sebelumnya route ini hanya me-redirect
  ke daftar pemeriksaan sehingga tidak pernah punya halaman sendiri.
- **Badge sidebar** — menu "Antrean Persetujuan" menampilkan jumlah pengajuan yang menunggu
  keputusan. Sidebar kini menunjukkan dua antrean berbeda: persetujuan pengajuan (badge merah)
  dan verifikasi tindak lanjut (badge kuning).
- **Konfirmasi toggle status** — menonaktifkan Pengguna, OPD, atau Pegawai kini meminta
  konfirmasi yang menyebut nama entitas dan menjelaskan dampaknya (mis. "Pengguna ini tidak
  akan dapat masuk ke sistem"). Sebelumnya satu klik langsung mengubah status.
- **Tombol Buat & Edit pada Daftar Temuan** — `temuan/index` kini punya tombol Tambah Temuan
  dan tombol Edit per baris, sejalan dengan halaman daftar lainnya.
- **Konfirmasi hapus kontekstual** — menghapus temuan menyebut judulnya dan menjelaskan bahwa
  tindak lanjut serta rekomendasi terkait ikut terhapus.

### Konsistensi visual (Phase 4)

- **Komponen badge Sumber** — `resources/views/components/sumber-badge.blade.php` menjadi
  satu-satunya sumber skema warna: BPK = emerald, BPKP = blue, ITJEN = amber,
  Inspektorat = purple. Sebelumnya skema ini tersebar di **7 lokasi** dengan **3 variasi
  berbeda**, dan halaman arsip sempat **menukar warna BPK dengan BPKP** — sebuah kesalahan
  yang berpotensi membuat pengguna salah membaca sumber pemeriksaan.
- **Label tabel laporan eksplisit** — sel kerugian yang dahulu menampilkan tiga angka
  dipisah garis miring (`X / Y / Z`) tanpa keterangan kini memakai label yang terbaca:
  `Nilai:`, `Disetor:`, `Sisa:`. Header kolom juga menjelaskan ketiga baris nilai tersebut.
- **Tombol filter seragam** — semua halaman memakai label `Filter`
  (sebelumnya bervariasi: "Filter", "Cari", "Terapkan Filter").
- **Tautan detail seragam** — semua halaman memakai label `Lihat Detail`
  (sebelumnya: "Detail", "Rincian", "Detail →", "Lihat Rincian").
- **Suite test 100% hijau** — tiga test `LaporanTest` yang lama gagal karena menguji
  desain tabel yang sudah digantikan format kartu naratif telah diselaraskan.
  Aplikasi kini memiliki 228 test yang seluruhnya lulus.

### Catatan pemeliharaan: halaman error kustom

`resources/views/errors/` berisi 4 halaman mandiri: `403`, `404`, `419`, `500`,
beserta `layout.blade.php` sebagai kerangka bersama.

| Kode | Kapan muncul | Tindakan yang ditawarkan |
|---|---|---|
| 403 | Akses ditolak | Kembali ke Dashboard |
| 404 | Data tidak ditemukan | Kembali ke Dashboard |
| 419 | Sesi kedaluwarsa (token CSRF) | Muat ulang halaman |
| 500 | Kesalahan server | Kembali ke Dashboard |

**Aturan penting:** halaman error **tidak boleh** mewarisi `layouts.app`.
Layout tersebut membaca `auth()->user()` melalui sidebar — bila error terjadi
pada request tanpa pengguna terautentikasi, mewarisi layout itu dapat memicu
error kedua saat menampilkan error pertama. Halaman 419 khususnya sering terjadi
justru ketika sesi sudah tidak valid.

**Aturan kedua:** halaman 500 tidak boleh menampilkan detail teknis apa pun
(nama exception, stack trace, nama tabel, kredensial). Penjaganya:
`ErrorPageTest::test_error_pages_do_not_leak_technical_details`.

**Catatan tentang `APP_DEBUG`:** selama `APP_DEBUG=true`, Laravel tetap
menampilkan halaman debug bawaan untuk error 500 (beserta stack trace dan
variabel lingkungan) — halaman kustom hanya dipakai bila `APP_DEBUG=false`.
Ini alasan mengapa `APP_DEBUG=false` wajib di produksi, dan mengapa Tahap 3
(command `app:check-production-config`) dibuat.

### Catatan pemeliharaan: atomisitas transaksi

Beberapa aksi mengubah **lebih dari satu tabel** sekaligus. Semua berada dalam
`DB::transaction(..., 3)` (retry 3× untuk deadlock MySQL):

| Controller | Method | Tabel yang diubah |
|---|---|---|
| `PengajuanController` | `submit`, `cancel`, `approve`, `revise`, `reject` | `pemeriksaans` + `temuans` |
| `TindakLanjutController` | `store`, `verifikasi`, `destroy` | `tindak_lanjuts` + `rekomendasis` + `temuans` |
| `RekomendasiController` | `store`, `destroy` | `rekomendasis` + `temuans` |

**Alasan:** kegagalan di tengah proses tanpa transaksi meninggalkan data tidak
sinkron secara permanen — misalnya pemeriksaan berstatus `DISETUJUI` sementara
temuannya masih `MENUNGGU PERSETUJUAN`, sehingga OPD tidak dapat menindaklanjuti.

**Aturan urutan berkas:** pada operasi yang menghapus berkas,
**database lebih dulu, berkas kemudian**. Bila dibalik, kegagalan database akan
menghilangkan bukti setor secara permanen padahal record-nya masih ada.
Berkas yatim (sisa) jauh lebih ringan dampaknya daripada record tanpa bukti.

**Aturan AuditLogger:** pencatatan audit selalu **di luar** transaksi.
`AuditLogger` sudah memiliki `try/catch` internal; bila diletakkan di dalam,
kegagalan pencatatan log dapat membatalkan operasi yang sebenarnya sah.

Penjaga: `TransactionIntegrityTest` membuktikan rollback nyata, konsistensi
status antar tabel, dan ketahanan berkas bukti terhadap kegagalan database.

### Catatan pemeliharaan: mencegah halaman 500 akibat lazy loading

**Latar belakang.** Aplikasi mengaktifkan `Model::preventLazyLoading()` di semua
lingkungan. Setiap akses relasi Eloquent yang belum di-eager-load akan melempar
`LazyLoadingViolationException` → HTTP 500. Ini pernah menyebabkan halaman
pemeriksaan, temuan, LHP, dan laporan gagal dibuka.

**Bug Laravel 13 yang memperburuk masalah.** `preventLazyLoading` disimpan di
property *instance* model, dan `Illuminate\Database\Eloquent\Builder::hydrate()`
hanya menyalinnya dari flag static bila hasil query **lebih dari satu baris**:

```php
// vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php:497
$model = $instance->newFromBuilder($item);

if (count($items) > 1) {                              // ← hanya bila >1 baris
    $model->preventsLazyLoading = Model::preventsLazyLoading();
}
```

Akibatnya `first()`, `find()`, dan route model binding **tidak pernah** menegakkan
aturan ini. Bug hanya muncul pada halaman yang memakai `get()`/`paginate()`,
sementara halaman detail — yang justru paling sering dibuka pengguna — lolos.

**Solusi.** `app/Models/Concerns/EnforcesLazyLoading.php` menimpa
`newFromBuilder()` dan `newInstance()` sehingga flag dipasang pada setiap model,
berapa pun jumlah barisnya. Trait ini sudah dipakai oleh **seluruh 11 model**.

**Aturan wajib bagi pengembang:**

1. Setiap view yang mengakses relasi (termasuk lewat accessor seperti
   `official_name`) mengharuskan relasi tersebut di-eager-load di controller.
2. Jalankan `php artisan test --filter=LazyLoadingSweepTest` sebelum commit.
   Test ini menyapu 7 peran × 35+ halaman — termasuk seluruh entitas melalui
   route model binding — dan akan gagal bila ada relasi yang tertinggal.
3. Jangan mematikan `preventLazyLoading` untuk membuat test lulus. Bila test
   gagal karena relasi belum dimuat, itu memang bug yang harus diperbaiki.

**Relasi yang selalu dimuat.** `App\Models\User` memiliki
`protected $with = ['role', 'pegawai', 'opd']` karena `role` dipakai oleh
`scopeForUser()` pada hampir setiap query, dan `official_name` (yang membaca
`pegawai`) ditampilkan di banyak halaman.

### Catatan pemeliharaan

Skema warna badge Sumber **hanya boleh** diubah di
`resources/views/components/sumber-badge.blade.php`. Test `VisualConsistencyTest`
akan gagal bila ada developer menulis kembali skema warna secara inline di view lain -
ini disengaja, untuk mencegah terulangnya inkonsistensi antar halaman.

### Keterbacaan Audit Trail & Laporan Cetak

- **Label aksi lengkap** — seluruh 16 jenis aksi yang ditulis aplikasi kini punya
  label Bahasa Indonesia. Sebelumnya 5 di antaranya tampil sebagai kode mentah
  (`FORCE_DELETE`, `LOGIN_FAILED`, `LOGIN_LOCKED`, `CANCEL_SUBMIT`, `DOWNLOAD`) —
  termasuk dua peristiwa keamanan yang paling perlu terlihat. Dropdown filter juga
  tidak lagi menampilkan kode mentah.
  Metode `AuditLog::knownActions()` menjadi acuan; test
  `AuditTrailLabelTest::test_every_recorded_action_has_an_indonesian_label`
  akan gagal bila ada aksi baru tanpa label.
- **Label modul** — `TINDAK_LANJUT`, `PEMERIKSAAN`, dan `AUTH` kini tampil sebagai
  "Tindak Lanjut", "Pemeriksaan", dan "Autentikasi".
- **Penandatangan laporan bersumber dari master Pegawai** — sebelumnya nama dan NIP
  kedua penandatangan ditulis tetap di dalam `laporan/cetak.blade.php`, dan
  **tidak ada satu pun yang cocok dengan master Pegawai**, sehingga laporan resmi
  mencetak pejabat yang salah dan tidak dapat diperbaiki oleh Super Admin.
  Kini diselesaikan oleh `App\Services\SignatoryResolver` dengan urutan:
  NIP eksplisit dari config → jabatan yang cocok → penanda "belum diatur".

  Untuk mengubah penandatangan, gunakan variabel lingkungan berikut
  (lihat `config/simantap.php`):

  ```env
  SIMANTAP_TTD_PEMBUAT_NIP=
  SIMANTAP_TTD_PENGESAH_NIP=
  ```

  Bila dibiarkan kosong, penandatangan dicari otomatis dari master Pegawai
  berdasarkan jabatan (`Auditor Ahli Muda` dan `Inspektur Daerah`).

### Diketahui belum dikerjakan

Semua item berikut **memerlukan tindakan di tingkat server atau pengujian**, bukan
perubahan kode:

- **Backup otomatis** — belum ada penjadwalan `mysqldump` atau pencadangan
  `storage/app/private`. Lihat bagian *Deploy ke Produksi*.
- **Uji beban** — belum pernah diuji dengan volume data produksi
  (mis. ribuan temuan lintas tahun).
- **UAT** — belum diuji bersama pengguna sungguhan (Super Admin & OPD)
  memakai data operasional.
- **Kredensial instansi** — `MAIL_*` dan `DB_*` pada `.env` produksi
  harus diisi dengan nilai sebenarnya.

---

## Lisensi

Perangkat lunak internal Inspektorat Provinsi Gorontalo. Tidak untuk didistribusikan ulang.
