@include('errors.layout', [
    'kode' => '419',
    'judul' => 'Sesi Anda Telah Berakhir',
    'pesan' => 'Halaman ini sudah terlalu lama dibiarkan terbuka sehingga token '
        . 'keamanan sesi tidak lagi berlaku. Muat ulang halaman, lalu ulangi '
        . 'langkah terakhir Anda. Data yang belum dikirim tidak tersimpan.',
    'aksiLabel' => 'Muat Ulang Halaman',
    'aksiReload' => true,
])
