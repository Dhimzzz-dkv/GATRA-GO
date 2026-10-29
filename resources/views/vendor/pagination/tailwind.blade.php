@php
    // View pagination kustom GATRA-GO (Bahasa Indonesia).
    // Dipakai otomatis oleh Laravel bila ada di resources/views/vendor/pagination/tailwind.blade.php.
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        {{-- Keterangan rentang data --}}
        <p class="text-xs text-gray-500">
            Menampilkan
            <span class="font-bold text-gray-800">{{ $paginator->firstItem() }}</span>
            &ndash;
            <span class="font-bold text-gray-800">{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-bold text-gray-800">{{ $paginator->total() }}</span>
            data
        </p>

        <div class="flex items-center gap-1">
            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed"
                      aria-disabled="true" aria-label="Halaman sebelumnya">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:border-gray-300"
                   aria-label="Halaman sebelumnya">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Sebelumnya
                </a>
            @endif

            {{-- Nomor halaman --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex items-center justify-center rounded-xl px-3 py-1.5 text-xs font-semibold text-gray-400"
                          aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex min-w-[34px] items-center justify-center rounded-xl border border-primary-600 bg-primary-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm"
                                  aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}"
                               class="inline-flex min-w-[34px] items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:border-gray-300"
                               aria-label="Ke halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:border-gray-300"
                   aria-label="Halaman berikutnya">
                    Berikutnya
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed"
                      aria-disabled="true" aria-label="Halaman berikutnya">
                    Berikutnya
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
