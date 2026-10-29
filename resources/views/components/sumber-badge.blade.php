@props([
    'sumber' => null,
    'size' => 'sm',
])

@php
    /**
     * Badge "Sumber Pemeriksaan" — satu sumber kebenaran untuk skema warna.
     *
     * Sebelumnya skema ini tersebar di 7 lokasi dengan 3 variasi berbeda,
     * bahkan BPK dan BPKP tertukar warnanya di halaman arsip. Komponen ini
     * menyatukannya agar instansi tidak salah membaca sumber pemeriksaan.
     *
     * Skema kanonik (dipakai mayoritas halaman):
     *   BPK        → emerald
     *   BPKP       → blue
     *   ITJEN      → amber
     *   Inspektorat→ navy
     */
    $skema = [
        'BPK' => [
            'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        ],
        'BPKP' => [
            'class' => 'bg-blue-100 text-blue-800 border-blue-200',
        ],
        'ITJEN' => [
            'class' => 'bg-amber-100 text-amber-800 border-amber-200',
        ],
        'Inspektorat' => [
            'class' => 'bg-primary-100 text-primary-800 border-primary-200',
        ],
    ];

    $meta = $skema[$sumber] ?? [
        'class' => 'bg-gray-100 text-gray-800 border-gray-200',
    ];

    $ukuran = match ($size) {
        'xs' => 'px-1.5 py-0.5 text-xs rounded',
        'md' => 'px-2.5 py-1 text-xs rounded-lg',
        default => 'px-2 py-0.5 text-xs rounded-md',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 border font-bold tracking-wider {$ukuran} {$meta['class']}"]) }}
      title="Sumber Pemeriksaan: {{ $sumber ?? 'Tidak diketahui' }}">
    {{ $sumber ?? '-' }}
</span>
