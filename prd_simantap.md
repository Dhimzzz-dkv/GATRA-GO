# PRD — GATRA-GO: Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit

**Nama sistem:** GATRA-GO (Gerbang Akuntabilitas dan Tindak Lanjut Rekomendasi Audit — Gorontalo)
**Platform:** Web
**Framework:** Laravel 13
**Frontend:** Blade + Tailwind CSS
**Database:** MySQL/PostgreSQL
**Pengelola:** Inspektorat Provinsi Gorontalo
**Status dokumen:** Draft PRD Final untuk Validasi

---

## 1. Latar Belakang

Sistem ini merupakan sistem informasi yang dikembangkan sebagai **output kegiatan magang di Inspektorat** untuk membantu pengelolaan, pencatatan, monitoring, dan pelaporan temuan hasil pemeriksaan terhadap Organisasi Perangkat Daerah (OPD) di Provinsi Gorontalo.

Temuan yang dikelola berasal dari empat sumber pemeriksaan:

1. **BPK**
2. **BPKP**
3. **ITJEN**
4. **Inspektorat**

Untuk tahap awal, sumber **ITJEN difokuskan pada ITJEN Kementerian Dalam Negeri (Kemendagri)** karena mencakup sejumlah OPD di Provinsi Gorontalo. Namun, tampilan sistem tetap menggunakan label **ITJEN** sehingga ke depannya dapat dikembangkan untuk sumber ITJEN dari kementerian lainnya.

Sistem tidak memberikan kewenangan kepada OPD untuk menginput atau mengubah temuan. Data temuan dimasukkan oleh Admin dari masing-masing sumber pemeriksaan yang merupakan pegawai Inspektorat, kemudian diajukan kepada Super Admin untuk proses persetujuan dan pengelolaan status.

---

# 2. Tujuan Sistem

Sistem bertujuan untuk:

1. Menyediakan pusat data temuan pemeriksaan OPD.
2. Mengelompokkan temuan berdasarkan sumber pemeriksaan.
3. Mendokumentasikan proses pengajuan dan persetujuan laporan/temuan.
4. Memudahkan Inspektorat dalam memonitor status tindak lanjut temuan.
5. Menyediakan dashboard monitoring bagi pimpinan/pemantau.
6. Menyediakan rekapitulasi berdasarkan tahun, sumber, OPD, administrasi, dan kerugian.
7. Menyimpan dokumen pendukung pemeriksaan secara terstruktur.
8. Menyediakan laporan yang dapat dicetak/diekspor.
9. Menyediakan riwayat aktivitas pengguna untuk menjaga akuntabilitas data.

---

# 3. Ruang Lingkup

Sistem mencakup:

* Manajemen pengguna dan role.
* Master data OPD.
* Master data pegawai Inspektorat.
* Master sumber pemeriksaan.
* Data pemeriksaan.
* Data SPT.
* Data Tim Pemeriksa.
* Data LHP.
* Data temuan.
* Rekap administrasi dan kerugian.
* Pengajuan dan persetujuan.
* Status penyelesaian/tindak lanjut.
* Dashboard monitoring.
* Laporan dan cetak.
* Audit trail.
* Pengelolaan dokumen.
* Arsip dan penghapusan laporan sesuai kewenangan.

---

# 4. Role dan Hak Akses

## 4.1 Super Admin

Super Admin berasal dari pihak Inspektorat dan bertanggung jawab terhadap **keseluruhan sistem**.

### Hak akses:

* Mengelola pengguna.
* Mengelola role.
* Mengelola master OPD.
* Mengelola Master Data Pegawai.
* Melihat seluruh sumber pemeriksaan.
* Melihat seluruh data pemeriksaan.
* Memeriksa pengajuan.
* Menyetujui pengajuan.
* Menolak pengajuan.
* Mengembalikan pengajuan untuk revisi.
* Mengubah status penyelesaian.
* Mengarsipkan data.
* Menghapus data sesuai kewenangan.
* Melihat dashboard keseluruhan.
* Melihat audit trail.
* Mencetak/mengekspor laporan.

