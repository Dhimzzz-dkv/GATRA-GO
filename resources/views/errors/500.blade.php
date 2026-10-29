@include('errors.layout', [
    'kode' => '500',
    'judul' => 'Terjadi Kesalahan pada Sistem',
    'pesan' => 'Permintaan Anda tidak dapat diproses karena kendala teknis pada '
        . 'sistem. Data yang sudah tersimpan tetap aman. Silakan coba lagi '
        . 'beberapa saat lagi.',
    'aksiLabel' => 'Kembali ke Dashboard',
    'aksiUrl' => url('/dashboard'),
])
