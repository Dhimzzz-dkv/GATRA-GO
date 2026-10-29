@props([
    'type' => 'lhp',
])

@php
    $variants = [
        'lhp' => 'lhp-badge inline-flex items-center rounded-md bg-primary-700 px-2.5 py-1 text-xs font-semibold text-white uppercase tracking-wide',
        'tindak-lanjut' => 'tl-badge inline-flex items-center rounded-md bg-emerald-700 px-2.5 py-1 text-xs font-semibold text-white uppercase tracking-wide',
    ];
    $badgeClass = $variants[$type] ?? $variants['lhp'];
@endphp

<span {{ $attributes->merge(['class' => $badgeClass]) }}>{{ $slot }}</span>
