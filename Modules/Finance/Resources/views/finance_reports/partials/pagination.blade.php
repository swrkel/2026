@if($paginator->hasPages())
    <div class="finance-report-pagination">
        @if($paginator->onFirstPage())
            <span class="btn btn-default disabled">Previous</span>
        @else
            <a class="btn btn-default" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif
        <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a class="btn btn-default" href="{{ $paginator->nextPageUrl() }}">Next</a>
        @else
            <span class="btn btn-default disabled">Next</span>
        @endif
    </div>
@endif