**Super Admin merupakan satu-satunya role yang memiliki kewenangan menetapkan dan mengubah Status Penyelesaian.**

---

## 4.2 Admin BPK

Admin BPK merupakan pegawai Inspektorat yang diberikan akses untuk mengelola data yang bersumber dari BPK.

Dapat:

* Membuat data pemeriksaan BPK.
* Menginput SPT.
* Mengunggah SPT.
* Menentukan Tim Pemeriksa.
* Menginput LHP.
* Menginput temuan BPK.
* Mengedit data yang menjadi kewenangannya sesuai status pengajuan.
* Mengajukan laporan kepada Super Admin.
* Melihat progress BPK.
* Melihat progress sumber lainnya.

Tidak dapat:

* Menginput temuan BPKP.
* Menginput temuan ITJEN.
* Menginput temuan Inspektorat.
* Mengubah Status Penyelesaian.
* Menyetujui pengajuan.

---

## 4.3 Admin BPKP

Memiliki konsep kewenangan yang sama dengan Admin BPK, tetapi hanya untuk data yang bersumber dari **BPKP**.

---

## 4.4 Admin ITJEN

Memiliki kewenangan terhadap data yang bersumber dari **ITJEN**.

Untuk tahap awal cakupan data ITJEN adalah **ITJEN Kemendagri**.

---

## 4.5 Admin Inspektorat

Memiliki kewenangan terhadap data yang bersumber dari **Inspektorat**.

Admin Inspektorat berbeda dengan Super Admin.

---

## 4.6 Pemantau

Pemantau terdiri dari:

* Gubernur
* Wakil Gubernur
* Sekretaris Daerah
* Inspektur

Masing-masing memiliki akun sendiri.

Pemantau memiliki akses **read-only** untuk:

* Melihat dashboard.
* Melihat seluruh OPD.
* Melihat progress pemeriksaan.
* Melihat jumlah temuan.
* Melihat status pengajuan.
* Melihat status penyelesaian.
* Melihat rekapitulasi berdasarkan sumber dan tahun.
* Melihat detail laporan yang diperbolehkan.

Pemantau tidak dapat mengubah data.

---

## 4.7 OPD

Setiap OPD memiliki akun sendiri.

OPD dapat:

* Login.
* Melihat data/temuan milik OPD.
* Melihat status pengajuan.
* Melihat status penyelesaian.
* Melihat progress tindak lanjut.

OPD **tidak dapat menginput, mengubah, atau menghapus temuan**.

---

# 5. Konsep Sumber Pemeriksaan

Sistem memiliki empat sumber utama:

| Sumber      | Keterangan                                |
| ----------- | ----------------------------------------- |
| BPK         | Badan Pemeriksa Keuangan                  |
| BPKP        | Badan Pengawasan Keuangan dan Pembangunan |
| ITJEN       | Inspektorat Jenderal                      |
| Inspektorat | Pemeriksaan oleh Inspektorat              |

Untuk ITJEN:

> Sistem tahap awal menggunakan **ITJEN Kemendagri**, tetapi label antarmuka tetap **ITJEN**.

Struktur sistem sebaiknya dibuat fleksibel agar sumber ITJEN dari kementerian lain dapat ditambahkan di masa depan.

---

# 6. Master Data Pegawai

Sistem memiliki **Master Data Pegawai Inspektorat**.

Data minimal:

* ID pegawai.
* Nama lengkap.
* NIP.
* Jabatan.
* Unit/bidang.
* Status aktif/nonaktif.

Master data pegawai digunakan untuk:

### Tim Pemeriksa

Admin memilih nama pegawai dari daftar yang tersedia.

### Akun pengguna

Akun Admin dikaitkan dengan data pegawai sehingga identitas pengguna dapat dicatat secara otomatis.

Contoh:

> Ahmad, S.E.
> Admin BPK

Ketika melakukan input:

