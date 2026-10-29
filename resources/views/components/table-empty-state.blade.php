@props([
    'colspan' => 1,
])

<tr>
    <td colspan="{{ $colspan }}" {{ $attributes->merge(['class' => 'px-6 py-12 text-center text-gray-500']) }}>
        <div role="status" aria-live="polite" class="flex flex-col items-center justify-center">
            {{ $slot }}
        </div>
    </td>
</tr>
