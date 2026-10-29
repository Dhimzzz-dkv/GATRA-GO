@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'secondary',
])

@php
    $variants = [
        'secondary' => 'inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-control border border-border bg-white px-4 py-2 text-sm font-medium text-ink-muted transition-colors hover:border-border-strong hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-60',
        'filter' => 'primary-gradient inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-control px-4 py-2 text-sm font-semibold text-white transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60',
        'primary' => 'primary-gradient inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-control px-5 py-2.5 text-sm font-semibold text-white transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60',
        'primary-compact' => 'primary-gradient inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-control px-4 py-2 text-sm font-semibold text-white transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60',
        'filter-reset' => 'inline-flex min-h-10 cursor-pointer items-center justify-center rounded-control px-3 text-sm font-semibold text-primary-700 transition-colors hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-60',
    ];
    $buttonClass = $variants[$variant] ?? $variants['secondary'];
@endphp

@if ($href !== null)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $buttonClass]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $buttonClass]) }}>{{ $slot }}</button>
@endif