> Penginput: Ahmad, S.E.

Tidak perlu mengetik nama secara manual.

---

# 7. Pemeriksaan

Data pemeriksaan menjadi induk dari data SPT, Tim Pemeriksa, LHP, dan temuan.

Informasi pemeriksaan minimal:

* Sumber pemeriksaan.
* OPD.
* Tahun pemeriksaan.
* SPT.
* Tim Pemeriksa.
* LHP.
* Temuan.

Struktur:

```text
PEMERIKSAAN
│
├── SPT
│   ├── Nomor SPT
│   └── Dokumen SPT
│
├── TIM PEMERIKSA
│   └── Pegawai Inspektorat
│
├── LHP
│
└── TEMUAN
    ├── Administrasi
    └── Kerugian
```

---

# 8. SPT

Sistem menyediakan fitur pengelolaan Surat Perintah Tugas (SPT).

Data:

* Nomor SPT.
* Dokumen SPT.
* Tanggal SPT jika diperlukan sesuai validasi Inspektorat.

Fitur:

* Upload SPT.
* Preview/download SPT.
* Penggantian dokumen sesuai kewenangan.
* Pencatatan pengguna yang mengunggah dokumen.

---

# 9. Tim Pemeriksa

Tim Pemeriksa dipilih dari **Master Data Pegawai Inspektorat**.

Satu pemeriksaan dapat memiliki beberapa anggota Tim Pemeriksa.

Contoh:

```text
Pemeriksaan OPD X
│
└── Tim Pemeriksa
    ├── Pegawai A
    ├── Pegawai B
    └── Pegawai C
```

Nama tidak diketik manual.

Admin memilih pegawai yang tersedia pada Master Data Pegawai.

---

# 10. LHP

Laporan Hasil Pemeriksaan (LHP) menjadi bagian dari data pemeriksaan.

Data yang dapat disimpan:

* Nomor LHP.
* Tanggal LHP.
* Dokumen LHP.
* Keterangan lain sesuai kebutuhan Inspektorat.

LHP dapat dikaitkan dengan satu kegiatan pemeriksaan dan memiliki beberapa temuan.

---

# 11. Temuan

Temuan merupakan inti dari sistem.

Setiap temuan minimal memiliki:

* Nomor temuan.
* Sumber pemeriksaan.
* OPD.
* Tahun.
* Pemeriksaan.
* LHP.
* Uraian temuan.
* Nilai kerugian jika ada.
* Informasi administrasi.
* Informasi kerugian.
* Status pengajuan.
* Status penyelesaian.
* Penginput.
* Tanggal input.
* Dokumen pendukung jika diperlukan.

---

# 12. Administrasi dan Kerugian

**Administrasi dan kerugian merupakan bagian dari informasi temuan dan bukan status temuan.**

Keduanya tidak digunakan sebagai pilihan:

> Administrasi saja / Kerugian saja.

Struktur data harus mengikuti kebutuhan Inspektorat terkait apakah sebuah temuan memiliki unsur administrasi, kerugian, atau keduanya.

Rekap tahunan sistem mencakup data:

* Administrasi.
* Kerugian.

Untuk periode:

**2021–2026.**

---

# 13. Identitas Penginput

Setiap data yang dimasukkan harus memiliki informasi pengguna yang melakukan input.

Sistem mencatat secara otomatis:

* Nama pengguna.
* Role.
* Sumber pemeriksaan.
* Tanggal input.

Contoh:

> Penginput: Ahmad, S.E.
> Role: Admin BPK
> Sumber: BPK
> Tanggal: 27 Agustus 2026

Identitas tersebut berasal dari akun pengguna dan **tidak diinput secara manual**.

---

# 14. Status Pengajuan

**Status Pengajuan berbeda dengan Status Penyelesaian.**

Status Pengajuan menggambarkan proses administratif dari data yang dimasukkan oleh Admin Sumber sampai mendapatkan persetujuan.

Usulan status:

