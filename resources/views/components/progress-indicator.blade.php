@props(['value' => 0])

@php
    $progressValue = is_numeric($value) ? max(0, min(100, (float) $value)) : 0;
    $progressHue = round($progressValue * 1.2, 1);
@endphp

<div aria-hidden="true" {{ $attributes->merge(['class' => 'progress-indicator h-full rounded-full']) }}
     style="--progress-hue: {{ $progressHue }}deg; width: {{ $progressValue }}%"></div>
