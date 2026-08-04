@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="ui-pagination" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true">← Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Sebelumnya</a>
        @endif

        <div class="ui-pagination__pages">
            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page === $paginator->currentPage())
                    <span class="is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya →</a>
        @else
            <span class="is-disabled" aria-disabled="true">Berikutnya →</span>
        @endif
    </nav>
@endif