```text
DRAFT
   ↓
DIAJUKAN
   ↓
MENUNGGU PERSETUJUAN
   ↓
DISETUJUI
```

Jika terdapat masalah:

```text
MENUNGGU PERSETUJUAN
        ↓
   PERLU REVISI
        ↓
      DRAFT
        ↓
     DIAJUKAN
```

atau:

```text
MENUNGGU PERSETUJUAN
        ↓
     DITOLAK
```

### Kewenangan

| Aktivitas      | Admin Sumber | Super Admin |
| -------------- | -----------: | ----------: |
| Membuat Draft  |            ✅ |           ✅ |
| Mengajukan     |            ✅ |           — |
| Menyetujui     |            ❌ |           ✅ |
| Meminta Revisi |            ❌ |           ✅ |
| Menolak        |            ❌ |           ✅ |

---

# 15. Status Penyelesaian

Status Penyelesaian **sepenuhnya terpisah dari Status Pengajuan**.

Status ini menunjukkan kondisi tindak lanjut temuan.

Status yang digunakan:

1. **Belum Ditindaklanjuti**
2. **Belum Sesuai**
3. **Sesuai**
4. **Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah**

Contoh:

```text
Status Pengajuan
→ DISETUJUI

Status Penyelesaian
→ BELUM DITINDAKLANJUTI
```

Kemudian dapat berubah menjadi:

```text
BELUM DITINDAKLANJUTI
          ↓
    BELUM SESUAI
          ↓
       SESUAI
```

atau:

```text
BELUM DITINDAKLANJUTI
          ↓
TIDAK DAPAT DITINDAKLANJUTI
DENGAN ALASAN YANG SAH
```

### Kewenangan

**Hanya Super Admin** yang dapat mengubah Status Penyelesaian.

---

# 16. Alasan Status Khusus

Apabila Status Penyelesaian dipilih:

> **Tidak Dapat Ditindaklanjuti dengan Alasan yang Sah**

maka sistem harus menyediakan:

* Kolom alasan/keterangan.
* Dokumen pendukung apabila diperlukan.

Alasan wajib diisi sebelum status dapat disimpan.

---

# 17. Workflow Utama

```text
ADMIN SUMBER
     │
     ↓
BUAT PEMERIKSAAN
     │
     ├── OPD
     ├── Tahun
     ├── SPT
     ├── Tim Pemeriksa
     └── LHP
     │
     ↓
INPUT TEMUAN
     │
     ↓
DRAFT
     │
     ↓
AJUKAN
     │
     ↓
MENUNGGU PERSETUJUAN
     │
     ↓
SUPER ADMIN
     │
     ├── SETUJUI
     │
     ├── PERLU REVISI
     │
     └── TOLAK
     │
     ↓
DISETUJUI
     │
     ↓
STATUS PENYELESAIAN
     │
     ├── BELUM DITINDAKLANJUTI
     ├── BELUM SESUAI
     ├── SESUAI
     └── TIDAK DAPAT DITINDAKLANJUTI
         DENGAN ALASAN YANG SAH
```

---

# 18. Dashboard Super Admin

Dashboard harus memberikan gambaran keseluruhan kondisi pemeriksaan.

Minimal menampilkan:

### Statistik

* Total OPD.
* Total pemeriksaan.
* Total temuan.
* Total temuan BPK.
* Total temuan BPKP.
* Total temuan ITJEN.
* Total temuan Inspektorat.
* Total pengajuan menunggu persetujuan.
* Total temuan belum ditindaklanjuti.
* Total temuan belum sesuai.
* Total temuan sesuai.
* Total temuan tidak dapat ditindaklanjuti dengan alasan yang sah.

### Grafik

Sistem menyediakan **grafik progress pemeriksaan**.

Grafik dapat difilter berdasarkan:

* Tahun.
* Sumber pemeriksaan.
* OPD.

Grafik utama monitoring dapat menggunakan **Status Penyelesaian**, sedangkan Status Pengajuan ditampilkan sebagai statistik/progress pengajuan tersendiri.

