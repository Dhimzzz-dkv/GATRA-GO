@include('errors.layout', [
    'kode' => '404',
    'judul' => 'Halaman Tidak Ditemukan',
    'pesan' => 'Halaman atau data yang Anda cari tidak ditemukan. Kemungkinan data '
        . 'sudah dihapus atau dipindahkan ke arsip. Periksa kembali menu '
        . 'Arsip & Pemulihan bila data tersebut pernah ada.',
    'aksiLabel' => 'Kembali ke Dashboard',
    'aksiUrl' => url('/dashboard'),
])
