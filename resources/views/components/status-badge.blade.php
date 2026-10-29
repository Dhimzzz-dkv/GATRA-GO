@props([
    'badge' => [],
    'size' => 'sm',
    'dotClass' => null,
])

@php
    $sizes = [
        'xs' => 'px-2.5 py-0.5 text-xs font-bold',
        'xs-wide' => 'px-2.5 py-0.5 text-xs font-bold',
        'sm' => 'px-2.5 py-0.5 text-xs font-semibold',
        'md' => 'px-3 py-1 text-xs font-semibold',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['sm'];
    $badgeClass = $badge['class'] ?? 'bg-gray-100 text-gray-700 border-gray-200';
    $label = $badge['label'] ?? '';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border {$sizeClass} {$badgeClass}"]) }}>
    @if ($dotClass)
        <span aria-hidden="true" class="rounded-full {{ $dotClass }} {{ $badge['dot'] ?? '' }}"></span>
    @endif
    {{ $label }}
</span>