---

# 19. Dashboard Pemantau

Dashboard Pemantau bersifat read-only.

Menampilkan:

* Total pemeriksaan.
* Total temuan.
* Progress pemeriksaan.
* Progress penyelesaian.
* Rekap berdasarkan sumber.
* Rekap berdasarkan tahun.
* Rekap berdasarkan OPD.
* Grafik status penyelesaian.

Pemantau tidak dapat mengubah data.

---

# 20. Dashboard Admin Sumber

Setiap Admin Sumber mendapatkan dashboard berdasarkan sumbernya.

Contoh Admin BPK:

> Fokus utama: data dan progress BPK.

Namun tetap dapat melihat progress:

* BPKP
* ITJEN
* Inspektorat

dalam bentuk monitoring.

Admin BPK tidak dapat mengelola data sumber lain.

---

# 21. Pencarian dan Filter

Sistem menyediakan pencarian dan filter berdasarkan:

* Nomor temuan.
* Nomor SPT.
* Nomor LHP.
* OPD.
* Tahun.
* Sumber pemeriksaan.
* Status Pengajuan.
* Status Penyelesaian.
* Nama penginput.
* Tim Pemeriksa.

---

# 22. Laporan dan Cetak

Sistem menyediakan laporan yang dapat:

* Ditampilkan di browser.
* Dicetak.
* Diekspor ke PDF.

Laporan dapat difilter berdasarkan:

* Tahun.
* OPD.
* Sumber pemeriksaan.
* Status.
* Pemeriksaan.

---

# 23. Format Tanda Tangan

Pada laporan cetak terdapat dua pihak yang menandatangani.

**Posisi kedua pihak ditukar dari format sebelumnya:**

```text
PIHAK 2                         PIHAK 1
  TTD                              TTD

Nama, NIP                      Nama, NIP
Jabatan                         Jabatan
```

Identitas pihak yang menandatangani harus tetap sesuai dengan pihaknya masing-masing.

Yang ditukar adalah **posisi kiri dan kanan**, bukan identitas pejabatnya.

---

# 24. Penghapusan dan Arsip

Karena terdapat kebutuhan dari Inspektorat bahwa laporan/kasus yang sudah selesai dapat dihapus, sistem menyediakan mekanisme penghapusan sesuai kewenangan.

Namun untuk menjaga keamanan data, mekanisme yang disarankan adalah:

**Soft Delete / Arsip terlebih dahulu**, bukan langsung menghapus data secara permanen.

Super Admin dapat:

* Mengarsipkan laporan.
* Menghapus data sesuai kewenangan.
* Melihat data yang telah diarsipkan apabila diperlukan.

Setiap penghapusan harus tercatat dalam **Audit Log**.

---

# 25. Audit Trail

Sistem harus mencatat aktivitas penting pengguna.

Contoh:

```text
27/08/2026 09:15
Ahmad — Admin BPK
Membuat Temuan BPK-001

27/08/2026 09:30
Ahmad — Admin BPK
Mengajukan Temuan BPK-001

28/08/2026 10:15
Budi — Super Admin
Menyetujui Temuan BPK-001

30/08/2026 14:20
Budi — Super Admin
Mengubah Status Penyelesaian
Belum Ditindaklanjuti → Sesuai
```

Aktivitas yang dicatat minimal:

* Login.
* Membuat data.
* Mengubah data.
* Mengajukan.
* Menyetujui.
* Menolak.
* Meminta revisi.
* Mengubah status.
* Mengarsipkan.
* Menghapus data.

---

# 26. Keamanan

Sistem harus menerapkan:

* Login dan autentikasi pengguna.
* Password yang disimpan dalam bentuk hash.
* Role-based access control.
* Pembatasan akses berdasarkan sumber pemeriksaan.
* Validasi upload file.
* Pembatasan format dan ukuran file.
* Proteksi terhadap akses data yang tidak sesuai role.
* Audit trail.
* Soft delete untuk data yang memerlukan pemulihan.

