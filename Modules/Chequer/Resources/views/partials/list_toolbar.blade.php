@php
    $searchName = $searchName ?? 'q';
    $searchPlaceholder = $searchPlaceholder ?? 'Search ...';
    $createUrl = $createUrl ?? null;
    $createLabel = $createLabel ?? 'Add';
@endphp
<div class="cheq-list-tools">
    <div class="cheq-export-group">
        <button type="button" class="cheq-tool-btn purple"><i class="fa fa-columns"></i> Column Visibility</button>
        <button type="button" class="cheq-tool-btn teal"><i class="fa fa-file"></i> Export to CSV</button>
        <button type="button" class="cheq-tool-btn green"><i class="fa fa-file-excel"></i> Export to Excel</button>
        <button type="button" class="cheq-tool-btn orange"><i class="fa fa-file-pdf"></i> Export to PDF</button>
        <button type="button" class="cheq-tool-btn dark"><i class="fa fa-print"></i> Print</button>
    </div>
    <div class="cheq-entry-group">
        <span>Show</span>
        <select class="cheq-mini-select" name="per_page" form="cheq-filter-form" onchange="document.getElementById('cheq-filter-form').submit()">
            @foreach([25,50,100] as $size)
                <option value="{{ $size }}" {{ (int)request('per_page',25)===$size ? 'selected' : '' }}>{{ $size }}</option>
            @endforeach
        </select>
        <span>entries</span>
    </div>
    <form id="cheq-filter-form" class="cheq-search-form" method="get" action="{{ url()->current() }}">
        <input class="cheq-search-input" type="text" name="{{ $searchName }}" value="{{ request($searchName) }}" placeholder="{{ $searchPlaceholder }}">
        <button class="cheq-btn" type="submit"><i class="fa fa-search"></i> Search</button>
        @if(request()->query())
            <a class="cheq-btn gray" href="{{ url()->current() }}">Reset</a>
        @endif
    </form>
    @if($createUrl)
        <a class="cheq-btn" href="{{ $createUrl }}"><i class="fa fa-plus"></i> {{ $createLabel }}</a>
    @endif
</div>
