@props(['items' => []])

<nav aria-label="Navigasi halaman" class="min-w-0">
    <ol class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-muted">
        @foreach ($items as $item)
            <li class="flex min-w-0 items-center gap-x-2">
                @if (! $loop->first)
                    <span aria-hidden="true" class="shrink-0 text-ink-subtle">/</span>
                @endif

                @if ($loop->last)
                    <span aria-current="page" class="max-w-48 truncate font-semibold text-ink">
                        {{ $item['label'] }}
                    </span>
                @elseif (isset($item['href']))
                    <a href="{{ $item['href'] }}" class="max-w-40 truncate rounded-sm transition-colors hover:text-primary-700 focus-visible:underline">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="max-w-40 truncate">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