---

# 27. Tema dan UI/UX

Tema sistem menggunakan **warna ungu** sebagai identitas visual utama.

Karakter desain:

* Profesional.
* Formal.
* Bersih.
* Modern.
* Mudah digunakan.
* Cocok untuk sistem pemerintahan.

Warna ungu digunakan secara konsisten pada:

* Sidebar.
* Navbar.
* Tombol utama.
* Card.
* Badge.
* Grafik.
* Form.
* Tabel.
* Navigasi.

Warna semantik seperti berhasil, peringatan, dan error tetap menggunakan pembeda warna yang sesuai agar status mudah dikenali.

---

# 28. Struktur Menu Utama

### Super Admin

```text
Dashboard
Master Data
├── OPD
├── Pegawai
├── Pengguna
└── Sumber Pemeriksaan

Pemeriksaan
├── Semua Pemeriksaan
├── Pengajuan
└── Arsip

Temuan
├── Semua Temuan
├── Belum Ditindaklanjuti
├── Belum Sesuai
├── Sesuai
└── Tidak Dapat Ditindaklanjuti

Laporan
Audit Log
Pengaturan
```

### Admin Sumber

```text
Dashboard
Pemeriksaan
Temuan
Pengajuan
Laporan
Profil
```

Data otomatis dibatasi sesuai sumbernya.

### Pemantau

```text
Dashboard
Pemeriksaan
Temuan
Laporan
Profil
```

Read-only.

### OPD

```text
Dashboard
Temuan OPD
Progress
Profil
```

Read-only.

---

# 29. Relasi Data Tingkat Tinggi

Konsep database:

```text
USERS
  │
  └── PEGAWAI
        │
        └── TIM PEMERIKSA
                  │
OPD ─────────→ PEMERIKSAAN
                  │
                  ├── SPT
                  │
                  ├── LHP
                  │
                  └── TEMUAN
                        │
                        ├── Status Pengajuan
                        ├── Status Penyelesaian
                        └── Penginput
```

Sumber pemeriksaan:

```text
SOURCE
  │
  └── PEMERIKSAAN
        │
        └── TEMUAN
```

---

# 30. Prinsip Utama Sistem

PRD ini menggunakan prinsip:

> **Input terkontrol, persetujuan terpusat, monitoring terbuka sesuai kewenangan, dan seluruh perubahan penting dapat ditelusuri.**

Dengan pembagian:

```text
ADMIN SUMBER
Input & Pengajuan
       ↓
SUPER ADMIN
Validasi, Persetujuan & Status
       ↓
PEMANTAU
Monitoring
       ↓
OPD
Melihat Progress
```

---

# 31. Catatan untuk Validasi Inspektorat

PRD ini merupakan **rancangan berdasarkan hasil diskusi sementara** dan tetap perlu divalidasi kembali dengan pihak Inspektorat sebelum implementasi.

Hal yang terutama perlu dikonfirmasi:

1. Detail field pada Temuan.
2. Detail data SPT.
3. Detail data LHP.
4. Format Tim Pemeriksa.
5. Status pengajuan yang resmi digunakan.
6. Status penyelesaian yang resmi.
7. Siapa yang berwenang menetapkan status tertentu.
8. Mekanisme revisi dan penolakan.
9. Mekanisme laporan selesai.
10. Mekanisme penghapusan/arsip.
11. Format resmi laporan cetak.
12. Nama dan posisi dua pihak pada TTD.
13. Data yang dapat dilihat masing-masing Pemantau.
14. Data tambahan yang dibutuhkan untuk rekap administrasi dan kerugian.
15. Apakah periode data 2021–2026 sudah final atau dapat diperluas.

**Catatan penting:** poin-poin tersebut sebaiknya dianggap sebagai *requirement yang perlu dikonfirmasi*, bukan asumsi bahwa prosedur internal Inspektorat pasti sama persis dengan rancangan sistem ini.
