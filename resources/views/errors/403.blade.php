@include('errors.layout', [
    'kode' => '403',
    'judul' => 'Akses Ditolak',
    'pesan' => 'Anda tidak memiliki kewenangan untuk membuka halaman atau data ini. '
        . 'Bila Anda merasa seharusnya berhak, sampaikan kepada administrator sistem '
        . 'agar hak akses peran Anda ditinjau.',
    'aksiLabel' => 'Kembali ke Dashboard',
    'aksiUrl' => url('/dashboard'),
])
