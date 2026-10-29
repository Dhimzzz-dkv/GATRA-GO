@props([
    'type' => 'success',
    'message' => null,
])

@php
    $variants = [
        'success' => [
            'container' => 'flash-message mb-4 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 transition-opacity duration-500',
            'icon' => 'text-state-success',
            'close' => 'text-state-success hover:text-green-800',
        ],
        'error' => [
            'container' => 'flash-message mb-4 flex items-center rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 transition-opacity duration-500',
            'icon' => 'text-state-danger',
            'close' => 'text-state-danger hover:text-red-800',
        ],
        'warning' => [
            'container' => 'flash-message mb-4 flex items-center rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 transition-opacity duration-500',
            'icon' => 'text-amber-700',
            'close' => 'text-amber-700 hover:text-amber-900',
        ],
        'info' => [
            'container' => 'flash-message mb-4 flex items-center rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-900 transition-opacity duration-500',
            'icon' => 'text-blue-700',
            'close' => 'text-blue-700 hover:text-blue-900',
        ],
    ];
    $variant = $variants[$type] ?? $variants['success'];
@endphp

@if ($message !== null && $message !== '')
    <div {{ $attributes->merge(['class' => $variant['container']]) }} role="{{ $type === 'error' ? 'alert' : 'status' }}">
        <svg aria-hidden="true" class="mr-2 h-5 w-5 flex-shrink-0 {{ $variant['icon'] }}" fill="currentColor" viewBox="0 0 20 20">
            @if ($type === 'error')
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            @elseif ($type === 'warning')
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM10 6a1 1 0 00-1 1v3a1 1 0 002 0V7a1 1 0 00-1-1zm0 7a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
            @elseif ($type === 'info')
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-9-1a1 1 0 112 0v5a1 1 0 11-2 0V9zm1-2a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            @else
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            @endif
        </svg>

        <span class="flex-1">{{ $message }}</span>

        <button type="button" onclick="this.parentElement.remove()" class="ml-3 transition {{ $variant['close'] }}" aria-label="Tutup notifikasi">
            <svg aria-hidden="true" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
@endif
