<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Penandatangan Laporan Cetak
    |--------------------------------------------------------------------------
    |
    | Nama pejabat yang menandatangani laporan resmi ("Cetak Format Resmi").
    |
    | Diambil dari master data Pegawai berdasarkan NIP bila NIP terisi.
    | Bila NIP kosong pada salah satu pihak, maka jabatan pada master Pegawai
    | yang cocok dengan `jabatan_match` akan dicari secara otomatis.
    |
    | Sebelumnya kedua nama ini ditulis tetap di dalam blade laporan cetak,
    | sehingga tidak dapat diubah oleh Super Admin dan tidak bersumber dari
    | master data. Isi nilai di bawah hanya bila ingin menimpa hasil otomatis.
    |
    */

    'penandatangan' => [

        // Pihak kiri: pembuat laporan / tim evaluasi
        'pembuat' => [
            'nip' => env('SIMANTAP_TTD_PEMBUAT_NIP'),
            'jabatan_label' => 'Ketua Tim Evaluasi & Pelaporan,',
            'jabatan_match' => 'Auditor Ahli Muda',
        ],

        // Pihak kanan: pejabat yang mengesahkan
        'pengesah' => [
            'nip' => env('SIMANTAP_TTD_PENGESAH_NIP'),
            'jabatan_label' => 'Inspektur Daerah Provinsi Gorontalo',
            'jabatan_match' => 'Inspektur Daerah',
        ],

    ],

];
