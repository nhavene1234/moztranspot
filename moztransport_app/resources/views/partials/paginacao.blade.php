<div class="pager">
    <span>Mostrando {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }}</span>
    <nav>
        @if ($paginator->onFirstPage())
            <span class="btn btn-sm btn-outline disabled">Anterior</span>
        @else
            <a class="btn btn-sm btn-outline" href="{{ $paginator->previousPageUrl() }}">Anterior</a>
        @endif
        @if ($paginator->hasMorePages())
            <a class="btn btn-sm btn-outline" href="{{ $paginator->nextPageUrl() }}">Seguinte</a>
        @else
            <span class="btn btn-sm btn-outline disabled">Seguinte</span>
        @endif
    </nav>
</div>
