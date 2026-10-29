<?php

namespace App\Services;

use App\Models\Pegawai;

/**
 * Menyelesaikan data penandatangan laporan cetak dari master Pegawai.
 *
 * Latar belakang: nama dan NIP penandatangan pada laporan "Cetak Format Resmi"
 * dahulu ditulis tetap di dalam blade, sehingga tidak bersumber dari master data
 * dan tidak dapat diubah oleh Super Admin. Kelas ini menggantikannya dengan
 * resolusi berjenjang:
 *
 *   1. NIP eksplisit dari config (bila diisi)
 *   2. Jabatan yang cocok dari master Pegawai
 *   3. Teks pengganti yang aman agar laporan tidak menampilkan nama kosong
 */
class SignatoryResolver
{
    /**
     * Data lengkap kedua penandatangan laporan.
     *
     * @return array{pembuat: array<string, ?string>, pengesah: array<string, ?string>}
     */
    public static function forLaporan(): array
    {
        return [
            'pembuat' => self::resolve('pembuat'),
            'pengesah' => self::resolve('pengesah'),
        ];
    }

    /**
     * Resolusi satu pihak penandatangan.
     *
     * @return array{nama: string, pangkat: ?string, nip: ?string, jabatan_label: string}
     */
    public static function resolve(string $pihak): array
    {
        $config = config("simantap.penandatangan.{$pihak}", []);
        $jabatanLabel = $config['jabatan_label'] ?? '';

        $pegawai = self::findPegawai($config);

        if ($pegawai === null) {
            return [
                'nama' => '',
                'pangkat' => null,
                'nip' => null,
                'jabatan_label' => $jabatanLabel,
            ];
        }

        return [
            'nama' => $pegawai->nama,
            'pangkat' => $pegawai->pangkat_golongan,
            'nip' => $pegawai->nip,
            'jabatan_label' => $jabatanLabel,
        ];
    }

    /**
     * Cari pegawai berdasarkan NIP eksplisit, atau jabatan yang cocok.
     */
    private static function findPegawai(array $config): ?Pegawai
    {
        $nip = $config['nip'] ?? null;

        if (! empty($nip)) {
            return Pegawai::query()->where('nip', $nip)->first();
        }

        $jabatanMatch = $config['jabatan_match'] ?? null;

        if (empty($jabatanMatch)) {
            return null;
        }

        return Pegawai::query()
            ->where('jabatan', 'like', "%{$jabatanMatch}%")
            ->orderBy('id')
            ->first();
    }
}
